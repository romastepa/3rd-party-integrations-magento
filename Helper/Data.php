<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Emarsys\Emarsys\Helper\Logs as EmarsysHelperLogs;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Model\ResourceModel\Customer as ModelResourceModelCustomer;
use Emarsys\Emarsys\Model\Queue;
use Magento\Framework\App\ProductMetadataInterface;
use Emarsys\Emarsys\Model\QueueFactory as EmarsysQueueFactory;
use Emarsys\Emarsys\Model\ResourceModel\Emarsysmagentoevents\CollectionFactory;
use Emarsys\Emarsys\Model\PlaceholdersFactory;
use Emarsys\Emarsys\Controller\Adminhtml\Email\Template;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as ProductCollectionFactory;
use Emarsys\Emarsys\Model\EmarsyseventmappingFactory;
use Emarsys\Emarsys\Model\ResourceModel\Emarsysevents\CollectionFactory as EmarsyseventsCollectionFactory;
use Emarsys\Emarsys\Model\Api as EmarsysApi;
use Emarsys\Emarsys\Model\Emarsysevents;
use Emarsys\Emarsys\Model\ResourceModel\Event as ModelResourceModelEvent;
use Magento\Email\Model\TemplateFactory as EmailTemplateFactory;
use Magento\Backend\Model\Session as BackendSession;
use Emarsys\Emarsys\Model\EmarsyseventsFactory;
use Emarsys\Emarsys\Model\Api\Api as EmarsysApiApi;
use Emarsys\Emarsys\Model\Logs as EmarsysModelLogs;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File as FilesystemIoFile;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Filesystem\Io\Ftp;

/**
 * Class Data
 * @package Emarsys\Emarsys\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const MODULE_NAME = 'Emarsys_Emarsys';

    const Version = '2.1';

    const OPTIN_PRIORITY = 'Emarsys';

    const EMARSYS_CDN_API_URL = 'https://api-cdn.emarsys.net/api/v2/';

    const EMARSYS_DEFAULT_API_URL = 'https://api.emarsys.net/api/v2/';

    //XML Path of Emarsys Credentials
    const XPATH_EMARSYS_ENABLED = 'emarsys_settings/emarsys_setting/enable';

    const XPATH_EMARSYS_API_ENDPOINT = 'emarsys_settings/emarsys_setting/emarsys_api_endpoint';

    const XPATH_EMARSYS_CUSTOM_URL = 'emarsys_settings/emarsys_setting/emarsys_custom_url';

    const XPATH_EMARSYS_API_USER = 'emarsys_settings/emarsys_setting/emarsys_api_username';

    const XPATH_EMARSYS_API_PASS = 'emarsys_settings/emarsys_setting/emarsys_api_password';

    //XML Path of Webdav Credentials
    const XPATH_WEBDAV_URL = 'emarsys_settings/webdav_setting/webdav_url';

    const XPATH_WEBDAV_USER = 'emarsys_settings/webdav_setting/webdav_user';

    const XPATH_WEBDAV_PASSWORD = 'emarsys_settings/webdav_setting/webdav_password';

    //XML Path of FTP Credentials
    const XPATH_EMARSYS_FTP_HOSTNAME = 'emarsys_settings/ftp_settings/hostname';

    const XPATH_EMARSYS_FTP_PORT = 'emarsys_settings/ftp_settings/port';

    const XPATH_EMARSYS_FTP_USERNAME = 'emarsys_settings/ftp_settings/username';

    const XPATH_EMARSYS_FTP_PASSWORD = 'emarsys_settings/ftp_settings/ftp_password';

    const XPATH_EMARSYS_FTP_BULK_EXPORT_DIR = 'emarsys_settings/ftp_settings/ftp_bulk_export_dir';

    const XPATH_EMARSYS_FTP_USEFTP_OVER_SSL = 'emarsys_settings/ftp_settings/useftp_overssl';

    const XPATH_EMARSYS_FTP_USE_PASSIVE_MODE = 'emarsys_settings/ftp_settings/usepassive_mode';

    //Contacts Synchronization
    const XPATH_EMARSYS_ENABLE_CONTACT_FEED = 'contacts_synchronization/enable/contact_feed';

    const XPATH_EMARSYS_REALTIME_SYNC = 'contacts_synchronization/emarsys_emarsys/realtime_sync';

    const XPATH_EMARSYS_BACKGROUND_RUNTIME = 'contacts_synchronization/emarsys_emarsys/background_runtime';

    const XPATH_EMARSYS_UNIQUE_FIELD = 'contacts_synchronization/emarsys_emarsys/unique_field';

    //Opt In Settings
    const XPATH_OPTIN_ENABLED = 'opt_in/optin_enable/enable_optin';

    const XPATH_OPTIN_EVERYPAGE_STRATEGY = 'opt_in/optin_enable/opt_in_strategy';

    const XPATH_OPTIN_FORCED_CONFIRMATION = 'opt_in/optin_enable/force_optin_confirmation';

    //Smart Insight
    const XPATH_SMARTINSIGHT_ENABLED = 'smart_insight/smart_insight/smartinsight_enabled';

    const XPATH_SMARTINSIGHT_EXPORT_ORDER_STATUS = 'smart_insight/smart_insight/orderexportforstatus';

    const XPATH_SMARTINSIGHT_EXPORTGUEST_CHECKOUTORDERS = 'smart_insight/smart_insight/exportguest_checkoutorders';

    const XPATH_SMARTINSIGHT_EXPORTUSING_EMAILIDENTIFIER = 'smart_insight/smart_insight/exportusing_emailidentifier';

    //Smart Insight API Settings
    const XPATH_EMARSYS_SIEXPORT_API_ENABLED = 'smart_insight/api_settings/enableapi';

    const XPATH_EMARSYS_SIEXPORT_MERCHANT_ID = 'smart_insight/api_settings/merchant_id';

    const XPATH_EMARSYS_SIEXPORT_TOKEN = 'smart_insight/api_settings/token';

    const XPATH_EMARSYS_SIEXPORT_MAX_RECORDS = 'smart_insight/api_settings/max_records_per_export';

    const XPATH_EMARSYS_SIEXPORT_MAX_RECORDS_OPT = 'smart_insight/api_settings/max_records_per_export_option';

    const XPATH_EMARSYS_SIEXPORT_API_URL = 'smart_insight/api_settings/smartinsight_api_url';

    const XPATH_EMARSYS_SIEXPORT_API_URL_KEY = 'smart_insight/api_settings/smartinsight_order_api_url_key';

    const XPATH_EMARSYS_CATALOG_EXPORT_API_URL_KEY = 'smart_insight/api_settings/smartinsight_product_api_url_key';

    //Product Catalog
    const XPATH_PREDICT_ENABLE_NIGHTLY_PRODUCT_FEED = 'emarsys_predict/feed_export/enable_nightly_product_feed';

    const XPATH_PREDICT_INCLUDE_BUNDLE_PRODUCT = 'emarsys_predict/feed_export/include_bundle_product';

    const XPATH_PREDICT_EXCLUDED_CATEGORIES = 'emarsys_predict/feed_export/excludedcategories';

    const XPATH_PREDICT_API_ENABLED = 'emarsys_predict/catalog_api_settings/enable_catalog_api';

    const XPATH_PREDICT_MERCHANT_ID = 'emarsys_predict/catalog_api_settings/catalog_merchant_id';

    const XPATH_PREDICT_TOKEN = 'emarsys_predict/catalog_api_settings/catalog_token';

    //WebExtend
    const XPATH_CMS_INDEX_INDEX = 'web_extend/recommended_product_pages/recommended_home_page';

    const XPATH_CATALOG_CATEGORY_VIEW = 'web_extend/recommended_product_pages/recommended_category_page';

    const XPATH_CATALOG_PRODUCT_VIEW = 'web_extend/recommended_product_pages/recommended_product_page';

    const XPATH_CHECKOUT_CART_INDEX = 'web_extend/recommended_product_pages/recommended_cart_page';

    const XPATH_CHECKOUT_ONEPAGE_SUCCESS = 'web_extend/recommended_product_pages/recommended_order_thankyou_page';

    const XPATH_CATALOGSEARCH_RESULT_INDEX = 'web_extend/recommended_product_pages/recommended_nosearch_result_page';

    const XPATH_WEBEXTEND_JS_TRACKING_ENABLED = 'web_extend/javascript_tracking/enable_javascript_integration';

    const XPATH_WEBEXTEND_MERCHANT_ID = 'web_extend/javascript_tracking/merchant_id';

    const XPATH_WEBEXTEND_MODE = 'web_extend/javascript_tracking/testmode';

    const XPATH_WEBEXTEND_IDENTITY = 'web_extend/javascript_tracking/identityregistered';

    const XPATH_WEBEXTEND_UNIQUE_ID = 'web_extend/javascript_tracking/uniqueidentifier';

    const XPATH_WEBEXTEND_USE_BASE_CURRENCY = 'web_extend/javascript_tracking/use_base_currency';

    const XPATH_WEBEXTEND_INCLUDE_TAX = 'web_extend/javascript_tracking/tax_included';

    const XPATH_WEBEXTEND_AJAXUPDATE = 'web_extend/javascript_tracking/ajaxupdate';

    const ENTITY_EXPORT_MODE_AUTOMATIC = 'Automatic';

    const ENTITY_EXPORT_MODE_MANUAL = 'Manual';

    const EMARSYS_RELEASE_URL = 'about_emarsys/emarsys_release/release_url';

    const CUSTOMER_EMAIL = 'Email';

    const SUBSCRIBER_ID = 'Magento Subscriber ID';

    const CUSTOMER_ID = 'Magento Customer ID';

    const CUSTOMER_UNIQUE_ID = 'Magento Customer Unique ID';

    const OPT_IN = 'Opt-In';

    const BATCH_SIZE = 1000;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var EmarsysModelLogs
     */
    protected $emarsysLogs;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var Timezone
     */
    protected $timezone;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadataInterface;

    /**
     * @var Logs
     */
    protected $logHelper;

    /**
     * @var Customer
     */
    protected $customerResourceModel;

    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var EmarsysQueueFactory
     */
    protected $queueColl;

    /**
     * @var CollectionFactory
     */
    protected $magentoEventsCollection;

    /**
     * @var PlaceholdersFactory
     */
    protected $emarsysEventPlaceholderMappingFactory;

    /**
     * @var Template
     */
    protected $emailTemplate;

    /**
     * @var ProductCollectionFactory
     */
    protected $magentoProductAttributeColl;

    /**
     * @var EmarsyseventmappingFactory
     */
    protected $emarsysEventMapping;

    /**
     * @var EmarsyseventsCollectionFactory
     */
    protected $emarsysEventCollectonFactory;

    /**
     * @var Event
     */
    protected $eventsResourceModel;

    /**
     * @var Emarsysevents
     */
    protected $emarsysEventsModel;

    /**
     * @var BackendSession
     */
    protected $session;

    /**
     * @var EmailTemplateFactory
     */
    protected $templateFactory;

    /**
     * @var EmarsyseventsFactory
     */
    protected $emarsysEventsModelFactory;

    /**
     * @var EmarsysApiApi
     */
    protected $api;

    /**
     * @var EmarsysApi
     */
    protected $modelApi;

    /**
     * @var StoreCollectionFactory
     */
    protected $storeCollection;

    /**
     * @var ModuleListInterface
     */
    protected $moduleListInterface;

    /**
     * @var Subscriber
     */
    protected $subscriber;

    /**
     * @var SubscriberCollectionFactory
     */
    protected $newsLetterCollectionFactory;

    /**
     * @var SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @var
     */
    private $_username;

    /**
     * @var
     */
    private $_secret;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var FilesystemIoFile
     */
    protected $filesystemIoFile;

    /**
     * @var Ftp
     */
    protected $ftp;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param DateTime $date
     * @param Timezone $timezone
     * @param Logs $logHelper
     * @param StoreManagerInterface $storeManager
     * @param ModelResourceModelCustomer $customerResourceModel
     * @param Queue $queueModel
     * @param ProductMetadataInterface $productMetadataInterface
     * @param EmarsysQueueFactory $queueModelColl
     * @param CollectionFactory $magentoEventsCollection
     * @param PlaceholdersFactory $emarsysEventPlaceholderMappingFactory
     * @param Template $emailTemplate
     * @param ProductCollectionFactory $magentoProductAttributeColl
     * @param EmarsyseventmappingFactory $emarsysEventMapping
     * @param EmarsyseventsCollectionFactory $emarsysEventCollectionFactory
     * @param EmarsysApi $modelApi
     * @param Emarsysevents $emarsysEventsModel
     * @param ModelResourceModelEvent $eventsResourceModel
     * @param EmailTemplateFactory $templateFactory
     * @param BackendSession $session
     * @param EmarsyseventsFactory $emarsysEventsModelFactory
     * @param EmarsysApiApi $api
     * @param EmarsysModelLogs $emarsysLogs
     * @param StoreCollectionFactory $storeCollection
     * @param ModuleListInterface $moduleListInterface
     * @param SubscriberCollectionFactory $newsLetterCollectionFactory
     * @param Subscriber $subscriber
     * @param SubscriberFactory $subscriberFactory
     * @param Ftp $ftp
     */
    public function __construct(
        Context $context,
        DateTime $date,
        Timezone $timezone,
        EmarsysHelperLogs $logHelper,
        StoreManagerInterface $storeManager,
        ModelResourceModelCustomer $customerResourceModel,
        Queue $queueModel,
        ProductMetadataInterface $productMetadataInterface,
        EmarsysQueueFactory $queueModelColl,
        CollectionFactory $magentoEventsCollection,
        PlaceholdersFactory $emarsysEventPlaceholderMappingFactory,
        Template $emailTemplate,
        ProductCollectionFactory $magentoProductAttributeColl,
        EmarsyseventmappingFactory $emarsysEventMapping,
        EmarsyseventsCollectionFactory $emarsysEventCollectionFactory,
        EmarsysApi $modelApi,
        Emarsysevents $emarsysEventsModel,
        ModelResourceModelEvent $eventsResourceModel,
        EmailTemplateFactory $templateFactory,
        BackendSession $session,
        EmarsyseventsFactory $emarsysEventsModelFactory,
        EmarsysApiApi $api,
        EmarsysModelLogs $emarsysLogs,
        StoreCollectionFactory $storeCollection,
        ModuleListInterface $moduleListInterface,
        SubscriberCollectionFactory $newsLetterCollectionFactory,
        Subscriber $subscriber,
        SubscriberFactory $subscriberFactory,
        DirectoryList $directoryList,
        FilesystemIoFile $filesystemIoFile,
        Ftp $ftp
    ) {
        $this->context = $context;
        $this->emarsysLogs = $emarsysLogs;
        $this->date = $date;
        $this->timezone = $timezone;
        $this->productMetadataInterface = $productMetadataInterface;
        $this->storeManager = $storeManager;
        $this->logHelper = $logHelper;
        $this->customerResourceModel = $customerResourceModel;
        $this->queue = $queueModel;
        $this->queueColl = $queueModelColl;
        $this->scopeConfigInterface = $context->getScopeConfig();
        $this->magentoEventsCollection = $magentoEventsCollection;
        $this->emarsysEventPlaceholderMappingFactory = $emarsysEventPlaceholderMappingFactory;
        $this->emailTemplate = $emailTemplate;
        $this->magentoProductAttributeColl = $magentoProductAttributeColl;
        $this->emarsysEventMapping = $emarsysEventMapping;
        $this->emarsysEventCollectonFactory = $emarsysEventCollectionFactory;
        $this->eventsResourceModel = $eventsResourceModel;
        $this->emarsysEventsModel = $emarsysEventsModel;
        $this->session = $session;
        $this->templateFactory = $templateFactory;
        $this->emarsysEventsModelFactory = $emarsysEventsModelFactory;
        $this->_emarsysApiUrl = 'https://trunk-int.s.emarsys.com/api/v2/';
        $this->api = $api;
        $this->modelApi = $modelApi;
        $this->storeCollection = $storeCollection;
        $this->moduleListInterface = $moduleListInterface;
        $this->subscriber = $subscriber;
        $this->newsLetterCollectionFactory = $newsLetterCollectionFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->directoryList = $directoryList;
        $this->filesystemIoFile = $filesystemIoFile;
        $this->ftp = $ftp;

        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function isTestModeEnabled()
    {
        return (bool)$this->scopeConfig->getValue(
            self::XPATH_WEBEXTEND_MODE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getIdentityRegistered()
    {
        return $this->scopeConfig->getValue(
            self::XPATH_WEBEXTEND_IDENTITY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getUniqueIdentifier()
    {
        return $this->scopeConfig->getValue(
            self::XPATH_WEBEXTEND_UNIQUE_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return bool
     */
    public function isUseBaseCurrency()
    {
        return (bool)$this->scopeConfig->getValue(
            self::XPATH_WEBEXTEND_USE_BASE_CURRENCY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return bool
     */
    public function isIncludeTax()
    {
        return (bool)$this->scopeConfig->getValue(
            self::XPATH_WEBEXTEND_INCLUDE_TAX,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return bool
     */
    public function isAjaxUpdateEnabled()
    {
        return (bool)$this->scopeConfig->getValue(
            self::XPATH_WEBEXTEND_AJAXUPDATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Smart Insight Merchant Id
     * @return bool
     */
    public function isSIAPIExportEnabled()
    {
        return (bool)$this->scopeConfig->getValue(
            self::XPATH_EMARSYS_SIEXPORT_API_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Smart Insight Merchant Id
     * @return mixed
     */
    public function getSIExportMerchantId()
    {
        return $this->scopeConfig->getValue(
            self::XPATH_EMARSYS_SIEXPORT_MERCHANT_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Smart Insight API token
     * @return mixed
     */
    public function getSIExportToken()
    {
        return $this->scopeConfig->getValue(
            self::XPATH_EMARSYS_SIEXPORT_TOKEN,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Smart Insight API url
     * @return mixed
     */
    public function getEmarsysApiUrl()
    {
        return $this->scopeConfig->getValue(
            self::XPATH_EMARSYS_SIEXPORT_API_URL,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Smart Insight Order API url key
     * @return mixed
     */
    public function getOrderApiUrlKey()
    {
        return $this->scopeConfig->getValue(
            self::XPATH_EMARSYS_SIEXPORT_API_URL_KEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Smart Insight Product API url key
     * @return mixed
     */
    public function getProductApiUrlKey()
    {
        return $this->scopeConfig->getValue(
            self::XPATH_EMARSYS_CATALOG_EXPORT_API_URL_KEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param $websiteId
     * @return mixed
     */
    public function isCatalogExportEnabled($websiteId)
    {
        return $this->scopeConfig->getValue(
            self::XPATH_PREDICT_ENABLE_NIGHTLY_PRODUCT_FEED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );
    }

    /**
     * @param type $username
     * @param type $password
     * @param type $endpoint
     * @param type $url
     */
    public function assignApiCredentials($username, $password, $endpoint, $url = null)
    {
        $this->_username = $username;
        $this->_secret = $password;

        if ($endpoint == 'custom') {
            $this->_emarsysApiUrl = rtrim($url, '/') . "/";
        } elseif ($endpoint == 'cdn') {
            $this->_emarsysApiUrl = self::EMARSYS_CDN_API_URL;
        } elseif ($endpoint == 'default') {
            $this->_emarsysApiUrl = self::EMARSYS_DEFAULT_API_URL;
        }
    }

    /**
     * @return EmarsysApi
     */
    public function getClient()
    {
        $this->modelApi->setParams([
            'api_url' => $this->_emarsysApiUrl,
            'api_username' => $this->_username,
            'api_password' => $this->_secret,
        ]);

        return $this->modelApi;
    }

    /**
     * Get Emarsys API Details
     *
     * @param type $storeId
     */
    public function getEmarsysAPIDetails($storeId)
    {
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        $scope = ScopeInterface::SCOPE_WEBSITES;
        $username = $this->scopeConfigInterface->getValue(self::XPATH_EMARSYS_API_USER, $scope, $websiteId);
        $password = $this->_secret = $this->scopeConfigInterface->getValue(self::XPATH_EMARSYS_API_PASS, $scope, $websiteId);
        $endpoint = $this->scopeConfigInterface->getValue(self::XPATH_EMARSYS_API_ENDPOINT, $scope, $websiteId);

        if ($endpoint == 'custom') {
            $url = $this->scopeConfigInterface->getValue(self::XPATH_EMARSYS_CUSTOM_URL, $scope, $websiteId);
            $this->_emarsysApiUrl = rtrim($url, '/') . "/";
        } elseif ($endpoint == 'cdn') {
            $this->_emarsysApiUrl = self::EMARSYS_CDN_API_URL;
        } elseif ($endpoint == 'default') {
            $this->_emarsysApiUrl = self::EMARSYS_DEFAULT_API_URL;
        }

        $this->_username = $username;
        $this->_secret = $password;
    }

    /**
     * @param $requestType
     * @param null $endPoint
     * @param string $requestBody
     * @return mixed
     * @throws \Exception
     */
    public function send($requestType, $endPoint = null, $requestBody = '')
    {
        if ($endPoint == 'custom') {
            $endPoint = '';
        }

        if (!in_array($requestType, ['GET', 'POST', 'PUT', 'DELETE'])) {
            throw new \Exception('Send first parameter must be "GET", "POST", "PUT" or "DELETE"');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        switch ($requestType) {
            case 'GET':
                curl_setopt($ch, CURLOPT_HTTPGET, 1);
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
                break;
        }
        curl_setopt($ch, CURLOPT_HEADER, false);

        $requestUri = $this->_emarsysApiUrl . $endPoint;
        curl_setopt($ch, CURLOPT_URL, $requestUri);

        /**
         * We add X-WSSE header for authentication.
         * Always use random 'nonce' for increased security.
         * timestamp: the current date/time in UTC format encoded as
         * an ISO 8601 date string like '2010-12-31T15:30:59+00:00' or '2010-12-31T15:30:59Z'
         * passwordDigest looks sg like 'MDBhOTMwZGE0OTMxMjJlODAyNmE1ZWJhNTdmOTkxOWU4YzNjNWZkMw=='
         */
        $nonce = md5(time());
        $timestamp = gmdate("c");
        $passwordDigest = base64_encode(sha1($nonce . $timestamp . $this->_secret, false));

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-WSSE: UsernameToken ' .
            'Username="' . $this->_username . '", ' .
            'PasswordDigest="' . $passwordDigest . '", ' .
            'Nonce="' . $nonce . '", ' .
            'Created="' . $timestamp . '"',
            'Content-type: application/json;charset="utf-8"',
        ]);
        $output = curl_exec($ch);

        if (curl_error($ch)) {
            $this->emarsysLogs->addErrorLog(
                curl_error($ch),
                $this->storeManager->getStore()->getId(),
                'Send(helper/data)'
            );
        }

        curl_close($ch);

        return $output;
    }

    /**
     * @param $storeId
     */
    public function insertFirstime($storeId)
    {
        $magentoEvents = $this->magentoEventsCollection->create();

        foreach ($magentoEvents as $magentoEvent) {
            $magentoEvent->getId();
            $eventMappingModel = $this->emarsysEventMapping->create();
            $eventMappingModel->setMagentoEventId($magentoEvent->getId());
            $eventMappingModel->setEmarsysEventId('');
            $eventMappingModel->setStoreId($storeId);
            $eventMappingModel->save();
        }
    }

    /**
     * @return array
     */
    public function getReadonlyMagentoEventIds()
    {
        return [1, 2];
    }

    /**
     * Checking readonly magento events
     * @param int $id
     * @return bool
     */
    public function isReadonlyMagentoEventId($id = 0)
    {
        if (in_array($id, $this->getReadonlyMagentoEventIds())) {
            return true;
        }

        return false;
    }

    /**
     * @param null $logId
     */
    public function importEvents($logId = null)
    {
        $logsArray['id'] = $logId;
        $logsArray['emarsys_info'] = 'Update Schema';

        try {
            $storeId = "";

            if ($this->session->getStoreId()) {
                $storeId = $this->session->getStoreId();
            }
            //get emarsys events and store it into array
            $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
            $apiEvents = $this->getEvents($storeId, $logId);

            if (count($apiEvents) == 0) {
                return;
            }

            $eventArray = [];
            foreach ($apiEvents as $key => $value) {
                $eventArray[$key] = $value;
            }

            //Delete unwanted events exist in database
            $emarsysEvents = $this->emarsysEventCollectonFactory->create();
            foreach ($emarsysEvents as $emarsysEvent) {
                if (!array_key_exists($emarsysEvent->getEventId(), $eventArray)) {
                    $this->eventsResourceModel->deleteEvent($emarsysEvent->getEventId(), $storeId);
                }
            }
            //Update & create new events found in Emarsys
            foreach ($eventArray as $key => $value) {
                $emarsysEvents = $this->emarsysEventCollectonFactory->create();
                $emarsysEventItem = $emarsysEvents->addFieldToFilter("event_id", $key)
                    ->addFieldToFilter("store_id", $storeId)
                    ->getFirstItem();
                if ($emarsysEventItem && $emarsysEventItem->getId()) {
                    $model = $this->emarsysEventsModel->load($emarsysEventItem->getId(), "id");
                    $model->setEmarsysEvent($value);
                    $model->setStoreId($storeId);
                    $model->save();
                } else {
                    $model = $this->emarsysEventsModelFactory->create();
                    $model->setEventId($key);
                    $model->setEmarsysEvent($value);
                    $model->setStoreId($storeId);
                    $model->save();
                }
            }
        } catch (\Exception $e) {
            $logsArray['description'] = $e->getMessage();
            $logsArray['action'] = 'Update Schema';
            $logsArray['store_id'] = $storeId;
            $logsArray['message_type'] = 'Error';
            $logsArray['log_action'] = 'True';
            $logsArray['website_id'] = $websiteId;
            $this->logHelper->logs($logsArray);

            return;
        }
    }

    /**
     * @param $storeId
     * @param null $logId
     * @return array|void
     */
    public function getEvents($storeId, $logId=null)
    {
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        $logsArray['id'] = $logId;
        $logsArray['store_id'] = $storeId;
        $logsArray['emarsys_info'] = 'Update Schema';

        try {
            $result = [];
            $this->api->setWebsiteId($websiteId);
            $response = $this->api->sendRequest('GET', 'event');

            if ($response['status']==200) {
                if (!empty($response['body']['data'])) {
                    foreach ($response['body']['data'] as $item) {
                        $result[$item['id']] = $item['name'];
                    }
                    $logsArray['description'] = print_r($result,true);
                    $logsArray['action'] = 'Update Schema';
                    $logsArray['message_type'] = 'Success';
                    $logsArray['log_action'] = 'True';
                    $logsArray['website_id'] = $websiteId;
                    $this->logHelper->logs($logsArray);
                }
            } else {
                $logsArray['description'] = $response['body']['replyText'];
                $logsArray['action'] = 'Update Schema';
                $logsArray['message_type'] = 'Error';
                $logsArray['log_action'] = 'True';
                $logsArray['website_id'] = $websiteId;
                $this->logHelper->logs($logsArray);

                return;
            }

            return $result;
        } catch (\Exception $e) {
            $logsArray['description'] = $e->getMessage();
            $logsArray['action'] = 'Mail Sent';
            $logsArray['message_type'] = 'Error';
            $logsArray['log_action'] = 'True';
            $logsArray['website_id'] = $websiteId;

            $this->logHelper->logs($logsArray);
        }
    }

    /**
     * Get Log file path from configuration
     *
     * @return string
     */
    public function getLogPath()
    {
        $path = $this->customerResourceModel->getDataFromCoreConfig(
            'logs/log_setting/download_logpath',
            null,
            null
        );

        if (!empty($path)) {
            return BP . '/' . ltrim($path, '/');
        } else {
            return '';
        }
    }

    /**
     * @return array|mixed
     */
    public function getPhpInfoArray()
    {
        try {
            ob_start();
            phpinfo(INFO_ALL);

            $pi = preg_replace(
                [
                    '#^.*<body>(.*)</body>.*$#m', '#<h2>PHP License</h2>.*$#ms',
                    '#<h1>Configuration</h1>#', "#\r?\n#", "#</(h1|h2|h3|tr)>#", '# +<#',
                    "#[ \t]+#", '#&nbsp;#', '#  +#', '# class=".*?"#', '%&#039;%',
                    '#<tr>(?:.*?)" src="(?:.*?)=(.*?)" alt="PHP Logo" /></a><h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
                    '#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
                    '#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
                    "# +#", '#<tr>#', '#</tr>#'],
                [
                    '$1', '', '', '', '</$1>' . "\n", '<', ' ', ' ', ' ', '', ' ',
                    '<h2>PHP Configuration</h2>' . "\n" . '<tr><td>PHP Version</td><td>$2</td></tr>' .
                    "\n" . '<tr><td>PHP Egg</td><td>$1</td></tr>',
                    '<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
                    '<tr><td>Zend Engine</td><td>$2</td></tr>' . "\n" .
                    '<tr><td>Zend Egg</td><td>$1</td></tr>', ' ', '%S%', '%E%'
                ],
                ob_get_clean()
            );

            $sections = explode('<h2>', strip_tags($pi, '<h2><th><td>'));
            unset($sections[0]);

            $pi = [];
            foreach ($sections as $section) {
                $n = substr($section, 0, strpos($section, '</h2>'));
                preg_match_all(
                    '#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#',
                    $section,
                    $askapache,
                    PREG_SET_ORDER
                );
                foreach ($askapache as $m) {
                    if (!isset($m[0]) || !isset($m[1]) || !isset($m[2])) {
                        continue;
                    }
                    $pi[$n][$m[1]] = (!isset($m[3]) || $m[2] == $m[3]) ? $m[2] : array_slice($m, 2);
                }
            }
        } catch (\Exception $exception) {
            return [];
        }

        return $pi;
    }

    /**
     * @return array
     */
    public function getPhpSettings()
    {
        return [
            //'memory_limit' => $this->getMemoryLimit(),
            'max_execution_time' => @ini_get('max_execution_time'),
            'phpinfo' => $this->getPhpInfoArray()
        ];
    }

    /**
     * @param bool $inMegabytes
     * @return int|string
     */
    public function getMemoryLimit($inMegabytes = true)
    {
        $memoryLimit = trim(ini_get('memory_limit'));

        if ($memoryLimit == '') {
            return 0;
        }

        switch (strtolower(substr($memoryLimit, -1))) {
            case 'm':
            case 'g':
            case 'k': return (int)$memoryLimit * 1024;
            default: return (int)$memoryLimit;
        }

        if ($inMegabytes) {
            $memoryLimit /= 1024 * 1024;
        }

        return $memoryLimit;
    }

    /**
     * @return mixed
     */
    public function getPhpVersion()
    {
        $phpVersion = @phpversion();
        $array = explode("-", $phpVersion);
        return $array[0];
    }

    /**
     * @param bool $asArray
     * @return string
     */
    public function getVersion($asArray = false)
    {
        $version = '';

        if ($this->productMetadataInterface->getVersion()) {
            $version = $this->productMetadataInterface->getVersion();
        }

        return $version;
    }

    /**
     * @param null $storeId
     * @return array
     */
    public function getRequirementsInfo($storeId = null)
    {
        if ($storeId) {
            $storeID = $storeId;
        } else {
            $storeID = $this->storeManager->getStore()->getId();
        }
        $websiteId = $this->storeManager->getStore($storeID)->getWebsiteId();
        $aggregatePortStatus = $this->openPortStatus($storeID);
        $smartInsightStatus = $this->getCheckSmartInsight($websiteId);
        $smartInsight = $smartInsightStatus ? 'Enable' : 'Disable';
        $emarsysStatus = $this->getEmarsysConnectionSetting($websiteId);
        $emarsysModuleStatus = $emarsysStatus ? 'Enable' : 'Disable';

        $clientPhpData = $this->getPhpSettings();

        if ($clientPhpData['phpinfo']['soap'] ['Soap Client'] == 'enabled') :
            $soapenable = 'Yes';
        else :
            $soapenable = 'No';
        endif;

        if ($clientPhpData['phpinfo']['curl'] ['cURL support'] == 'enabled') :
            $curlenable = 'Yes';
        else :
            $curlenable = 'No';
        endif;

        $requirements = [
            'php_version' => [
                'title' => 'PHP Version',
                'condition' => [
                    'sign' => '>=',
                    'value' => '5.6.0'
                ],
                'current' => [
                    'value' => $this->getPhpVersion(),
                    'status' => true
                ]
            ],
            'memory_limit' => [
                'title' => 'Memory Limit',
                'condition' => [
                    'sign' => '>=',
                    'value' => '512 MB'
                ],
                'current' => [
                    'value' => ini_get('memory_limit'),
                    'status' => true
                ]
            ],
            'magento_version' => [
                'title' => 'Magento Version',
                'condition' => [
                    'sign' => '>=',
                    'value' => '2.1',
                ],
                'current' => [
                    'value' => $this->getVersion(false),
                    'status' => true
                ]
            ],
            'emarsys_extension_version' => [
                'title' => 'Emarsys Extension Version',
                'condition' => [
                    'sign' => '>=',
                    'value' => '1.0.0',
                ],
                'current' => [
                    'value' => $this->getEmarsysVersion(),
                    'status' => true
                ]
            ],
            'curl_enabled' => [
                'title' => 'Curl Enabled',
                'condition' => [
                    'sign' => '',
                    'value' => 'Yes',
                ],
                'current' => [
                    'value' => $curlenable,
                    'status' => true
                ]
            ],
            'tcp_21' => [
                'title' => 'TCP21,TCP32000-35000Open',
                'condition' => [
                    'sign' => '',
                    'value' => 'Yes'
                ],
                'current' => [
                    'value' => $aggregatePortStatus,
                    'status' => true
                ]
            ],
            'soap_enabled' => [
                'title' => 'SOAP Enabled',
                'condition' => [
                    'sign' => '',
                    'value' => 'Yes'
                ],
                'current' => [
                    'value' => $soapenable,
                    'status' => true
                ]
            ],
            'smart_insight' => [
                'title' => 'Smart Insight',
                'condition' => [
                    'sign' => '',
                    'value' => 'Enable'
                ],
                'current' => [
                    'value' => $smartInsight,
                    'status' => true
                ]
            ],
            'real_tome_sync' => [
                'title' => 'Realtime Synchronization',
                'condition' => [
                    'sign' => '',
                    'value' => 'Enable'
                ],
                'current' => [
                    'value' => $this->getRealTimeSync($websiteId),
                    'status' => true
                ]
            ],
            'emarsys_connection_setting' => [
                'title' => 'Emarsys Connection Setting',
                'condition' => [
                    'sign' => '',
                    'value' => 'Enable'
                ],
                'current' => [
                    'value' => $emarsysModuleStatus,
                    'status' => true
                ]
            ],
        ];

        foreach ($requirements as $key => &$requirement) {
            // max execution time is unlimited
            if ($key == 'max_execution_time' && $clientPhpData['max_execution_time'] == 0) {
                continue;
            }

            $requirement['current']['status'] = version_compare(
                $requirement['current']['value'],
                $requirement['condition']['value'],
                $requirement['condition']['sign']
            );
        }

        return $requirements;
    }

    /**
     * @param $storeID
     * @return string
     */
    public function openPortStatus($storeID)
    {
        $websiteId = $this->storeManager->getStore($storeID)->getWebsiteId();
        $scope = ScopeInterface::SCOPE_WEBSITES;

        if ($scope && $websiteId) {
            $host = $this->scopeConfigInterface->getValue(self::XPATH_EMARSYS_FTP_HOSTNAME, $scope, $websiteId);
        } else {
            $host = $this->scopeConfigInterface->getValue(self::XPATH_EMARSYS_FTP_HOSTNAME);
        }

        $ports = [21, rand(32000, 32500), rand(32000, 32500)];
        $portStatus = [];
        foreach ($ports as $port) {
            $errno = null;
            $errstr = null;

            $connection = @fsockopen($host, $port, $errno, $errstr);

            if (is_resource($connection)) {
                $portStatus[] = 'open';
                fclose($connection);
            } else {
                $portStatus[] = 'closed';
            }
        }
        if (in_array('closed', $portStatus)) {
            $aggregatePortStatus = 'No';
        } else {
            $aggregatePortStatus = 'Yes';
        }

        return $aggregatePortStatus;
    }

    /**
     * @param $customerId
     * @param $websiteId
     * @param $storeId
     * @param $cron
     * @param $entityType
     */
    public function syncFail($customerId, $websiteId, $storeId, $cron, $entityType)
    {
        $queueColl = $this->queueColl->create()->getCollection();
        $queueModel = $this->queue;
        $queueColl->addFieldToFilter('entity_id', $customerId);
        $queueColl->addFieldToFilter('website_id', $websiteId);

        if (($queueColl->getSize() > 0) && $queueColl->getData() && isset($queueColl->getData()[0])) {
            $data = $queueColl->getData()[0];
            $queueModel->load($data['id']);
            if ($cron == 1) {
                $queueModel->setHitCount($data['hit_count'] + 1);
            } else {
                $queueModel->setHitCount(0);
            }
            $queueModel->save();
        } else {
            $queueModel = $this->queueColl->create();
            $queueModel->setEntityId($customerId);
            $queueModel->setWebsiteId($websiteId);
            $queueModel->setStoreId($storeId);
            $queueModel->setEntityTypeId($entityType);
            $queueModel->save();
        }
    }

    /**
     * @param $customerId
     * @param $websiteId
     * @param $storeId
     * @param $cron
     */
    public function syncSuccess($customerId, $websiteId, $storeId, $cron)
    {
        $queueColl = $this->queueColl->create()->getCollection();
        $queueModel = $this->queue;

        $queueColl->addFieldToFilter('entity_id', $customerId);
        $queueColl->addFieldToFilter('website_id', $websiteId);

        if ($queueColl->getSize() > 0) {
            $data = $queueColl->getData()[0];
            $queueModel->load($data['id']);
            $queueModel->delete();
        }
    }

    /**
     * @param $mappingId
     * @param $storeId
     * @return string
     */
    public function insertFirstimeMappingPlaceholders($mappingId, $storeId)
    {
        $magentoPlaceholderarray = [];
        $emarsysEventMappingColl = $this->emarsysEventMapping->create()->getCollection()
            ->addFieldToFilter('id', $mappingId);
        $magentoEventColl = $this->magentoEventsCollection->create()
            ->addFieldToFilter('id', $emarsysEventMappingColl->getData()[0]['magento_event_id']);
        $value = $this->scopeConfigInterface->getValue($magentoEventColl->getData()[0]['config_path'], 'default', 0);

        if ($value == "") {
            return '';
        }
        if (is_numeric($value)) {
            $emailTemplateModelColl = $this->templateFactory->create()->getCollection()
                ->addFieldToFilter('template_id', $value);
            $emailText = $emailTemplateModelColl->getData()[0]['template_text'];
        } else {
            $template = $this->emailTemplate->initTemplate('id');
            $templateId = $value;
            $template->setForcedArea($templateId);
            $template->loadDefault($templateId);
            $emailText = $template->getData()['template_text'];
        }

        $array = [];
        $i = 0;
        while ($variable = $this->substringBetween($emailText)) {
            $emailText = str_replace($variable, '', $emailText);
            if (!strstr($variable, '{{trans')) {
                $emarsysVariable = $this->getPlaceholderName($variable);
            }
            $emarsysVariable = $this->getPlaceholderName($variable);

            if (strstr($variable, '{{trans')) {
                $variable = $this->substringBetweenTransVar($variable);
                $emarsysVariable = $this->getPlaceholderName($variable);
            }

            if (strstr($variable, '{{depend')) {
                $variable = $this->substringBetweenTransVar($variable);
                $emarsysVariable = $this->getPlaceholderName($variable);
            }

            if (strstr($variable, '{{/if')) {
                $variable = $this->substringBetweenTransVar($variable);
                $emarsysVariable = $this->getPlaceholderName($variable);
            }

            if (strstr($variable, '{{template config_path')) {
                $variable = $this->substringBetweenTransVar($variable);
                $emarsysVariable = $this->getPlaceholderName($variable);
            }

            if (strstr($variable, '{{if')) {
                $variable = $this->substringBetweenTransVar($variable);
                $emarsysVariable = $this->getPlaceholderName($variable);
            }


            if (!empty($emarsysVariable)) {
                $array[$i]["event_mapping_id"] = $mappingId;
                $array[$i]["magento_placeholder_name"] = $variable;
                $array[$i]["emarsys_placeholder_name"] = $emarsysVariable;
                $array[$i]["store_id"] = $storeId;
                $i++;
            }
            $emarsysVariable = "";
        }

        foreach ($array as $key => $value) {
            if (in_array($value['magento_placeholder_name'], $magentoPlaceholderarray)) {
                continue;
            }
            $placeholderModel = $this->emarsysEventPlaceholderMappingFactory->create();
            $placeholderModel->setEventMappingId($value['event_mapping_id']);
            $placeholderModel->setMagentoPlaceholderName($value['magento_placeholder_name']);
            $placeholderModel->setEmarsysPlaceholderName($value['emarsys_placeholder_name']);
            $placeholderModel->setStoreId($value['store_id']);
            $magentoPlaceholderarray[] =$value['magento_placeholder_name'];
            $placeholderModel->save();
        }

        return 'success';
    }

    /**
     * Refresh Placeholders
     * @param $mappingId
     * @param $storeId
     */
    public function refreshPlaceholders($mappingId, $storeId)
    {
        $emarsysEventMappingCollFromDbArray = [];
        $emarsysEventMappingCollFromDb = $this->emarsysEventPlaceholderMappingFactory->create()
            ->getCollection()
            ->addFieldToFilter('event_mapping_id', $mappingId)
            ->addFieldToFilter('store_id', $storeId);

        foreach ($emarsysEventMappingCollFromDb as $_emarsysEventMappingCollFromDb) {
            $emarsysEventMappingCollFromDbArray[$_emarsysEventMappingCollFromDb['id']] = $_emarsysEventMappingCollFromDb['magento_placeholder_name'];
        }

        $emarsysEventMappingColl = $this->emarsysEventMapping->create()
            ->getCollection()
            ->addFieldToFilter('id', $mappingId);
        $magentoEventColl = $this->magentoEventsCollection->create()
            ->addFieldToFilter('id', $emarsysEventMappingColl->getData()[0]['magento_event_id']);

        $value = $this->scopeConfigInterface->getValue($magentoEventColl->getData()[0]['config_path'], 'store', $storeId);

        if (is_numeric($value)) {
            $emailTemplateModelColl = $this->templateFactory->create()
                ->getCollection()
                ->addFieldToFilter('template_id', $value);
            $emailText = $emailTemplateModelColl->getData()[0]['template_text'];
        } else {
            $template = $this->emailTemplate->initTemplate('id');
            $templateId = $value;
            $template->setForcedArea($templateId);
            $template->loadDefault($templateId);

            $emailText = $template->getData()['template_text'];
        }

        $templatePlaceholderArray = [];
        $array = [];
        $i = 0;
        while ($variable = $this->substringBetween($emailText)) {
            $emailText = str_replace($variable, '', $emailText);
            if (!strstr($variable, '{{trans')) {
                $emarsysVariable = $this->getPlaceholderName($variable);
            }
            $emarsysVariable = $this->getPlaceholderName($variable);

            if (strstr($variable, '{{trans')) {
                $variable = $this->substringBetweenTransVar($variable);
                $emarsysVariable = $this->getPlaceholderName($variable);
            }
            if (strstr($variable, '{{/depend')) {
                $variable = $this->substringBetweenTransVar($variable);
                $emarsysVariable = $this->getPlaceholderName($variable);
            }

            if (strstr($variable, '{{/if')) {
                $variable = $this->substringBetweenTransVar($variable);
                $emarsysVariable = $this->getPlaceholderName($variable);
            }

            if (strstr($variable, '{{template config_path')) {
                $variable = $this->substringBetweenTransVar($variable);
                $emarsysVariable = $this->getPlaceholderName($variable);
            }

            if (strstr($variable, '{{depend')) {
                $variable = $this->substringBetweenTransVar($variable);
                $emarsysVariable = $this->getPlaceholderName($variable);
            }
            if (strstr($variable, '{{if')) {
                $variable = $this->substringBetweenTransVar($variable);
                $emarsysVariable = $this->getPlaceholderName($variable);
            }

            if (!empty($emarsysVariable)) {
                $array[$i]["event_mapping_id"] = $mappingId;
                $array[$i]["magento_placeholder_name"] = $variable;
                $array[$i]["emarsys_placeholder_name"] = $emarsysVariable;
                $array[$i]["store_id"] = $storeId;
                $i++;
            }
            $emarsysVariable = "";
            $templatePlaceholderArray[] = $variable;
        }

        $deleteFromDbArray = array_diff($emarsysEventMappingCollFromDbArray, $templatePlaceholderArray);

        foreach ($deleteFromDbArray as $key => $_deleteFromDbArray) {
            $placeholderModel = $this->emarsysEventPlaceholderMappingFactory->create()->load($key);
            $placeholderModel->delete();
        }

        $insertNewFromTemplate = array_diff($templatePlaceholderArray, $emarsysEventMappingCollFromDbArray);

        foreach ($insertNewFromTemplate as $key => $_insertNewFromTemplate) {
            if ($_insertNewFromTemplate) {
                $placeholderModel = $this->emarsysEventPlaceholderMappingFactory->create();
                $placeholderModel->setEventMappingId($mappingId);
                $placeholderModel->setMagentoPlaceholderName($_insertNewFromTemplate);
                $placeholderModel->setEmarsysPlaceholderName($this->getPlaceholderName($_insertNewFromTemplate));
                $placeholderModel->setStoreId($storeId);
                $placeholderModel->save();
            }
        }
    }

    /**
     * @param $haystack
     * @param string $start
     * @param string $end
     * @return bool|string
     */
    public function substringBetween($haystack, $start = "{{", $end = "}}")
    {
        try {
            if (strpos($haystack, $start) === false || strpos($haystack, $end) === false) {
                return false;
            } else {
                $start_position = strpos($haystack, $start) + strlen($start);
                $end_position = strpos($haystack, $end);
                $string = substr($haystack, $start_position, $end_position - $start_position);
                return $start . $string . $end;
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'substringBetween($haystack, $start = "{{", $end = "}}")'
            );
        }
    }

    /**
     * @param $haystack
     * @param string $start
     * @param string $end
     * @return bool|string
     */
    public function substringBetweenTransVar($haystack, $start = "=$", $end = "}}")
    {
        try {
            if (strpos($haystack, $start) === false || strpos($haystack, $end) === false) {
                return false;
            } else {
                $start_position = strpos($haystack, $start) + strlen($start);
                $end_position = strpos($haystack, $end);
                $string = substr($haystack, $start_position, $end_position - $start_position);
                return "{{var " . $string . "}}";
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'substringBetweenTransVar($haystack, $start = "=$", $end = "}}")'
            );
        }
    }

    /**
     * @param string $variable
     * @return string
     */
    public function getPlaceholderName($variable = '')
    {
        try {
            if (empty($variable)
                || in_array($variable, ["{{/if}}", "{{/depend}}", "{{else}}", "{{var non_inline_styles}}"])
                || strstr($variable, 'inlinecss')
            ) {
                return;
            }
            $findReplace = [
                " "     => "_",
                ".get"  => "_",
                "."     => "_",
                "{{"    => "_",
                "}}"    => "_",
                "()"    => "_",
                "("     => "_",
                ")"     => "_",
                "["     => "_",
                "]"     => "_",
                "<"     => "_",
                ">"     => "_",
                "=$"    => "_",
                "="     => "_",
                "/"     => "_",
                "\\"    => "_",
                "\n"    => "_",
                "$"     => "_",
                "%"     => "_",
                ","     => "_",
                ":"     => "_",
                "|"     => "_",
                "'"     => "",
                '"'     => "",
                "var"   => "",
                "trans" => "",
                "_____" => "_",
                "____"  => "_",
                "___"   => "_",
                "__"    => "_",
            ];
            $emarsysVariable = str_replace(array_keys($findReplace), $findReplace, strtolower($variable));
            return trim(trim($emarsysVariable, "_"));
        } catch (\Exception $e) {
            $storeId = $this->storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'getPlaceholderName');
        }
    }

    /**
     * @return mixed
     */
    public function emarsysDefaultPlaceholders()
    {
        try {
            $order['product_purchases']['unitary_price_exc_tax'] = strtoupper("unitary_price_exc_tax");
            $order['product_purchases']['unitary_price_inc_tax'] = strtoupper("unitary_price_inc_tax");
            $order['product_purchases']['unitary_tax_amount'] = strtoupper("unitary_tax_amount");
            $order['product_purchases']['line_total_price_exc_tax'] = strtoupper("line_total_price_exc_tax");
            $order['product_purchases']['line_total_tax_amount'] = strtoupper("line_total_tax_amount");
            $order['product_purchases']['unitary_price_inc_tax'] = strtoupper("unitary_price_inc_tax");
            $order['product_purchases']['product_id'] = strtoupper("product_id");
            $order['product_purchases']['product_type'] = strtoupper("product_type");
            $order['product_purchases']['base_original_price'] = strtoupper("base_original_price");
            $order['product_purchases']['sku'] = strtoupper("sku");
            $order['product_purchases']['product_name'] = strtoupper("product_name");
            $order['product_purchases']['product_weight'] = strtoupper("product_weight");
            $order['product_purchases']['qty_ordered'] = strtoupper("qty_ordered");
            $order['product_purchases']['original_price'] = strtoupper("original_price");
            $order['product_purchases']['price'] = strtoupper("price");
            $order['product_purchases']['base_price'] = strtoupper("base_price");
            $order['product_purchases']['tax_percent'] = strtoupper("tax_percent");
            $order['product_purchases']['tax_amount'] = strtoupper("tax_amount");
            $order['product_purchases']['discount_amount'] = strtoupper("discount_amount");
            $order['product_purchases']['price_line_total'] = strtoupper("price_line_total");
            $order['product_purchases']['_external_image_url'] = strtoupper("_external_image_url");
            $order['product_purchases']['_url'] = strtoupper("_url");
            $order['product_purchases']['_url_name'] = strtoupper("_url_name");
            $order['product_purchases']['product_description'] = strtoupper("product_description");
            $order['product_purchases']['short_description'] = strtoupper("short_description");
            $order['product_purchases']['additional_data'] = strtoupper("additional_data");
            $order['product_purchases']['full_options'] = strtoupper("full_options");

            $attributesColl = $this->magentoProductAttributeColl->create();

            foreach ($attributesColl as $attribute) {
                if ($attribute['attribute_code'] == "gallery") {
                    continue;
                }
                $order['product_purchases']['attribute_' . $attribute['attribute_code']] = strtoupper('attribute_' . $attribute['attribute_code']);
            }

            return $order;
        } catch (\Exception $e) {
            $storeId = $this->storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog(htmlentities($e->getMessage()), $storeId, 'emarsysDefaultPlaceholders');
        }
    }

    /**
     * @param $mapping_id
     * @param $store_id
     * @return string
     */
    public function insertFirstimeHeaderMappingPlaceholders($mapping_id, $store_id)
    {
        try {
            $magentoEventsCollection = $this->magentoEventsCollection->create()
                ->addFieldToFilter('config_path', 'design/email/header_template');
            $emarsysEventMappingColl = $this->emarsysEventMapping->create()
                ->getCollection()
                ->addFieldToFilter('magento_event_id', $magentoEventsCollection->getData()[0]['id']);
            $emarsysEventMappingColl->addFieldToFilter('store_id', $store_id);

            return $this->insertFirstimeMappingPlaceholders(
                $emarsysEventMappingColl->getFirstItem()->getMagentoEventId(),
                $store_id
            );
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                htmlentities($e->getMessage()),
                $store_id,
                'insertFirstimeHeaderMappingPlaceholders'
            );
        }
    }

    /**
     * @param $mapping_id
     * @param $store_id
     * @return string
     */
    public function insertFirstimeFooterMappingPlaceholders($mapping_id, $store_id)
    {
        try {
            $magentoEventsCollection = $this->magentoEventsCollection->create()
                ->addFieldToFilter('config_path', 'design/email/footer_template');
            $emarsysEventMappingColl = $this->emarsysEventMapping->create()
                ->getCollection()
                ->addFieldToFilter(
                    'magento_event_id',
                    $magentoEventsCollection->getData()[0]['id']
                );
            $emarsysEventMappingColl->addFieldToFilter('store_id', $store_id);

            return $this->insertFirstimeMappingPlaceholders(
                $emarsysEventMappingColl->getFirstItem()->getMagentoEventId(),
                $store_id
            );
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                htmlentities($e->getMessage()),
                $store_id,
                'insertFirstimeFooterMappingPlaceholders'
            );
        }
    }

    /**
     * @param $mapping_id
     * @param $store_id
     * @return array
     */
    public function emarsysHeaderPlaceholders($mapping_id, $store_id)
    {
        $headerPlaceholderArray = [];
        try {
            $magentoEventsCollection = $this->magentoEventsCollection->create()
                ->addFieldToFilter('config_path', 'design/email/header_template');
            $emarsysEventMappingColl = $this->emarsysEventMapping->create()
                ->getCollection()
                ->addFieldToFilter('magento_event_id', $magentoEventsCollection->getData()[0]['id']);
            $emarsysEventPlaceholderMappingColl = $this->emarsysEventPlaceholderMappingFactory->create()
                ->getCollection()
                ->addFieldToFilter('event_mapping_id', $emarsysEventMappingColl->getData()[0]['id']);

            if (count($emarsysEventPlaceholderMappingColl->getData())) {
                foreach ($emarsysEventPlaceholderMappingColl->getData() as $_emarsysEventPlaceholderMappingColl) {
                    $headerPlaceholderArray[$_emarsysEventPlaceholderMappingColl['emarsys_placeholder_name']] = $_emarsysEventPlaceholderMappingColl['magento_placeholder_name'];
                }
            }

            return $headerPlaceholderArray;
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                htmlentities($e->getMessage()),
                $store_id,
                'emarsysHeaderPlaceholders'
            );
        }

        return $headerPlaceholderArray;
    }

    /**
     * @param $mapping_id
     * @param $store_id
     * @return array
     */
    public function emarsysFooterPlaceholders($mapping_id, $store_id)
    {
        $footerPlaceholderArray = [];
        try {
            $magentoEventsCollection = $this->magentoEventsCollection->create()
                ->addFieldToFilter('config_path', 'design/email/footer_template');
            $emarsysEventMappingColl = $this->emarsysEventMapping->create()
                ->getCollection()
                ->addFieldToFilter('magento_event_id', $magentoEventsCollection->getData()[0]['id']);
            $emarsysEventPlaceholderMappingColl = $this->emarsysEventPlaceholderMappingFactory->create()
                ->getCollection()
                ->addFieldToFilter('event_mapping_id', $emarsysEventMappingColl->getData()[0]['id']);

            if (count($emarsysEventPlaceholderMappingColl->getData())) {
                foreach ($emarsysEventPlaceholderMappingColl->getData() as $_emarsysEventPlaceholderMappingColl) {
                    $footerPlaceholderArray[$_emarsysEventPlaceholderMappingColl['emarsys_placeholder_name']] = $_emarsysEventPlaceholderMappingColl['magento_placeholder_name'];
                }
            }
            return $footerPlaceholderArray;
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                htmlentities($e->getMessage()),
                $store_id,
                'emarsysFooterPlaceholders'
            );
        }

        return $footerPlaceholderArray;
    }

    /**
     * @param $websiteId
     * @return string
     */
    public function getCheckSmartInsight($websiteId)
    {
        $smartInsight = false;

        if ($websiteId) {
            if ($this->scopeConfigInterface->getValue(self::XPATH_SMARTINSIGHT_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES, $websiteId)) {
                $smartInsight = true;
            }
        } else {
            if ($this->scopeConfigInterface->getValue(self::XPATH_SMARTINSIGHT_ENABLED)) {
                $smartInsight = true;
            };
        }

        return $smartInsight;
    }

    /**
     * @param $websiteId
     * @return string
     */
    public function getRealTimeSync($websiteId)
    {
        return 'Enable';
    }

    /**
     * @param $websiteId
     * @return string
     */
    public function getEmarsysConnectionSetting($websiteId)
    {
        $result = false;

        if ($websiteId) {
            if ($this->scopeConfigInterface->getValue(self::XPATH_EMARSYS_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES, $websiteId)) {
                $result = true;
            }
        } else {
            if ($this->scopeConfigInterface->getValue(self::XPATH_EMARSYS_ENABLED)) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * @param $templateId
     * @param $storeScope
     * @return string
     */
    public function getMagentoEventId($templateId, $storeScope)
    {
        $event_id = "";
        try {
            $magentoEventsCollection = $this->magentoEventsCollection->create();
            foreach ($magentoEventsCollection as $magentoEvent) {
                if ($this->scopeConfigInterface->getValue($magentoEvent->getConfigPath(), $storeScope) == $templateId) {
                    $event_id = $magentoEvent->getId();
                }
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                htmlentities($e->getMessage()),
                $this->storeManager->getStore()->getId(),
                'getMagentoEventId'
            );
        }

        return $event_id;
    }

    /**
     * @param $magentoEventId
     * @return string
     */
    public function getEmarsysEventMappingId($magentoEventId, $storeId = null)
    {
        if (is_null($storeId)) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $emarsysEventMappingId = "";
        try {
            $emarsysEventsMappingCollection = $this->emarsysEventMapping->create()
                ->getCollection()
                ->addFieldToFilter('store_id', $storeId)
                ->addFieldToFilter('magento_event_id', $magentoEventId);

            if (count($emarsysEventsMappingCollection->getData())) {
                $emarsysEventMappingId = $emarsysEventsMappingCollection->getData()[0]['id'];
            }

            return $emarsysEventMappingId;
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                htmlentities($e->getMessage()),
                $this->storeManager->getStore()->getId(),
                'getEmarsysEventMappingId'
            );
        }

        return $emarsysEventMappingId;
    }

    /**
     * @param $magentoEventId
     * @return string
     */
    public function getEmarsysEventApiId($magentoEventId, $storeId = null)
    {
        if (is_null($storeId)) {
            $storeId = $this->storeManager->getStore()->getId();
        }
        $emarsysEventApiId = "";
        try {
            $emarsysEventsMappingCollection = $this->emarsysEventMapping->create()
                ->getCollection()
                ->addFieldToFilter('store_id', $storeId)
                ->addFieldToFilter('magento_event_id', $magentoEventId);

            $emarsysEventsColl = $this->emarsysEventsModelFactory->create()
                ->getCollection()
                ->addFieldToFilter('id', $emarsysEventsMappingCollection->getData()[0]['emarsys_event_id']);

            if (count($emarsysEventsColl->getData())) {
                $emarsysEventApiId = $emarsysEventsColl->getData()[0]['event_id'];
            }

            return $emarsysEventApiId;
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                htmlentities($e->getMessage()),
                $this->storeManager->getStore()->getId(),
                'getEmarsysEventApiId'
            );
        }

        return $emarsysEventApiId;
    }

    /**
     * @return \Magento\Email\Model\Template
     */
    protected function getTemplateInstance()
    {
        return $this->templateFactory->create();
    }

    /**
     * @param $emarsysEventMappingId
     * @return array
     */
    public function getPlaceHolders($emarsysEventMappingId)
    {
        $placeHolders = [];
        try {
            $emarsysPlaceholderCollection = $this->emarsysEventPlaceholderMappingFactory->create()
                ->getCollection()
                ->addFieldToFilter('event_mapping_id', $emarsysEventMappingId);

            $variables = [];

            if (count($emarsysPlaceholderCollection->getData())) {
                foreach ($emarsysPlaceholderCollection->getData() as $value) {
                    $variables[$value['emarsys_placeholder_name']] = $value['magento_placeholder_name'];
                }

                return $variables;
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                htmlentities($e->getMessage()),
                $this->storeManager->getStore()->getId(),
                'getPlaceHolders'
            );
        }

        return $placeHolders;
    }

    /**
     * @param $path
     * @param $storeCode
     * @param $storeId
     * @return mixed
     */
    public function getConfigValue($path, $storeCode, $storeId)
    {
        if ($storeCode == "default") {
            return $this->scopeConfigInterface->getValue($path, 'default', 0);
        } else {
            return $this->scopeConfigInterface->getValue($path, $storeCode, $storeId);
        }
    }

    /**
     * Technical support email for error logs
     *
     * @return array
     */
    public function logErrorSenderEmail()
    {
        $data = [
            'name' => 'Technical Support',
            'email' => 'support@emarsys.com'
        ];
        return $data;
    }

    /**
     * @return int
     */
    public function getFirstStoreId()
    {
        $firstStoreId = $this->storeManager->getStore()->getId();
        $listOfStores = $this->storeCollection->create()->addFieldToFilter('store_id', ['neq' => 0]);

        if ($listOfStores) {
            $firstStoreId = $listOfStores->getFirstItem()->getStoreId();
        }

        return $firstStoreId;
    }

    /**
     * @param $websiteId
     * @return int
     */
    public function getFirstStoreIdOfWebsite($websiteId)
    {
        $firstStoreId = $this->storeManager->getStore()->getId();
        $listOfStores = $this->storeCollection->create()
            ->addFieldToFilter('website_id', $websiteId)
            ->addFieldToFilter('store_id', ['neq' => 0]);

        if ($listOfStores) {
            $firstStoreId = $listOfStores->getFirstItem()->getStoreId();
        }

        return $firstStoreId;
    }

    /**
     * @param $templateId
     * @param $storeScope
     * @return array
     */
    public function getMagentoEventIdAndPath($templateId, $storeScope, $storeId = null)
    {
        $event_id = "";
        $configPath = '';
        try {
            $magentoEventsCollection = $this->magentoEventsCollection->create();

            foreach ($magentoEventsCollection as $magentoEvent) {
                if ($this->scopeConfigInterface->getValue($magentoEvent->getConfigPath(), $storeScope, $storeId) == $templateId) {
                    $event_id = $magentoEvent->getId();
                    $configPath = $magentoEvent->getConfigPath();
                }
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                htmlentities($e->getMessage()),
                $this->storeManager->getStore()->getId(),
                'getMagentoEventIdAndPath'
            );
        }

        return [$event_id, $configPath];
    }

    /**
     * @param string $dateTime
     * @return \DateTime|string
     */
    public function getDateTimeInLocalTimezone($dateTime = '')
    {

        $toTimezone = $this->timezone->getConfigTimezone();
        $returnDateTime = $this->timezone->date($dateTime);
        $returnDateTime->setTimezone(new \DateTimeZone($toTimezone));
        $returnDateTime = $returnDateTime->format('Y-m-d H:i:s');

        return $returnDateTime;
    }

    /**
     * Checks whether emarsys is enabled or not.
     * @param null $websiteId
     * @return boolean
     */
    public function isEmarsysEnabled($websiteId = null)
    {
        $emarsysEnabled = false;

        if ($websiteId) {
            $emarsysUserName = $this->scopeConfigInterface->getValue(
                self::XPATH_EMARSYS_API_USER,
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES,
                $websiteId
            );
            $emarsysPassword = $this->scopeConfigInterface->getValue(
                self::XPATH_EMARSYS_API_PASS,
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES,
                $websiteId
            );
            $emarsysFlag = $this->scopeConfigInterface->getValue(
                self::XPATH_EMARSYS_ENABLED,
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES,
                $websiteId
            );

            if ($emarsysUserName && $emarsysPassword && $emarsysFlag) {
                $emarsysEnabled = true;
            }
        } else {
            $emarsysUserName = $this->scopeConfigInterface->getValue(self::XPATH_EMARSYS_API_USER);
            $emarsysPassword = $this->scopeConfigInterface->getValue(self::XPATH_EMARSYS_API_PASS);
            $emarsysFlag = $this->scopeConfigInterface->getValue(self::XPATH_EMARSYS_ENABLED);

            if ($emarsysUserName && $emarsysPassword && $emarsysFlag) {
                $emarsysEnabled = true;
            }
        }

        return $emarsysEnabled;
    }

    /**
     * Checks whether contacts synchronization is enabled or not.
     *
     * @param null $websiteId
     * @return bool
     */
    public function isContactsSynchronizationEnable($websiteId = null)
    {
        $contactsSynchronization = false;

        if ($this->isEmarsysEnabled($websiteId)) {
            if ($websiteId) {
                $contactsSynchronization = $this->scopeConfigInterface->getValue(
                    self::XPATH_EMARSYS_ENABLE_CONTACT_FEED,
                    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES,
                    $websiteId
                );
            } else {
                $contactsSynchronization = $this->scopeConfigInterface->getValue(self::XPATH_EMARSYS_ENABLE_CONTACT_FEED);
            }
        }

        return (bool)$contactsSynchronization;
    }

    /**
     * @return mixed
     */
    public function getEmarsysVersion()
    {
        return $this->moduleListInterface->getOne(self::MODULE_NAME)['setup_version'];
    }

    /**
     * @param $subscriber
     */
    public function realtimeTimeBasedOptinSync($subscriber)
    {
        try {
            $fieldId = $this->customerResourceModel->getKeyId(self::OPT_IN, $subscriber->getStoreId());

            $payload = [
                'key_id' => $this->customerResourceModel->getKeyId(self::CUSTOMER_EMAIL, $subscriber->getStoreId()),
                'key_value' => $subscriber->getSubscriberEmail(),
                'field_id' => $fieldId,
            ];

            $this->getEmarsysAPIDetails($subscriber->getStoreId());
            $response = $this->getClient()->get('contact/last_change', $payload);

            if (isset($response['data']['time'])) {
                $emarsysTime = $response['data']['time'];
                $emarsysOptinChangeTime = $this->convertToUtc($emarsysTime);
                $magentoOptinChangeTime = $subscriber->getChangeStatusAt();

                if (isset($response['data']['current_value'])) {
                    $emarsysOptinValue = $response['data']['current_value'];
                }
                $magentoOptinValue = $subscriber->getSubscriberStatus();

                if ((($emarsysOptinChangeTime == $magentoOptinChangeTime) || ($emarsysOptinChangeTime >= $magentoOptinChangeTime))
                    && $emarsysOptinValue != $magentoOptinValue
                ) {
                    if ($emarsysOptinValue == 1) {
                        $statusToBeChanged = \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED;
                    } elseif ($emarsysOptinValue == 2) {
                        $statusToBeChanged = \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED;
                    } else {
                        $statusToBeChanged = \Magento\Newsletter\Model\Subscriber::STATUS_UNCONFIRMED;
                    }
                    if (!in_array($magentoOptinValue, [\Magento\Newsletter\Model\Subscriber::STATUS_NOT_ACTIVE, \Magento\Newsletter\Model\Subscriber::STATUS_UNCONFIRMED])
                        && !in_array($statusToBeChanged, [\Magento\Newsletter\Model\Subscriber::STATUS_NOT_ACTIVE, \Magento\Newsletter\Model\Subscriber::STATUS_UNCONFIRMED]
                        )) {
                        $subscriber->setSubscriberStatus($statusToBeChanged)
                            ->setEmarsysNoExport(true)
                            ->save();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->addErrorLog(
                htmlentities($e->getMessage()),
                $subscriber->getStoreId(),
                'realtimeTimeBasedOptinSync'
            );
        }
    }

    /**
     * @param $emarsysTime
     * @return string
     */
    public function convertToUtc($emarsysTime)
    {
        try {
            $emarsysDate = \DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $emarsysTime,
                new \DateTimeZone('Europe/Vienna')
            );
            $acst_date = clone $emarsysDate; // we don't want PHP's default pass object by reference here
            $acst_date->setTimeZone(new \DateTimeZone('UTC'));

            return $emarsysOptinChangeTime = $acst_date->format('Y-m-d H:i:s');  // UTC:  2011-04-27 2:exit;

        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                htmlentities($e->getMessage()),
                $this->storeManager->getStore()->getId(),
                'convertToUtc'
            );
        }
    }

    /**
     * Time based optin sync for backgourd type
     *
     * @param $subscriberIdsArray
     * @param $storeId
     */
    public function backgroudTimeBasedOptinSync($subscriberIdsArray, $storeId)
    {
        try {
            $logsArray['job_code'] = 'Backgroud Time Based Optin Sync';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'Backgroud Time Based Optin Sync';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['store_id'] = $storeId;
            $logId = $this->logHelper->manualLogs($logsArray);

            $store = $this->storeManager->getStore($storeId);

            $fieldId = $this->customerResourceModel->getKeyId(self::OPT_IN, $storeId);
            $emailKey = $this->customerResourceModel->getKeyId(self::CUSTOMER_EMAIL, $storeId);
            $key = '';

            $subscribersCollection = $this->newsLetterCollectionFactory->create()
                ->addFieldToFilter('subscriber_id', ['in' => $subscriberIdsArray]);
            $magLastModifiedStatus = [];
            foreach ($subscribersCollection as $_subscriber) {
                $magLastModifiedStatus[$_subscriber->getSubscriberEmail() . $key] = [
                    'change_status_at' => $_subscriber->getChangeStatusAt(),
                    'subscriber_status' => $_subscriber->getSubscriberStatus(),
                    'subscriber_id' => $_subscriber->getId(),
                ];
            }

            $payload = [
                'keyId' => $emailKey,
                'keyValues' => array_keys($magLastModifiedStatus),
                'fieldId' => $fieldId,
            ];

            $this->getEmarsysAPIDetails($storeId);
            $response = $this->getClient()->post('contact/last_change', $payload);

            if (isset($response['data']['result'])) {
                $emarsysSubscribers = $response['data']['result'];
                foreach ($emarsysSubscribers as $emarsysSubscriberKey => $emarsysSubscriberValue) {
                    $magentoLastUpdatedTime = $magLastModifiedStatus[$emarsysSubscriberKey]['change_status_at'];
                    $magentoSubscriptionStatus = $magLastModifiedStatus[$emarsysSubscriberKey]['subscriber_status'];
                    $currentEmarsysSubcsriptionStatus = $emarsysSubscriberValue['current_value'];
                    $emarsysLastUpdateTime = $this->convertToUtc($emarsysSubscriberValue['time']);
                    $subscriberId = $magLastModifiedStatus[$emarsysSubscriberKey]['subscriber_id'];

                    if ($currentEmarsysSubcsriptionStatus != $magentoSubscriptionStatus) {
                        if ($emarsysLastUpdateTime >= $magentoLastUpdatedTime) {
                            if ($currentEmarsysSubcsriptionStatus == 1) {
                                $statusToBeChanged = \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED;
                            } elseif ($currentEmarsysSubcsriptionStatus == 2) {
                                $statusToBeChanged = \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED;
                            } else {
                                $statusToBeChanged = \Magento\Newsletter\Model\Subscriber::STATUS_NOT_ACTIVE;
                            }

                            $emarsysSubscriber = $this->subscriber->load($subscriberId);

                            $emarsysSubscriber->setSubscriberStatus($statusToBeChanged)
                                ->setEmarsysNoExport(true)
                                ->save();

                            $logsArray['id'] = $logId;
                            $logsArray['emarsys_info'] = 'Backgroud Time Based Optin Sync Success';
                            $logsArray['description'] = $emarsysSubscriber->getSubscriberId() . " => Optin Updated to " . $statusToBeChanged;
                            $logsArray['action'] = 'Backgroud Time Based Optin Sync';
                            $logsArray['message_type'] = 'Success';
                            $logsArray['log_action'] = 'True';
                            $logsArray['website_id'] = $store->getWebsiteId();
                            $this->logHelper->logs($logsArray);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->addErrorLog(
                htmlentities($e->getMessage()),
                $storeId,
                'backgroudTimeBasedOptinSync'
            );
        }
    }

    /**
     * @param array $websiteIds
     * @param bool $isTimeBased
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importSubscriptionUpdates(array $websiteIds, $isTimeBased = false)
    {
        try {
            $logsArray['job_code'] = 'Sync contact Export';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'Running requestSubscriptionUpdates';
            $logsArray['description'] = 'Started Sync Contacts Subscription Data';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['website_id'] = current($websiteIds);
            $logsArray['store_id'] = $this->storeManager->getWebsite(current($websiteIds))->getDefaultStore()->getId();
            $logId = $this->logHelper->manualLogs($logsArray);
            $this->getEmarsysAPIDetails(current($websiteIds));

            if ($this->isEmarsysEnabled(current($websiteIds))) {
                $offset = 0;
                $limit = 100;
                do {
                    $exportId = $this->scopeConfigInterface->getValue(
                        'emarsys_suite2/storage/export_id',
                        \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                        current($websiteIds)
                    );

                    $apiCall = sprintf('export/%s/data/offset=%s&limit=%s', $exportId, $offset, $limit);
                    $message = "Request " . $apiCall;
                    $response = $this->getClient()->get($apiCall, [], false);
                    $message .= "\nResponse: " . (\Zend_Json::encode($response));

                    $this->logHelper->childLogs($logId, $message, current($websiteIds));
                    $offset += $limit;
                } while ($this->_processSubscriptionUpdates($response, $isTimeBased));
            }
        } catch (\Exception $e) {
            $this->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getWebsite(current($websiteIds))->getDefaultStore()->getId(),
                'importSubscriptionUpdates(helperData)'
            );
        }
    }

    /**
     * @param $contents
     * @param bool $isTimeBased
     * @return bool
     */
    protected function _processSubscriptionUpdates($contents, $isTimeBased = false)
    {
        if ($contents) {
            $lines = explode(PHP_EOL, $contents);
            $changedOptinArray = [];
            foreach ($lines as $line) {
                $changedOptinArray[] = str_getcsv($line);
            }

            if (!isset($changedOptinArray) || count($changedOptinArray) <= 1) {
                return false;
            }
            $subscriberIds = [];

            foreach ($changedOptinArray as $optIn) {
                if (isset($optIn['0']) && isset($optIn['1']) && is_numeric($optIn['0'])) {
                    $userId = $optIn['0'];
                    $userSubscriberId = $optIn['1'];
                    $model = $this->subscriberFactory->create()->load($userSubscriberId);

                    if ($userSubscriberId && $model->getId()) {
                        $subscriberIds[] = $model->getId();
                        $storeId = $model->getStoreId();
                    }
                }
            }
            if (count($subscriberIds) > 0 && $storeId) {
                $this->backgroudTimeBasedOptinSync($subscriberIds, $storeId);
            }

            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getBaseUrl()
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();

        return str_replace('index.php/', '', $baseUrl);
    }

    /**
     * Get Emarsys Latest Version Information
     *
     * @return mixed
     * @throws \Exception
     */
    public function getEmarsysLatestVersionInfo()
    {
        $apiUrl = $this->scopeConfigInterface->getValue('emarsys_settings/ftp_settings/apiurl', 'default', 0);
        $ch = curl_init();
        $timeout = 500;
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        $data = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \Exception(curl_errno($ch));
        } else {
            curl_close($ch);
            return $data;
        }
    }

    /**
     * Check FTP Connection
     *
     * @param $hostname
     * @param $username
     * @param $password
     * @param $port
     * @param $ftpSsl
     * @param $passiveMode
     * @return bool
     */
    public function checkFtpConnection($hostname, $username, $password, $port, $ftpSsl, $passiveMode)
    {
        $result = false;
        try {
            if (!$username || !$password || !$hostname || !$port) {
                return $result;
            }

            if ($ftpSsl == 1) {
                $ftpConnId = @ftp_ssl_connect($hostname, $port);
            } else {
                $ftpConnId = @ftp_connect($hostname, $port);
            }
            if ($ftpConnId != '') {
                $ftpLogin = @ftp_login($ftpConnId, $username, $password);
                if ($ftpLogin == 1) {
                    $passsiveState = true;
                    if ($passiveMode == 1) {
                        $passsiveState = @ftp_pasv($ftpConnId, true);
                    }
                    if ($passsiveState) {
                        $result = true;
                        @ftp_close($ftpConnId);
                    }
                }
            }
        } catch (\Exception $e) {
            $storeId = $this->_storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'checkFtpConnection');
        }

        return $result;
    }

    /**
     * @param mixed $store
     * @return bool
     */
    public function checkFtpConnectionByStore($store)
    {
        $result = false;

        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore($store);
        try {
            $hostname = $store->getConfig(self::XPATH_EMARSYS_FTP_HOSTNAME);
            $port = $store->getConfig(self::XPATH_EMARSYS_FTP_PORT);
            $username = $store->getConfig(self::XPATH_EMARSYS_FTP_USERNAME);
            $password = $store->getConfig(self::XPATH_EMARSYS_FTP_PASSWORD);
            $ftpSsl = $store->getConfig(self::XPATH_EMARSYS_FTP_USEFTP_OVER_SSL);
            $passiveMode = $store->getConfig(self::XPATH_EMARSYS_FTP_USE_PASSIVE_MODE);

            if (!$username || !$password || !$hostname || !$port) {
                return $result;
            }
            $result = $this->ftp->open(
                array(
                    'host' => $hostname,
                    'port' => $port,
                    'user' => $username,
                    'password' => $password,
                    'ssl' => $ftpSsl ? true : false,
                    'passive' => $passiveMode ? true : false
                )
            );
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $store->getId(), 'checkFtpConnection');
        }

        return $result;
    }

    /**
     * @param mixed $store
     * @param string $filePath
     * @param string $filename
     * @return bool
     */
    public function moveFileToFtp($store, $filePath, $filename)
    {
        $result = false;
        if ($this->checkFtpConnectionByStore($store)) {
            $result = $this->ftp->write($filename, $filePath);
            $this->ftp->close();
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getSalesOrderCsvDefaultHeader($store = 0)
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore($store);

        $emailAsIdentifierStatus = (bool)$store->getConfig(self::XPATH_SMARTINSIGHT_EXPORTUSING_EMAILIDENTIFIER);
        if ($emailAsIdentifierStatus) {
            return ['order', 'timestamp', 'email', 'item', 'price', 'quantity'];
        } else {
            return ['order', 'timestamp', 'customer', 'item', 'price', 'quantity'];
        }
    }

    /**
     * @param $folderName
     * @return bool
     */
    public function checkAndCreateFolder($folderName)
    {
        if ($this->filesystemIoFile->checkAndCreateFolder($folderName)) {
            if ($this->filesystemIoFile->chmod($folderName, 0775, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $folderName
     * @return string
     */
    public function getEmarsysMediaDirectoryPath($folderName)
    {
        return $this->directoryList->getPath(DirectoryList::MEDIA) . '/emarsys/' . $folderName;
    }

    /**
     * @param $scope
     * @param $websiteId
     * @return array|bool
     */
    public function collectWebDavCredentials($scope, $websiteId)
    {
        //webDav credentials from admin configurations
        $webDavUrl = $this->customerResourceModel->getDataFromCoreConfig(self::XPATH_WEBDAV_URL, $scope, $websiteId);
        $webDavUser = $this->customerResourceModel->getDataFromCoreConfig(self::XPATH_WEBDAV_USER, $scope, $websiteId);
        $webDavPass = $this->customerResourceModel->getDataFromCoreConfig(self::XPATH_WEBDAV_PASSWORD, $scope, $websiteId);

        if ($webDavUrl != '' && $webDavUser != '' && $webDavPass != '') {
            return [
                'baseUri' => $webDavUrl,
                'userName' => $webDavUser,
                'password' => $webDavPass,
                'proxy' => '',
            ];
        }

        return false;
    }

    /**
     * @param $entity
     * @param $storeCode
     * @return string
     */
    public function getCustomerCsvFileName($entity, $storeCode)
    {
        if ($entity == \Magento\Customer\Model\Customer::ENTITY) {
            $entityCode = 'customers_';
        } else {
            $entityCode = 'subscribers_';
        }

        return $entityCode . $this->date->date('YmdHis', time()) . "_" . $storeCode . ".csv";
    }

    /**
     * @param $outputFile
     * @return string
     */
    public function getContactCsvGenerationPath($outputFile)
    {
        $path = BP . '/var/' . $outputFile;

        return $path;
    }

    /**
     * @return bool
     */
    public function isOptinForcedConfirmationEnabled($websiteId)
    {
        return (bool)$this->scopeConfig->getValue(
            self::XPATH_OPTIN_FORCED_CONFIRMATION,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );
    }

    /**
     * @param $messages
     * @param $storeId
     * @param $info
     */
    public function addErrorLog($messages, $storeId, $info)
    {
        return $this->emarsysLogs->addErrorLog($messages, $storeId, $info);
    }
}
