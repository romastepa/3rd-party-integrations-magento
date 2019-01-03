<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model;

use Emarsys\Emarsys\Helper\Data as EmarsysHelperData;

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
     * @var EmarsysHelperData
     */
    protected $emarsysHelperData;

    /**
     * Emarsysproductexport constructor.
     *
     * @param ProductCollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param CurrencyFactory $currencyFactory
     * @param File $file
     * @param EmarsysHelperData $emarsysHelperData
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
        EmarsysHelperData $emarsysHelperData,
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
        $this->emarsysHelperData = $emarsysHelperData;

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
     * @param null|string $excludedCategories
     * @return object
     */
    public function getCatalogExportProductCollection($storeId, $currentPageNumber, $attributes, $includeBundle = null, $excludedCategories = null)
    {
        try {
            /** @var \Magento\Store\Model\Store $store */
            $store = $this->storeManager->getStore($storeId);

            $collection = $this->productCollectionFactory->create()
                ->addStoreFilter($store->getId())
                ->setPageSize(self::BATCH_SIZE)
                ->setCurPage($currentPageNumber)
                ->addAttributeToSelect($attributes)
                ->addAttributeToSelect(['visibility', 'request_path'])
                ->addUrlRewrite()
                ->addPriceData();

            if (is_null($includeBundle)) {
                $includeBundle = $store->getConfig(EmarsysHelperData::XPATH_PREDICT_INCLUDE_BUNDLE_PRODUCT);
            }

            if (!$includeBundle) {
                $collection->addAttributeToFilter('type_id', ['neq' => \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE]);
            }
            if (is_null($excludedCategories)) {
                $excludedCategories = $store->getConfig(EmarsysHelperData::XPATH_PREDICT_EXCLUDED_CATEGORIES);
            }
            if ($excludedCategories) {
                $excludedCategories = explode(',', $excludedCategories);
                $collection->addCategoriesFilter(['nin' => $excludedCategories]);
            }

            //If we have multistock (custom module) we have to add
            //{{table}}.website_id = $store->getWebsiteId() to condition
            $collection->joinField(
                'inventory_in_stock',
                'cataloginventory_stock_item',
                'is_in_stock',
                'product_id = entity_id',
                null,
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
    public function saveToCsv($websiteId, $header, $processedStores, $merchantId, $logsArray)
    {
        $this->_mapHeader = $header;
        $this->_processedStores = $processedStores;
        $this->_preparedData = array();

        $fileDirectory = $this->emarsysHelperData->getEmarsysMediaDirectoryPath(ProductModel::ENTITY . '/' . $merchantId);
        $this->emarsysHelperData->checkAndCreateFolder($fileDirectory);

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
