<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model;

use Magento\{
    Eav\Model\Config as EavConfig,
    Framework\App\Area,
    Framework\Model\AbstractModel,
    Framework\Model\Context,
    Framework\Model\ResourceModel\AbstractResource,
    Framework\Registry,
    Framework\Message\ManagerInterface as MessageManagerInterface,
    Framework\Data\Collection\AbstractDb,
    Framework\Stdlib\DateTime\DateTime,
    Framework\App\Filesystem\DirectoryList,
    Framework\File\Csv,
    Catalog\Helper\Image,
    Catalog\Model\Product\Attribute\Source\Status,
    Catalog\Model\Product\Visibility,
    Catalog\Model\CategoryFactory,
    Catalog\Model\Product as ProductModel,
    Store\Model\App\Emulation,
    Store\Model\StoreManagerInterface,
    ConfigurableProduct\Model\Product\Type\Configurable as TypeConfigurable,
    Bundle\Model\Product\Type as TypeBundle,
    GroupedProduct\Model\Product\Type\Grouped as TypeGrouped
};

use Emarsys\Emarsys\{
    Helper\Logs as EmarsysHelperLogs,
    Helper\Data as EmarsysHelper,
    Model\ResourceModel\Customer as EmarsysResourceModelCustomer,
    Model\ResourceModel\Product as ProductResourceModel,
    Model\ResourceModel\Emarsysproductexport as ProductExportResourceModel,
    Model\Emarsysproductexport as ProductExportModel
};

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
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

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

    protected $_errorCount = false;
    protected $_mode = false;
    protected $_credentials = [];
    protected $_websites = [];
    protected $_attributeCache = [];
    protected $_categoryNames = [];
    protected $_mapHeader = ['item'];
    protected $_processedStores = [];
    protected $_parentProducts = [];
    protected $_productTypeInstance = null;

    /**
     * @var State
     */
    protected $state;
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

    /**
     * Product constructor.
     *
     * @param MessageManagerInterface $messageManager
     * @param ProductModel $productModel
     * @param DateTime $date
     * @param EmarsysHelperLogs $logsHelper
     * @param EmarsysResourceModelCustomer $customerResourceModel
     * @param ProductResourceModel $productResourceModel
     * @param Emarsysproductexport $productExportModel
     * @param ProductExportResourceModel $productExportResourceModel
     * @param CategoryFactory $categoryFactory
     * @param StoreManagerInterface $storeManager
     * @param EavConfig $eavConfig
     * @param EmarsysHelper $emarsysHelper
     * @param Csv $csvWriter
     * @param DirectoryList $directoryList
     * @param ApiExport $apiExport
     * @param Image $imageHelper
     * @param Emulation $appEmulation
     * @param TypeConfigurable $typeConfigurable
     * @param TypeBundle $typeBundle
     * @param TypeGrouped $typeGrouped
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
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
        EmarsysHelper $emarsysHelper,
        Csv $csvWriter,
        DirectoryList $directoryList,
        ApiExport $apiExport,
        Image $imageHelper,
        Emulation $appEmulation,
        TypeConfigurable $typeConfigurable,
        TypeBundle $typeBundle,
        TypeGrouped $typeGrouped,
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->messageManager = $messageManager;
        $this->productModel = $productModel;
        $this->date = $date;
        $this->logsHelper = $logsHelper;
        $this->customerResourceModel = $customerResourceModel;
        $this->productResourceModel = $productResourceModel;
        $this->productExportModel = $productExportModel;
        $this->productExportResourceModel = $productExportResourceModel;
        $this->categoryFactory = $categoryFactory;
        $this->storeManager = $storeManager;
        $this->eavConfig =  $eavConfig;
        $this->emarsysHelper =  $emarsysHelper;
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
     * @param string $mode
     * @param null $includeBundle
     * @param null $excludedCategories
     * @return bool
     * @throws \Exception
     */
    public function consolidatedCatalogExport($mode = EmarsysHelper::ENTITY_EXPORT_MODE_AUTOMATIC, $includeBundle = null)
    {
        set_time_limit(0);

        $result = false;

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
                $this->setCredentials($store, $logsArray);
            }

            foreach ($this->getCredentials() as $websiteId => $website) {
                $emarsysFieldNames = $magentoAttributeNames = [];

                foreach ($website as $storeId => $store) {
                    foreach ($store['mapped_attributes_names'] as $mapAttribute) {
                        $emarsysFieldId = $mapAttribute['emarsys_attr_code'];
                        $emarsysFieldNames[$storeId][] = $this->productResourceModel->getEmarsysFieldName($storeId, $emarsysFieldId);
                        $magentoAttributeNames[$storeId][] = $mapAttribute['magento_attr_code'];
                    }
                }

                $this->productExportResourceModel->truncateTable();

                $defaultStoreID = false;
                $this->_mapHeader = ['item'];
                $this->_processedStores = [];
                foreach ($website as $storeId => $store) {
                    $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
                    $currencyStoreCode = $store['store']->getDefaultCurrencyCode();
                    if (!$defaultStoreID) {
                        $defaultStoreID = $store['store']->getWebsite()->getDefaultStore()->getId();
                    }

                    $excludedCategories = $store['store']->getConfig(EmarsysHelper::XPATH_PREDICT_EXCLUDED_CATEGORIES);

                    if (!empty($excludedCategories)) {
                        $excludedCategories = explode(',', str_replace(' ', '', $excludedCategories));
                    }

                    if (empty($excludedCategories)) {
                        $excludedCategories = [];
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
                    $headerOld = $emarsysFieldNames[$storeId];
                    $header = [];
                    foreach ($headerOld as $el) {
                        $header[] = $el;
                        if ($el == 'available') {
                            $header[] = 'available_rec';
                        }
                    }
                    $this->_categoryNames = [];
                    $this->_parentProducts = [];

                    $this->prepareHeader(
                        $store['store']->getCode(),
                        $header,
                        ($storeId == $defaultStoreID) ? $storeId : 0,
                        $currencyStoreCode
                    );

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
                        $logsArray['emarsys_info'] = __('Processing data for store %1', $storeId);
                        $logsArray['description'] = __('%1 of %2', $currentPageNumber, $lastPageNumber);
                        $logsArray['message_type'] = 'Success';
                        $this->logsHelper->logs($logsArray);

                        $products = [];
                        foreach ($collection as $product) {
                            $catIds = $product->getCategoryIds();
                            $categoryNames = $this->getCategoryNames($catIds, $storeId, $excludedCategories);
                            $product->setStoreId($storeId);
                            $products[$product->getId()] = [
                                'entity_id' => $product->getId(),
                                'params' => serialize([
                                    'default_store' => ($storeId == $defaultStoreID) ? $storeId : 0,
                                    'store' => $store['store']->getCode(),
                                    'store_id' => $store['store']->getId(),
                                    'data' => $this->_getProductData($magentoAttributeNames[$storeId], $product, $categoryNames, $store['store'], $collection, $logsArray),
                                    'header' => $header,
                                    'currency_code' => $currencyStoreCode,
                                ])];
                        }

                        if (!empty($products)) {
                            $this->productExportResourceModel->saveBulkProducts($products);
                        }
                        $currentPageNumber++;
                    }
                    $logsArray['emarsys_info'] = __('Data for store %1 prepared', $storeId);
                    $logsArray['description'] = __('Data for store %1 prepared', $storeId);
                    $logsArray['message_type'] = 'Success';
                    $this->logsHelper->logs($logsArray);
                    $this->appEmulation->stopEnvironmentEmulation();
                }

                if (!empty($store)) {
                    $logsArray['emarsys_info'] = __('Starting data uploading');
                    $logsArray['description'] = __('Starting data uploading');
                    $logsArray['message_type'] = 'Success';
                    $this->logsHelper->logs($logsArray);

                    $csvFilePath = $this->productExportModel->saveToCsv(
                        $websiteId,
                        $this->_mapHeader,
                        $this->_processedStores,
                        $logsArray
                    );

                    $uploaded = $this->moveFile($store['store'], $csvFilePath, $logsArray, $mode);
                    if ($uploaded) {
                        $logsArray['emarsys_info'] = __('Data for was uploaded');
                        $logsArray['description'] = __('Data for was uploaded');
                        $logsArray['message_type'] = 'Success';
                    } else {
                        $logsArray['emarsys_info'] = __('Error during data uploading');
                        $logsArray['description'] = __('Error during data uploading');
                        $logsArray['message_type'] = 'Error';
                    }
                    $this->logsHelper->logs($logsArray);
                }
            }

            if ($this->_errorCount) {
                $logsArray['status'] = 'error';
                $logsArray['messages'] = __('Product export have an error. Please check.');
            } else {
                $logsArray['status'] = 'success';
                $logsArray['messages'] = __('Product export completed');
            }
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $this->logsHelper->manualLogsUpdate($logsArray);
            $result = true;
        } catch (\Exception $e) {
            $logsArray['messages'] = __('consolidatedCatalogExport Exception');
            $logsArray['status'] = 'error';
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $this->logsHelper->manualLogsUpdate($logsArray);

            $logsArray['emarsys_info'] = __('consolidatedCatalogExport Exception');
            $logsArray['description'] = __("Exception %1", $e->getMessage() . ' trace: ' . $e->getTraceAsString());
            $logsArray['message_type'] = 'Error';
            $this->logsHelper->logs($logsArray);
        }

        return $result;
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
            $this->_processedStores[$storeCode] = [];
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

    /**
     * @param \Magento\Store\Model\Store $store
     * @param string $csvFilePath
     * @param array $logsArray
     * @param string $mode
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Http_Client_Exception
     */
    public function moveFile($store, $csvFilePath, $logsArray, $mode)
    {
        $result = true;
        $apiExportEnabled = $store->getConfig(EmarsysHelper::XPATH_PREDICT_API_ENABLED);

        $isBig = (filesize($csvFilePath) / pow(1024, 2)) > 100;
        $merchantId = $store->getConfig(EmarsysHelper::XPATH_PREDICT_MERCHANT_ID);
        $websiteId = $store->getWebsiteId();
        $url = $this->emarsysHelper->getEmarsysMediaUrlPath(ProductModel::ENTITY . '/' . $websiteId, $csvFilePath);
        if ($apiExportEnabled && !$isBig) {
            //get token from admin configuration
            $token = $store->getConfig(EmarsysHelper::XPATH_PREDICT_TOKEN);

            //Assign API Credentials
            $this->apiExport->assignApiCredentials($merchantId, $token);

            //Get catalog API Url
            $apiUrl = $this->apiExport->getApiUrl(ProductModel::ENTITY);

            //Export CSV to API
            $apiExportResult = $this->apiExport->apiExport($apiUrl, $csvFilePath);
            if ($apiExportResult['result'] == 1) {
                //successfully uploaded file on Emarsys
                $logsArray['emarsys_info'] = __('File uploaded to Emarsys');
                $logsArray['description'] = __('File uploaded to Emarsys. File Name: %1. API Export result: %2', $url, $apiExportResult['resultBody']);
                $logsArray['message_type'] = 'Success';
                $this->logsHelper->logs($logsArray);
                $this->_errorCount = false;
                if ($mode == EmarsysHelper::ENTITY_EXPORT_MODE_MANUAL) {
                    $this->messageManager->addSuccessMessage(
                        __("File uploaded to Emarsys successfully !!!")
                    );
                }
            } else {
                //Failed to export file on Emarsys
                $this->_errorCount = true;
                $msg = isset($apiExportResult['resultBody']) ? $apiExportResult['resultBody'] : '';
                $logsArray['emarsys_info'] = __('Failed to upload file on Emarsys');
                $logsArray['description'] = __('Failed to upload %1 on Emarsys. %2' , $url, $msg);
                $logsArray['message_type'] = 'Error';
                $this->logsHelper->logs($logsArray);
                if ($mode == EmarsysHelper::ENTITY_EXPORT_MODE_MANUAL) {
                    $this->messageManager->addErrorMessage(
                        __("Failed to upload file on Emarsys !!! " . $msg)
                    );
                }
                $result = false;
            }
        } else {
            $bulkDir = $store->getConfig(EmarsysHelper::XPATH_EMARSYS_FTP_BULK_EXPORT_DIR);
            $outputFile = $bulkDir . 'products_' . $store->getWebsiteId() . '.csv';
            if ($this->emarsysHelper->moveFileToFtp($store, $csvFilePath, $outputFile)) {
                //successfully uploaded the file on ftp
                $this->_errorCount = false;
                $logsArray['emarsys_info'] = __('File uploaded to FTP server successfully');
                $logsArray['description'] = $url . ' > ' . $outputFile;
                $logsArray['message_type'] = 'Success';
                $this->logsHelper->logs($logsArray);
                if ($mode == EmarsysHelper::ENTITY_EXPORT_MODE_MANUAL) {
                    $this->messageManager->addSuccessMessage(
                        __("File uploaded to FTP server successfully !!!")
                    );
                }
            } else {
                //failed to upload file on FTP server
                $this->_errorCount = true;
                $errorMessage = error_get_last();
                $msg = isset($errorMessage['message']) ? $errorMessage['message'] : '';
                $logsArray['emarsys_info'] = __('Failed to upload file on FTP server');
                $logsArray['description'] = __('Failed to upload %1 on FTP server %2' , $url, $msg);
                $logsArray['message_type'] = 'Error';
                $this->logsHelper->logs($logsArray);
                if ($mode == EmarsysHelper::ENTITY_EXPORT_MODE_MANUAL) {
                    $this->messageManager->addErrorMessage(
                        __("Failed to upload file on FTP server !!! " . $msg)
                    );
                }
                $result = false;
            }
        }

        $this->emarsysHelper->removeFilesInFolder($this->emarsysHelper->getEmarsysMediaDirectoryPath(ProductModel::ENTITY . '/' . $websiteId));

        return $result;
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
            if (isset($this->_credentials[$websiteId][$storeId])) {
                $return = $this->_credentials[$websiteId][$storeId];
            }
        }
        return $return;
    }

    /**
     * Set Store Credential
     *
     * @param \Magento\Store\Model\Store $store
     * @param array $logsArray
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setCredentials($store, $logsArray)
    {
        $storeId = $store->getId();
        $websiteId = $this->getWebsiteId($store);
        if (!isset($this->_credentials[$websiteId][$storeId])) {
            if ($store->getConfig(EmarsysHelper::XPATH_EMARSYS_ENABLED)
                && $store->getConfig(EmarsysHelper::XPATH_PREDICT_ENABLE_NIGHTLY_PRODUCT_FEED)
            ) {
                //get method of catalog export from admin configuration
                $merchantId = $store->getConfig(EmarsysHelper::XPATH_PREDICT_MERCHANT_ID);
                if ($store->getConfig(EmarsysHelper::XPATH_PREDICT_API_ENABLED)) {
                    $token = $store->getConfig(EmarsysHelper::XPATH_PREDICT_TOKEN);
                    if ($merchantId == '' || $token == '') {
                        $this->_errorCount = true;
                        $logsArray['emarsys_info'] = __('Invalid API credentials');
                        $logsArray['description'] = __('Invalid API credential. Please check your settings and try again');
                        $logsArray['message_type'] = 'Error';
                        $this->logsHelper->logs($logsArray);
                        if ($this->_mode == EmarsysHelper::ENTITY_EXPORT_MODE_MANUAL) {
                            $this->messageManager->addErrorMessage(
                                __('Invalid API credential. Please check your settings and try again !!!')
                            );
                        }
                        return;
                    }
                    $logsArray['emarsys_info'] = __('Set API credentials');
                    $logsArray['description'] = __('Set API credentials for store %1', $storeId);
                    $logsArray['message_type'] = 'Success';
                    $this->logsHelper->logs($logsArray);
                } else {
                    if (!$this->emarsysHelper->checkFtpConnectionByStore($store)) {
                        $this->_errorCount = true;
                        $logsArray['emarsys_info'] = __('Failed to connect with FTP server.');
                        $logsArray['description'] = __('Failed to connect with FTP server.');
                        $logsArray['message_type'] = 'Error';
                        $this->logsHelper->logs($logsArray);
                        if ($this->_mode == EmarsysHelper::ENTITY_EXPORT_MODE_MANUAL) {
                            $this->messageManager->addErrorMessage(
                                __("Failed to connect with FTP server. Please check your settings and try again !!!")
                            );
                        }
                        return;
                    }
                    $logsArray['emarsys_info'] = __('Set FTP credentials');
                    $logsArray['description'] = __('Set FTP credentials for store %1', $storeId);
                    $logsArray['message_type'] = 'Success';
                    $this->logsHelper->logs($logsArray);
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
                    $this->_credentials[$websiteId][$storeId]['merchant_id'] = $merchantId;
                } else {
                    $this->_errorCount = true;
                    $logsArray['emarsys_info'] = __('Catalog Feed Export Mapping Error');
                    $logsArray['description'] = __('No default mapping for for the store %1.', $store->getName());
                    $logsArray['message_type'] = 'Error';
                    $this->logsHelper->logs($logsArray);
                }
            } else {
                $this->_errorCount = true;
                $logsArray['emarsys_info'] = __('Catalog Feed Export is Disabled');
                $logsArray['description'] = __('Catalog Feed Export is Disabled for the store %1.', $store->getName());
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
        $apiUserName = $store->getConfig(EmarsysHelper::XPATH_EMARSYS_API_USER);
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
     * @param $excludedCategories
     * @return array
     */
    public function getCategoryNames($catIds, $storeId, $excludedCategories = [])
    {
        $key = $storeId . '-' . serialize($catIds);
        if (!isset($this->_categoryNames[$key])) {
            $this->_categoryNames[$key] = [];
            foreach ($catIds as $catId) {
                if (in_array($catId, $excludedCategories)) {
                    continue;
                }
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
                    $this->_categoryNames[$key][] = implode(" > ", $childCats);
                }
            }
        }

        return $this->_categoryNames[$key];
    }

    /**
     * @param $magentoAttributeNames
     * @param \Magento\Catalog\Model\Product $productObject
     * @param $categoryNames
     * @param \Magento\Store\Model\Store $store
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param array $logsArray
     * @return array
     * @throws \Exception
     */
    protected function _getProductData($magentoAttributeNames, $productObject, $categoryNames, $store, $collection, $logsArray)
    {
        $attributeData = [];
        foreach ($magentoAttributeNames as $attributeCode) {
            try {
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
                        $inStock = $productObject->isAvailable();
                        $visibility = ($productObject->getVisibility() != Visibility::VISIBILITY_NOT_VISIBLE);

                        if ($status && $inStock) {
                            $attributeData[] = 'TRUE';
                        } else {
                            $attributeData[] = 'FALSE';
                        }

                        //'available_rec':
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
                        $price = $productObject->getMinimalPrice();
                        if ($price <= 0.0001) {
                            $price = $attributeOption;
                        }
                        $attributeData[] = number_format($price, 2, '.', '');
                        break;
                    default:
                        $attributeData[] = $attributeOption;
                        break;

                }
            } catch (\Exception $e) {
                $attributeData[] = '';
                $logsArray['emarsys_info'] = __('consolidatedCatalogExport _getProductData Exception');
                $logsArray['description'] = __('%1: %2', $attributeCode, $e->getMessage());
                $logsArray['message_type'] = 'Error';
                $this->logsHelper->logs($logsArray);
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
