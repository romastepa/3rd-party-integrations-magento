<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Emarsys\Emarsys\Helper\Logs as EmarsysHelperLogs;
use Emarsys\Emarsys\Model\ResourceModel\Customer as EmarsysResourceModelCustomer;
use Emarsys\Emarsys\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Eav\Model\Config as EavConfig;
use Emarsys\Emarsys\Helper\Data as EmarsysDataHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Csv;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as TypeConfigurable;
use Magento\Bundle\Model\Product\Type as TypeBundle;
use Magento\GroupedProduct\Model\Product\Type\Grouped as TypeGrouped;

use Emarsys\Emarsys\Model\Emarsysproductexport as ProductExportModel;
use Emarsys\Emarsys\Model\ResourceModel\Emarsysproductexport as ProductExportResourceModel;

use Magento\Framework\Model\AbstractModel;

/**
 * Class Product
 * @package Emarsys\Emarsys\Model
 */
class Product extends AbstractModel
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var MessageManagerInterface
     */
    protected $messageManager;

    /**
     * @var Customer
     */
    protected $customerResourceModel;

    /**
     * @var ProductResourceModel
     */
    protected $productResourceModel;

    /**
     * @var ProductExportModel
     */
    protected $productExportModel;

    /**
     * @var ProductExportResourceModel
     */
    protected $productExportResourceModel;

    /**
     * @var ProductModel
     */
    protected $productModel;

    /**
     * @var Logs
     */
    protected $logsHelper;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var EavConfig
     */
    protected $eavConfig;

    /**
     * @var EmarsysDataHelper
     */
    protected $emarsysHelper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Csv
     */
    protected $csvWriter;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var ApiExport
     */
    protected $apiExport;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var Emulation
     */
    protected $appEmulation;

    /**
     * @var TypeConfigurable
     */
    protected $typeConfigurable;

    /**
     * @var TypeBundle
     */
    protected $typeBundle;

    /**
     * @var TypeGrouped
     */
    protected $typeGrouped;

    protected $_errorCount = false;
    protected $_mode = false;
    protected $_credentials = [];
    protected $_websites = [];
    protected $_attributeCache = [];
    protected $_parentProducts = [];
    protected $_productTypeInstance = null;

    /**
     * Product constructor.
     * @param Context $context
     * @param Registry $registry
     * @param MessageManagerInterface $messageManager
     * @param ProductModel $productModel
     * @param DateTime $date
     * @param EmarsysHelperLogs $logsHelper
     * @param EmarsysResourceModelCustomer $customerResourceModel
     * @param ProductResourceModel $productResourceModel
     * @param ProductExportModel $productExportModel
     * @param ProductExportResourceModel $productExportResourceModel
     * @param CategoryFactory $categoryFactory
     * @param StoreManagerInterface $storeManager
     * @param EavConfig $eavConfig
     * @param EmarsysDataHelper $emarsysHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param Csv $csvWriter
     * @param DirectoryList $directoryList
     * @param ApiExport $apiExport
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param Image $imageHelper
     * @param Emulation $appEmulation
     * @param TypeConfigurable $typeConfigurable
     * @param TypeBundle $typeBundle
     * @param TypeGrouped $typeGrouped
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        MessageManagerInterface $messageManager,
        ProductModel $productModel,
        DateTime $date,
        EmarsysHelperLogs $logsHelper,
        EmarsysResourceModelCustomer $customerResourceModel,
        ProductResourceModel $productResourceModel,
        ProductExportModel $productExportModel,
        ProductExportResourceModel $productExportResourceModel,
        CategoryFactory $categoryFactory,
        StoreManagerInterface $storeManager,
        EavConfig $eavConfig,
        EmarsysDataHelper $emarsysHelper,
        ScopeConfigInterface $scopeConfig,
        Csv $csvWriter,
        DirectoryList $directoryList,
        ApiExport $apiExport,
        Image $imageHelper,
        Emulation $appEmulation,
        TypeConfigurable $typeConfigurable,
        TypeBundle $typeBundle,
        TypeGrouped $typeGrouped,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->customerResourceModel = $customerResourceModel;
        $this->productResourceModel = $productResourceModel;
        $this->productExportModel = $productExportModel;
        $this->productExportResourceModel = $productExportResourceModel;
        $this->productModel = $productModel;
        $this->logsHelper = $logsHelper;
        $this->date = $date;
        $this->categoryFactory = $categoryFactory;
        $this->eavConfig =  $eavConfig;
        $this->emarsysHelper =  $emarsysHelper;
        $this->scopeConfig = $scopeConfig;
        $this->csvWriter = $csvWriter;
        $this->directoryList = $directoryList;
        $this->apiExport = $apiExport;
        $this->imageHelper = $imageHelper;
        $this->appEmulation = $appEmulation;
        $this->typeConfigurable = $typeConfigurable;
        $this->typeBundle = $typeBundle;
        $this->typeGrouped = $typeGrouped;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('Emarsys\Emarsys\Model\ResourceModel\Product');
    }

    /**
     * @param $storeId
     * @param $mappedAttributes
     * @param $filePath
     * @param $includeBundle
     * @param $excludedCategories
     */
    public function generateProductCsv($storeId, $mappedAttributes, $filePath, $includeBundle, $excludedCategories)
    {
        $store = $this->storeManager->getStore($storeId);
        $websiteId = $store->getWebsiteId();
        $scope = ScopeInterface::SCOPE_WEBSITES;

        $emarsysFieldNames = [];
        $magentoAttributeNames = [];

        foreach ($mappedAttributes as $mapAttribute) {
            $emarsysFieldId = $mapAttribute['emarsys_attr_code'];
            $emarsysFieldNames[] = $this->productResourceModel->getEmarsysFieldName($storeId, $emarsysFieldId);
            $magentoAttributeNames[] = $mapAttribute['magento_attr_code'];
        }

        $productCollection = $this->productCollectionFactory->create()->getCollection()
            ->addStoreFilter($storeId)
            ->addWebsiteFilter($websiteId)
            ->addAttributeToFilter('visibility', ["neq" => 1]);

        if (is_null($includeBundle)) {
            $includeBundle = $this->scopeConfig->getValue(
                EmarsysDataHelper::XPATH_PREDICT_INCLUDE_BUNDLE_PRODUCT,
                $scope,
                $websiteId
            );
        }
        if (is_null($excludedCategories)) {
            $excludedCategories = $this->scopeConfig->getValue(
                EmarsysDataHelper::XPATH_PREDICT_EXCLUDED_CATEGORIES,
                $scope,
                $websiteId
            );
        }
        $excludeCategories = explode(',', $excludedCategories);

        $productAttributes = [];
        foreach ($productCollection as $product) {
            $excludeCatFlag = 0;
            $productData = $this->productCollectionFactory->create()->setStoreId($storeId)->load($product['entity_id']);
            $productType = $productData->getTypeId();
            $catIds = $productData->getCategoryIds();
            $categoryNames = [];
            foreach ($catIds as $catId) {
                if (in_array($catId, $excludeCategories)) {
                    $excludeCatFlag = 1;
                    break;
                }
                $cateData = $this->categoryFactory->create()->load($catId);
                $categoryPath = $cateData->getPath();
                $categoryPathIds = explode('/', $categoryPath);
                $childCats = [];
                if (count($categoryPathIds) > 2) {
                    $pathIndex = 0;
                    foreach ($categoryPathIds as $categoryPathId) {
                        if ($pathIndex <= 1) {
                            $pathIndex++;
                            continue;
                        }
                        $childCateData = $this->categoryFactory->create()->load($categoryPathId);
                        $childCats[] = $childCateData->getName();
                    }
                    $categoryNames[] = implode(" > ", $childCats);
                }
            }
            if (($includeBundle == 0 && $productType == 'bundle') || $excludeCatFlag == 1) {
                continue;
            }

            $attributeData = [];
            foreach ($magentoAttributeNames as $attributeName) {
                $attributeOption = $productData->getData($attributeName);
                if (!is_array($attributeOption)) {
                    $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeName);
                    if ($attribute->getFrontendInput() == 'boolean' || $attribute->getFrontendInput() == 'select'  || $attribute->getFrontendInput() == 'multiselect' ) {
                        $attributeOption = $productData->getAttributeText($attributeName);
                    }
                }
                if (isset($attributeOption) && $attributeOption != '') {
                    if (is_array($attributeOption)) {
                        if ($attributeName == 'category_ids') {
                            $attributeData[] = implode('|', $categoryNames);
                        } elseif ($attributeName == 'quantity_and_stock_status') {
                            if ($productData->getData('quantity_and_stock_status')['is_in_stock'] == 1) {
                                $attributeData[] =  'TRUE';
                            } else {
                                $attributeData[] = 'FALSE';
                            }
                        } else {
                            $attributeData[] = implode(',', $attributeOption);
                        }
                    } else {
                        if ($attributeName == 'image') {
                            $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                            $imgUrl = $mediaUrl . 'catalog/product' . $attributeOption;
                            $attributeData[] = str_replace('pub/', '', $imgUrl);
                        } elseif ($attributeName == 'url_key') {
                            $attributeData[] = $productData->getProductUrl();
                        } else {
                            $attributeData[] = $attributeOption;
                        }
                    }
                } else {
                    if ($attributeName == 'url_key') {
                        $attributeData[] = $productData->getProductUrl();
                    } else {
                        $attributeData[] = '';
                    }
                }
            }
            $productAttributes[] = $attributeData;
        }

        //Added Header
        array_unshift($productAttributes, $emarsysFieldNames);

        //Write catalog data into CSV
        $this->csvWriter
            ->setEnclosure('"')
            ->setDelimiter(',')
            ->saveData($filePath, $productAttributes);

        return;
    }

    /**
     * @param $suffix
     * @return string
     */
    public function getCatalogProductCsvFileName($suffix)
    {
        return "products_" . $this->date->date('YmdHis', time()) . "_" . $suffix . ".csv";
    }


    public function consolidatedCatalogExport($mode = EmarsysDataHelper::ENTITY_EXPORT_MODE_AUTOMATIC, $includeBundle = null, $excludedCategories = null)
    {
        set_time_limit(0);

        $logsArray['job_code'] = 'product';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = __('Bulk product export started');
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = $mode;
        $logsArray['auto_log'] = 'Complete';
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['store_id'] = 0;
        $logId = $this->logsHelper->manualLogs($logsArray, 1);
        $logsArray['id'] = $logId;
        $logsArray['log_action'] = 'sync';
        $logsArray['action'] = 'synced to emarsys';

        try {
            $this->_errorCount = false;
            $this->_mode = $mode;

            $allStores = $this->storeManager->getStores();

            /** @var \Magento\Store\Model\Store $store */
            foreach ($allStores as $store) {
                $this->setCredentials($store, $logId);
            }

            foreach ($this->getCredentials() as $websiteId => $website) {
                $emarsysFieldNames = array();
                $magentoAttributeNames = array();

                foreach ($website as $storeId => $store) {
                    foreach ($store['mapped_attributes_names'] as $mapAttribute) {
                        $emarsysFieldId = $mapAttribute['emarsys_attr_code'];
                        $emarsysFieldNames[$storeId][] = $this->productResourceModel->getEmarsysFieldName($storeId, $emarsysFieldId);
                        $magentoAttributeNames[$storeId][] = $mapAttribute['magento_attr_code'];
                    }
                }

                $this->productExportResourceModel->truncateTable();

                $defaultStoreID = false;

                foreach ($website as $storeId => $store) {
                    $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
                    $currencyStoreCode = $store['store']->getDefaultCurrencyCode();
                    if (!$defaultStoreID) {
                        $defaultStoreID = $store['store']->getWebsite()->getDefaultStore()->getId();
                    }
                    $currentPageNumber = 1;
                    $collection = $this->productExportModel->getCatalogExportProductCollection(
                        $storeId,
                        $currentPageNumber,
                        $magentoAttributeNames[$storeId],
                        $includeBundle,
                        $excludedCategories
                    );

                    $lastPageNumber = $collection->getLastPageNumber();
                    $header = $emarsysFieldNames[$storeId];

                    while ($currentPageNumber <= $lastPageNumber) {
                        if ($currentPageNumber != 1) {
                            $collection = $this->productExportModel->getCatalogExportProductCollection(
                                $storeId,
                                $currentPageNumber,
                                $magentoAttributeNames[$storeId],
                                $includeBundle,
                                $excludedCategories
                            );
                        }
                        $products = array();
                        foreach ($collection as $product) {
                            $catIds = $product->getCategoryIds();
                            $categoryNames = $this->getCategoryNames($catIds, $storeId);
                            $product->setStoreId($storeId);
                            $products[$product->getId()] = array(
                                'entity_id' => $product->getId(),
                                'params' => serialize(array(
                                    'default_store' => ($storeId == $defaultStoreID) ? $storeId : 0,
                                    'store' => $store['store']->getCode(),
                                    'store_id' => $store['store']->getId(),
                                    'data' => $this->_getProductData($magentoAttributeNames[$storeId], $product, $categoryNames, $store['store'], $collection),
                                    'header' => $header,
                                    'currency_code' => $currencyStoreCode,
                                ))
                            );
                        }

                        if (!empty($products)) {
                            $this->productExportResourceModel->saveBulkProducts($products);
                        }
                        $currentPageNumber++;
                    }
                    $this->appEmulation->stopEnvironmentEmulation();
                }

                if (!empty($store)) {
                    list($csvFilePath, $outputFile) = $this->productExportModel->saveToCsv($websiteId);
                    $bulkDir = $this->customerResourceModel->getDataFromCoreConfig(
                        EmarsysDataHelper::XPATH_EMARSYS_FTP_BULK_EXPORT_DIR, 
                        ScopeInterface::SCOPE_WEBSITES, 
                        $websiteId
                    );
                    $outputFile = $bulkDir . $outputFile;
                    $this->moveFile($store['store'], $outputFile, $csvFilePath, $logId, $mode);
                }
            }

            $logsArray['id'] = $logId;
            if ($this->_errorCount) {
                $logsArray['status'] = 'error';
                $logsArray['messages'] = __('Product export have an error. Please check.');
            } else {
                $logsArray['status'] = 'success';
                $logsArray['messages'] = __('Product export completed');
            }
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $this->logsHelper->manualLogsUpdate($logsArray);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = __('consolidatedCatalogExport Exception');
            $logsArray['description'] = __("Exception " . $msg);
            $logsArray['message_type'] = 'Error';
            $this->logsHelper->logs($logsArray);
            if ($mode == EmarsysDataHelper::ENTITY_EXPORT_MODE_MANUAL) {
                $this->messageManager->addErrorMessage(
                    __("Exception " . $msg)
                );
            }
        }
    }

    /**
     * @param \Magento\Store\Model\Store $store
     * @param string $outputFile
     * @param string $csvFilePath
     * @param int $logId
     * @param string $mode
     */
    public function moveFile($store, $outputFile, $csvFilePath, $logId, $mode)
    {
        $apiExportEnabled = $store->getConfig(EmarsysDataHelper::XPATH_PREDICT_API_ENABLED);

        if ($apiExportEnabled) {
            $merchantId = $store->getConfig(EmarsysDataHelper::XPATH_PREDICT_MERCHANT_ID);
            //get token from admin configuration
            $token = $store->getConfig(EmarsysDataHelper::XPATH_PREDICT_TOKEN);

            //Assign API Credentials
            $this->apiExport->assignApiCredentials($merchantId, $token);

            //Get catalog API Url
            $apiUrl = $this->apiExport->getApiUrl(\Magento\Catalog\Model\Product::ENTITY);

            //Export CSV to API
            $apiExportResult = $this->apiExport->apiExport($apiUrl, $csvFilePath);

            if ($apiExportResult['result'] == 1) {
                //successfully uploaded file on Emarsys
                $logsArray['id'] = $logId;
                $logsArray['job_code'] = 'product';
                $logsArray['emarsys_info'] = __('File uploaded to Emarsys');
                $logsArray['description'] = __('File uploaded to Emarsys. File Name: %1. API Export result: %2', $csvFilePath, $apiExportResult['resultBody']);
                $logsArray['message_type'] = 'Success';
                $this->logsHelper->logs($logsArray);
                $this->_errorCount = false;
                if ($mode == EmarsysDataHelper::ENTITY_EXPORT_MODE_MANUAL) {
                    $this->messageManager->addSuccessMessage(
                        __("File uploaded to Emarsys successfully !!!")
                    );
                }
            } else {
                //Failed to export file on Emarsys
                $this->_errorCount = true;
                $msg = $apiExportResult['resultBody'];
                $logsArray['id'] = $logId;
                $logsArray['job_code'] = 'product';
                $logsArray['emarsys_info'] = __('Failed to upload file on Emarsys');
                $logsArray['description'] = __('Failed to upload file on Emarsys. %1' , $msg);
                $logsArray['message_type'] = 'Error';
                $this->logsHelper->logs($logsArray);
                if ($mode == EmarsysDataHelper::ENTITY_EXPORT_MODE_MANUAL) {
                    $this->messageManager->addErrorMessage(
                        __("Failed to upload file on Emarsys !!! " . $msg)
                    );
                }
            }
        } else {
            if ($this->emarsysHelper->moveFileToFtp($store, $csvFilePath, $outputFile)) {
                //successfully uploaded the file on ftp
                $this->_errorCount = false;
                $logsArray['id'] = $logId;
                $logsArray['job_code'] = 'product';
                $logsArray['emarsys_info'] = __('File uploaded to FTP server successfully');
                $logsArray['description'] = $outputFile;
                $logsArray['message_type'] = 'Success';
                $this->logsHelper->logs($logsArray);
                if ($mode == EmarsysDataHelper::ENTITY_EXPORT_MODE_MANUAL) {
                    $this->messageManager->addSuccessMessage(
                        __("File uploaded to FTP server successfully !!!")
                    );
                }
            } else {
                //failed to upload file on FTP server
                $this->_errorCount = true;
                $errorMessage = error_get_last();
                $msg = isset($errorMessage['message']) ? $errorMessage['message'] : '';
                $logsArray['id'] = $logId;
                $logsArray['job_code'] = 'product';
                $logsArray['emarsys_info'] = __('Failed to upload file on FTP server');
                $logsArray['description'] = __('Failed to upload file on FTP server %1' , $msg);
                $logsArray['message_type'] = 'Error';
                $this->logsHelper->logs($logsArray);
                if ($mode == EmarsysDataHelper::ENTITY_EXPORT_MODE_MANUAL) {
                    $this->messageManager->addErrorMessage(
                        __("Failed to upload file on FTP server !!! " . $msg)
                    );
                }
            }
        }
        if (file_exists($csvFilePath)) {
            unlink($csvFilePath);
        }
    }

    /**
     * Get Store Credentials
     *
     * @param null|int $websiteId
     * @param null|int $storeId
     * @return array|mixed
     */
    public function getCredentials($websiteId = null, $storeId = null)
    {
        $return = $this->_credentials;
        if (!is_null($storeId) && !is_null($websiteId)) {
            $return = null;
            if (isset($this->_credentials[$storeId])) {
                $return = $this->_credentials[$storeId];
            }
        }
        return $return;
    }

    /**
     * Set Store Credential
     *
     * @param \Magento\Store\Model\Store $store
     * @param int $logId
     */
    public function setCredentials($store, $logId)
    {
        $storeId = $store->getId();
        $websiteId = $this->getWebsiteId($store);
        if (!isset($this->_credentials[$websiteId][$storeId])) {
            if ($store->getConfig(EmarsysDataHelper::XPATH_EMARSYS_ENABLED)) {
                //check feed export enabled for the website
                if ($store->getConfig(EmarsysDataHelper::XPATH_PREDICT_ENABLE_NIGHTLY_PRODUCT_FEED)) {

                    //get method of catalog export from admin configuration
                    $apiExportEnabled = $store->getConfig(EmarsysDataHelper::XPATH_PREDICT_API_ENABLED);

                    if ($apiExportEnabled) {
                        $merchantId = $store->getConfig(EmarsysDataHelper::XPATH_PREDICT_MERCHANT_ID);
                        $token = $store->getConfig(EmarsysDataHelper::XPATH_PREDICT_TOKEN);
                        if ($merchantId == '' || $token == '') {
                            $this->_errorCount = true;
                            $logsArray['id'] = $logId;
                            $logsArray['job_code'] = 'product';
                            $logsArray['emarsys_info'] = __('Invalid API credentials');
                            $logsArray['description'] = __('Invalid API credential. Please check your settings and try again');
                            $logsArray['message_type'] = 'Error';
                            $this->logsHelper->logs($logsArray);
                            if ($this->_mode == EmarsysDataHelper::ENTITY_EXPORT_MODE_MANUAL) {
                                $this->messageManager->addErrorMessage(
                                    __('Invalid API credential. Please check your settings and try again !!!')
                                );
                            }
                            return;
                        }
                    } else {
                        $checkFtpConnection = $this->emarsysHelper->checkFtpConnectionByStore($store);
                        if (!$checkFtpConnection) {
                            $this->_errorCount = true;
                            $logsArray['id'] = $logId;
                            $logsArray['job_code'] = 'product';
                            $logsArray['emarsys_info'] = __('Failed to connect with FTP server.');
                            $logsArray['description'] = __('Failed to connect with FTP server.');
                            $logsArray['message_type'] = 'Error';
                            $this->logsHelper->logs($logsArray);
                            if ($this->_mode == EmarsysDataHelper::ENTITY_EXPORT_MODE_MANUAL) {
                                $this->messageManager->addErrorMessage(
                                    __("Failed to connect with FTP server. Please check your settings and try again !!!")
                                );
                            }
                            return;
                        }
                    }

                    $mappedAttributes = $this->productResourceModel->getMappedProductAttribute($storeId);
                    $mappingField = 0;
                    foreach ($mappedAttributes as $mapAttribute) {
                        $emarsysFieldId = $mapAttribute['emarsys_attr_code'];
                        if ($emarsysFieldId != 0) {
                            $mappingField = 1;
                        }
                    }
                    if ($mappingField) {
                        $this->_credentials[$websiteId][$storeId]['store'] = $store;
                        $this->_credentials[$websiteId][$storeId]['mapped_attributes_names'] = $mappedAttributes;
                    }
                } else {
                    $this->_errorCount = true;
                    $logsArray['id'] = $logId;
                    $logsArray['job_code'] = 'product';
                    $logsArray['emarsys_info'] = __('Catalog Feed Export is Disabled');
                    $logsArray['description'] = __('Catalog Feed Export is Disabled for the store %1.', $store->getName());
                    $logsArray['message_type'] = 'Error';
                    $this->logsHelper->logs($logsArray);
                }
            } else {
                $this->_errorCount = true;
                $logsArray['id'] = $logId;
                $logsArray['job_code'] = 'product';
                $logsArray['emarsys_info'] = __('Emarsys is disabled');
                $logsArray['description'] = __('Emarsys is disabled for the website %1', $websiteId);
                $logsArray['message_type'] = 'Error';
                $this->logsHelper->logs($logsArray);
            }
        }
    }

    /**
     * Get Grouped WebsiteId
     *
     * @param \Magento\Store\Model\Store $store
     * @return int
     */
    public function getWebsiteId($store)
    {
        $apiUserName = $store->getConfig(EmarsysDataHelper::XPATH_EMARSYS_API_USER);
        if (!isset($this->_websites[$apiUserName])) {
            $this->_websites[$apiUserName] = $store->getWebsiteId();
        }

        return $this->_websites[$apiUserName];
    }

    /**
     * Get Category Names
     *
     * @param $catIds
     * @param $storeId
     * @return array
     */
    public function getCategoryNames($catIds, $storeId)
    {
        $categoryNames = [];
        foreach ($catIds as $catId) {
            $cateData = $this->categoryFactory->create()
                ->setStoreId($storeId)
                ->load($catId);
            $categoryPath = $cateData->getPath();
            $categoryPathIds = explode('/', $categoryPath);
            $childCats = [];
            if (count($categoryPathIds) > 2) {
                $pathIndex = 0;
                foreach ($categoryPathIds as $categoryPathId) {
                    if ($pathIndex <= 1) {
                        $pathIndex++;
                        continue;
                    }
                    $childCateData = $this->categoryFactory->create()
                        ->setStoreId($storeId)
                        ->load($categoryPathId);
                    $childCats[] = $childCateData->getName();
                }
                $categoryNames[] = implode(" > ", $childCats);
            }
        }

        return $categoryNames;
    }

    /**
     * @param $magentoAttributeNames
     * @param \Magento\Catalog\Model\Product $productObject
     * @param $categoryNames
     * @param \Magento\Store\Model\Store $store
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getProductData($magentoAttributeNames, $productObject, $categoryNames, $store, $collection)
    {
        $attributeData = [];
        foreach ($magentoAttributeNames as $attributeCode) {
            $attributeOption = $productObject->getData($attributeCode);
            if (!is_array($attributeOption)) {
                $attribute = $this->getEavAttribute($attributeCode);
                if ($attribute->getFrontendInput() == 'boolean'
                    || $attribute->getFrontendInput() == 'select'
                    || $attribute->getFrontendInput() == 'multiselect'
                ) {
                    $attributeOption = $productObject->getAttributeText($attributeCode);
                }
            }
            switch ($attributeCode) {
                case 'quantity_and_stock_status':
                    $status = ($productObject->getStatus() == Status::STATUS_ENABLED);
                    $inStock = ($productObject->getData('inventory_in_stock') == 1);
                    $visibility = ($productObject->getVisibility() != Visibility::VISIBILITY_NOT_VISIBLE);

                    if ($status && $inStock && $visibility) {
                        $attributeData[] = 'TRUE';
                    } else {
                        $attributeData[] = 'FALSE';
                    }
                    break;
                case 'category_ids':
                    $attributeData[] = implode('|', $categoryNames);
                    break;
                case is_array($attributeOption):
                    $attributeData[] = implode(',', $attributeOption);
                    break;
                case 'image':
                    /** @var \Magento\Catalog\Helper\Image $helper */
                    $url = $this->imageHelper
                        ->init($productObject, 'product_base_image')
                        ->setImageFile($attributeOption)
                        ->getUrl();
                    $attributeData[] = $url;
                    break;
                case 'url_key':
                    $url = $productObject->getProductUrl();
                    if ($productObject->getVisibility() == Visibility::VISIBILITY_NOT_VISIBLE) {
                        $parentProducts = $this->typeConfigurable->getParentIdsByChild($productObject->getId());
                        $this->_productTypeInstance = $this->typeConfigurable;
                        if (empty($parentProducts)) {
                            $parentProducts = $this->typeBundle->getParentIdsByChild($productObject->getId());
                            $this->_productTypeInstance = $this->typeBundle;
                            if (empty($parentProducts)) {
                                $parentProducts = $this->typeGrouped->getParentIdsByChild($productObject->getId());
                                $this->_productTypeInstance = $this->typeGrouped;
                            }
                        }
                        if (!empty($parentProducts)) {
                            $parentProductId = current($parentProducts);
                            $parentProduct = $collection->getItemById($parentProductId);
                            if (!$parentProduct) {
                                if (!isset($this->_parentProducts[$parentProductId])) {
                                    $this->productModel->setTypeInstance($this->_productTypeInstance);
                                    $this->_parentProducts[$parentProductId] = $this->productModel->load($parentProductId);
                                    $parentProduct = $this->_parentProducts[$parentProductId];
                                } else {
                                    $parentProduct = $this->_parentProducts[$parentProductId];
                                }
                            }
                            if ($parentProduct) {
                                $parentProduct->setStoreId($store->getId());
                                $url = $parentProduct->getProductUrl();
                            }
                        }
                    }
                    $attributeData[] = $url;
                    break;
                case 'price':
                    $price = $attributeOption;
                    if (!$attributeOption && $productObject->getMinimalPrice()) {
                        $price = $productObject->getMinimalPrice();
                    }
                    $attributeData[] = number_format($price, 2, '.', '');
                    break;
                default:
                    $attributeData[] = $attributeOption;
                    break;

            }
        }

        return $attributeData;
    }

    /**
     * @param $attributeCode
     * @return  AbstractAttribute
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getEavAttribute($attributeCode)
    {
        if (!isset($this->_attributeCache[$attributeCode])) {
            $this->_attributeCache[$attributeCode] = $this->eavConfig->getAttribute('catalog_product', $attributeCode);
        }
        return $this->_attributeCache[$attributeCode];
    }
}
