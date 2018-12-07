<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Helper\Data as EmarsysDataHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Directory\Model\CurrencyFactory;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;

use Magento\Framework\Model\AbstractModel;

/**
 * Class Emarsysproductexport
 * @package Emarsys\Emarsys\Model
 */
class Emarsysproductexport extends AbstractModel
{
    CONST EMARSYS_DELIMITER = '{EMARSYS}';
    CONST BATCH_SIZE = 500;

    protected $_preparedData = array();

    protected $_mapHeader = array('item');

    protected $_processedStores = array();

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
     * @var \Magento\Framework\File\Csv
     */
    protected $csvWriter;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $ioFile;

    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    protected $dir;

    /**
     * @var \Magento\CatalogInventory\Helper\Stock
     */
    protected $stockFilter;

    /**
     * Emarsysproductexport constructor.
     *
     * @param ProductCollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param CurrencyFactory $currencyFactory
     * @param \Magento\Framework\Filesystem\Io\File $ioFile
     * @param \Magento\Framework\File\Csv $csvWriter
     * @param \Magento\Framework\Filesystem\DirectoryList $dir,
     * @param \Magento\CatalogInventory\Helper\Stock $stockFilter,
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
        \Magento\Framework\Filesystem\Io\File $ioFile,
        \Magento\Framework\File\Csv $csvWriter,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\CatalogInventory\Helper\Stock $stockFilter,
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
        $this->ioFile = $ioFile;
        $this->csvWriter = $csvWriter;
        $this->dir = $dir;
        $this->stockFilter = $stockFilter;

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
     * @return object
     */
    public function getCatalogExportProductCollection($storeId, $currentPageNumber, $attributes, $includeBundle, $excludedCategories)
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
                $includeBundle = $store->getConfig(EmarsysDataHelper::XPATH_PREDICT_INCLUDE_BUNDLE_PRODUCT);
            }

            if (!$includeBundle) {
                $collection->addAttributeToFilter('type_id', ['neq' => \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE]);
            }
            if (is_null($excludedCategories)) {
                $excludedCategories = $store->getConfig(EmarsysDataHelper::XPATH_PREDICT_EXCLUDED_CATEGORIES);
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
     * @param $websiteId
     * @return array
     * @throws \Exception
     */
    public function saveToCsv($websiteId)
    {
        $this->_mapHeader = array('item');
        $this->_preparedData = array();
        $this->_prepareData();

        $path = $this->dir->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR) . '/export';

        if (!is_dir($path)) {
            $this->ioFile->mkdir($path, 0775);
        }

        $name = 'products_' . $websiteId . '.csv';
        $file = $path . '/' . $name;

        $columnCount = count($this->_mapHeader);
        $emptyArray = array_fill(0, $columnCount, "");

        foreach ($this->_preparedData as &$row) {
            if (count($row) < $columnCount) {
                $row = $row + $emptyArray;
            }
        }

        $this->csvWriter
            ->setEnclosure('"')
            ->setDelimiter(',')
            ->saveData($file, ([$this->_mapHeader] + $this->_preparedData));

        return array($file, $name);
    }

    /**
     * Prepare Data for CSV
     *
     * @return array
     */
    protected function _prepareData()
    {
        $currentPageNumber = 1;

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
                $productId = $product->getId();
                $productData = explode(self::EMARSYS_DELIMITER, $product->getParams());
                foreach ($productData as $param) {
                    $item = unserialize($param);
                    $map = $this->prepareHeader(
                        $item['store'],
                        $item['header'],
                        $item['default_store'],
                        $item['currency_code']
                    );

                    if (!isset($this->_preparedData[$productId])) {
                        $this->_preparedData[$productId] = array_fill(0, count($this->_mapHeader), "");
                    } else {
                        $processed = $this->_preparedData[$productId];
                        $this->_preparedData[$productId] = array_fill(0, count($this->_mapHeader), "");
                        $this->_preparedData[$productId] = $processed + $this->_preparedData[$productId];
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
                                    $value = $value * $rate;
                                }
                            }
                            $this->_preparedData[$productId][$map[$key]] = str_replace(array("\n", "\r"), '', $value);
                        }
                    }
                }
                ksort($this->_preparedData[$productId]);
            }

            $currentPageNumber++;
        }

        return $this->_preparedData;
    }

    /**
     * Prepare Global Header and Mapping
     *
     * @param string $storeCode
     * @param array $header
     * @param bool $isDefault
     * @param string $currencyCode
     * @return mixed
     */
    public function prepareHeader($storeCode, $header, $isDefault = false, $currencyCode)
    {
        if (!array_key_exists($storeCode, $this->_processedStores)) {
            // $this->_processedStores[$storeCode] = array(oldKey => newKey);
            $this->_processedStores[$storeCode] = array();
            foreach ($header as $key => &$value) {
                if (strtolower($value) == 'item') {
                    unset($header[$key]);
                    $this->_processedStores[$storeCode][$key] = 0;
                    continue;
                }

                if (!$isDefault) {
                    if (strtolower($value) == 'price' || strtolower($value) == 'msrp') {
                        $newValue = $value . '_' . $currencyCode;
                        $existingKey = array_search($newValue, $this->_mapHeader);
                        if ($existingKey) {
                            unset($header[$key]);
                            $this->_processedStores[$storeCode][$key] = $existingKey;
                            continue;
                        } else {
                            $value = $newValue;
                        }
                    } else {
                        $value = $value . '_' . $storeCode;
                    }
                }
            }
            $headers = array_flip($header);

            foreach ($headers as $head => $key) {
                $this->_mapHeader[] = $head;
                $renewedHead = array_keys($this->_mapHeader);
                $lastElementKey = array_pop($renewedHead);
                $this->_processedStores[$storeCode][$key] = $lastElementKey;
            }
        }

        return $this->_processedStores[$storeCode];
    }
}
