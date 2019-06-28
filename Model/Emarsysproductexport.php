<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model;

use Emarsys\Emarsys\Helper\Data as EmarsysHelper;

use Magento\{
    Framework\App\Config\ScopeConfigInterface,
    Framework\Filesystem\Driver\File,
    Framework\Registry,
    Framework\Model\Context,
    Framework\Model\ResourceModel\AbstractResource,
    Framework\Data\Collection\AbstractDb,
    Framework\Model\AbstractModel,
    Catalog\Model\Product as ProductModel,
    Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory,
    Directory\Model\CurrencyFactory,
    Store\Model\StoreManagerInterface
};

/**
 * Class Emarsysproductexport
 * @package Emarsys\Emarsys\Model
 */
class Emarsysproductexport extends AbstractModel
{
    CONST EMARSYS_DELIMITER = '{EMARSYS}';
    CONST BATCH_SIZE = 500;

    protected $_preparedData = [];
    protected $_mapHeader = ['item'];
    protected $_processedStores = [];
    protected $_delimiter = ',';
    protected $_enclosure = '"';

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var CurrencyFactory
     */
    protected $currencyFactory;

    /**
     * @var File
     */
    protected $file;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * Emarsysproductexport constructor.
     *
     * @param ProductCollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param CurrencyFactory $currencyFactory
     * @param File $file
     * @param EmarsysHelper $emarsysHelper
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        CurrencyFactory $currencyFactory,
        File $file,
        EmarsysHelper $emarsysHelper,
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $context->getLogger();
        $this->currencyFactory = $currencyFactory;
        $this->file = $file;
        $this->emarsysHelper = $emarsysHelper;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('Emarsys\Emarsys\Model\ResourceModel\Emarsysproductexport');
    }

    /**
     * Get Catalog Product Export Collection
     * @param int|object $storeId
     * @param int $currentPageNumber
     * @param array $attributes
     * @param null|1|0 $includeBundle
     * @param array $excludedCategories
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getCatalogExportProductCollection($storeId, $currentPageNumber, $attributes, $includeBundle = null, $excludedCategories = [])
    {
        try {
            /** @var \Magento\Store\Model\Store $store */
            $store = $this->storeManager->getStore($storeId);

            $collection = $this->productCollectionFactory->create()
                ->addStoreFilter($store->getId())
                ->setPageSize(self::BATCH_SIZE)
                ->setCurPage($currentPageNumber)
                ->addAttributeToSelect($attributes)
                ->addAttributeToSelect(['visibility'])
                ->addUrlRewrite();

            if (is_null($includeBundle)) {
                $includeBundle = $store->getConfig(EmarsysHelper::XPATH_PREDICT_INCLUDE_BUNDLE_PRODUCT);
            }

            if (!$includeBundle) {
                $collection->addAttributeToFilter('type_id', ['neq' => \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE]);
            }

            if (!empty($excludedCategories)) {
                $collection->addCategoriesFilter(['nin' => $excludedCategories]);
            }

            $connection = $collection->getSelect()->getConnection();
            //If we have multistock (custom module) we have to add
            //$websiteId = $store->getWebsiteId() to condition
            $websiteId = 0;
            $joinCondition = $connection->quoteInto(
                'e.entity_id = stock_status_index.product_id' . ' AND stock_status_index.website_id = ?',
                $websiteId
            );

            $joinCondition .= $connection->quoteInto(
                ' AND stock_status_index.stock_id = ?',
                1
            );

            $collection->getSelect()->joinLeft(
                ['stock_status_index' => $connection->getTableName('cataloginventory_stock_status')],
                $joinCondition,
                [
                    'is_salable' => 'stock_status',
                    'qty'
                ]
            );

            //Minimal price left join
            $cond = $connection->prepareSqlCondition('price_index.customer_group_id', 0)
                . ' ' . \Magento\Framework\DB\Select::SQL_AND . ' '
                . $connection->prepareSqlCondition('price_index.website_id', $store->getWebsiteId());

            $least = $connection->getLeastSql(['price_index.min_price', 'price_index.tier_price']);
            $minimalExpr = $connection->getCheckSql(
                'price_index.tier_price IS NOT NULL',
                $least,
                'price_index.min_price'
            );

            $fields = [
                'price',
                'tax_class_id',
                'final_price',
                'minimal_price' => $minimalExpr,
                'min_price',
                'max_price',
                'tier_price',
            ];

            $collection->joinTable(
                ['price_index' => 'catalog_product_index_price'],
                'entity_id = entity_id',
                $fields,
                $cond,
                'left'
            );

            return $collection;
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    /**
     * Save CSV for Website
     *
     * @param int $websiteId
     * @param array $header
     * @param array $processedStores
     * @param string $merchantId
     * @param array $logsArray
     * @return string
     * @throws \Exception
     */
    public function saveToCsv($websiteId, $header, $processedStores, $logsArray)
    {
        $this->_mapHeader = $header;
        $this->_processedStores = $processedStores;
        $this->_preparedData = [];

        $fileDirectory = $this->emarsysHelper->getEmarsysMediaDirectoryPath(ProductModel::ENTITY . '/' . $websiteId);
        $this->emarsysHelper->checkAndCreateFolder($fileDirectory);

        $name = 'products_' . $websiteId . '.csv';
        $file = $fileDirectory . '/' . $name;

        $fh = fopen($file, 'w');
        $this->file->filePutCsv($fh, $this->_mapHeader, $this->_delimiter, $this->_enclosure);
        $this->_prepareData($fh);
        fclose($fh);

        return $file;
    }

    /**
     * Prepare Data for CSV
     *
     * @param resource $fh
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _prepareData($fh)
    {
        $currentPageNumber = 1;

        $columnCount = count($this->_mapHeader);
        $emptyArray = array_fill(0, $columnCount, "");

        $collection = $this->getCollection();
        $collection->setPageSize(self::BATCH_SIZE)
            ->setCurPage($currentPageNumber);

        $lastPageNumber = $collection->getLastPageNumber();

        while ($currentPageNumber <= $lastPageNumber) {
            if ($currentPageNumber != 1) {
                $collection->setCurPage($currentPageNumber);
                $collection->clear();
            }
            foreach ($collection as $product) {
                $data = [];
                $productId = $product->getId();
                $productData = explode(self::EMARSYS_DELIMITER, $product->getParams());
                foreach ($productData as $param) {
                    $item = unserialize($param);
                    $map = $this->_processedStores[$item['store']];

                    if (!isset($data[$productId])) {
                        $data[$productId] = array_fill(0, count($this->_mapHeader), "");
                    } else {
                        $processed = $data[$productId];
                        $data[$productId] = array_fill(0, count($this->_mapHeader), "");
                        $data[$productId] = $processed + $data[$productId];
                    }

                    foreach ($item['data'] as $key => $value) {
                        if (isset($map[$key])) {
                            if (isset($this->_mapHeader[$map[$key]]) &&
                                ($this->_mapHeader[$map[$key]] == 'price_' . $item['currency_code']
                                    || $this->_mapHeader[$map[$key]] == 'msrp_' . $item['currency_code']
                                )) {
                                $currencyCodeTo = $this->storeManager->getStore($item['store_id'])->getBaseCurrency()->getCode();
                                if ($item['currency_code'] != $currencyCodeTo) {
                                    $rate = $this->currencyFactory->create()->load($item['currency_code'])->getAnyRate($currencyCodeTo);
                                    $value = number_format($value * $rate, 2, '.', '');
                                }
                            }
                            $data[$productId][$map[$key]] = str_replace(["\n", "\r"], "", $value);
                        }
                    }
                }
                ksort($data[$productId]);

                if (count($data[$productId]) < $columnCount) {
                    $data[$productId] = $data[$productId] + $emptyArray;
                }

                $this->file->filePutCsv($fh, $data[$productId], $this->_delimiter, $this->_enclosure);
            }

            $currentPageNumber++;
        }
        return true;
    }
}
