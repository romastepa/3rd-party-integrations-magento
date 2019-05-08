<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Cron;

use Emarsys\Emarsys\Helper\Data as EmarsysDataHelper;
use Emarsys\Emarsys\Model\ResourceModel\Customer as EmarsysCustomerResourceModel;
use Emarsys\Emarsys\Model\Api as EmarsysModelApi;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Request\Http;
use Emarsys\Emarsys\Helper\Logs;
use Magento\Config\Model\ResourceModel\Config;
use Emarsys\Emarsys\Model\Logs as EmarsysModelLogs;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Framework\App\Cache\TypeListInterface;

/**
 * Class SyncContactsSubscriptionData
 * @package Emarsys\Emarsys\Cron
 */
class SyncContactsSubscriptionData
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var Logs
     */
    protected $logsHelper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var EmarsysModelLogs
     */
    protected $emarsysLogs;

    /**
     * @var EmarsysDataHelper
     */
    protected $emarsysDataHelper;

    /**
     * @var EmarsysCustomerResourceModel
     */
    protected $customerResourceModel;

    /**
     * @var EmarsysModelApi
     */
    protected $modelApi;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Config
     */
    protected $resourceConfig;

    /**
     * @var TypeListInterface
     */
    protected $_cacheTypeList;

    /**
     * SyncContactsSubscriptionData constructor.
     * @param StoreManagerInterface $storeManager
     * @param DateTime $date
     * @param Logs $logsHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param EmarsysModelLogs $emarsysLogs
     * @param EmarsysDataHelper $emarsysDataHelper
     * @param EmarsysCustomerResourceModel $customerResourceModel
     * @param EmarsysModelApi $modelApi
     * @param Http $request
     * @param Registry $registry
     * @param Config $resourceConfig
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        DateTime $date,
        Logs $logsHelper,
        ScopeConfigInterface $scopeConfig,
        EmarsysModelLogs $emarsysLogs,
        EmarsysDataHelper $emarsysDataHelper,
        EmarsysCustomerResourceModel $customerResourceModel,
        EmarsysModelApi $modelApi,
        Http $request,
        Registry $registry,
        Config $resourceConfig,
        TypeListInterface $cacheTypeList
    ) {
        $this->storeManager = $storeManager;
        $this->date = $date;
        $this->logsHelper = $logsHelper;
        $this->scopeConfig = $scopeConfig;
        $this->emarsysLogs = $emarsysLogs;
        $this->emarsysDataHelper = $emarsysDataHelper;
        $this->customerResourceModel = $customerResourceModel;
        $this->modelApi = $modelApi;
        $this->request = $request;
        $this->registry = $registry;
        $this->resourceConfig = $resourceConfig;
        $this->_cacheTypeList = $cacheTypeList;
    }

    /**
     * import emsrays optin changes to magento once in a day.
     */
    public function execute()
    {
        $queue = [];
        $websites = $this->storeManager->getWebsites();
        foreach ($websites as $website) {
            $logsArray['job_code'] = 'Sync contact Export';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = __('Running Sync Contacts Subscription Data');
            $logsArray['description'] = __('Started Sync Contacts Subscription Data');
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['website_id'] = $website->getWebsiteId();
            $logsArray['store_id'] = $website->getDefaultStore()->getId();
            $logId = $this->logsHelper->manualLogs($logsArray);
            try {
                $enable = $this->scopeConfig->getValue('emarsys_settings/emarsys_setting/enable', 'websites', $website->getWebsiteId());
                if ($enable) {
                    $emarsysUserName = $this->scopeConfig->getValue('emarsys_settings/emarsys_setting/emarsys_api_username', 'websites', $website->getWebsiteId());
                    if (!array_key_exists($emarsysUserName, $queue)) {
                        $queue[$emarsysUserName] = [];
                    }
                    $queue[$emarsysUserName][] = $website->getWebsiteId();
                }
            } catch (\Exception $e) {
                $this->emarsysLogs->addErrorLog($e->getMessage(), $this->storeManager->getStore()->getId(), 'syncContactsSubscriptionData(helper/data)');
            }
        }
        if (!empty($queue)) {
            foreach ($queue as $websiteId) {
                $this->requestSubscriptionUpdates($websiteId, true);
            }
        }
    }

    /**
     * API Request to get updates
     */
    public function requestSubscriptionUpdates(array $websiteId, $isTimeBased = false)
    {
        try {
            $logsArray['job_code'] = 'Sync contact Export';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = __('Running requestSubscriptionUpdates');
            $logsArray['description'] = __('Started Sync Contacts Subscription Data');
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['website_id'] = current($websiteId);
            $logsArray['store_id'] = $this->storeManager->getWebsite(current($websiteId))->getDefaultStore()->getId();
            $logId = $this->logsHelper->manualLogs($logsArray);
            $logsArray['id'] = $logId;
            $logsArray['action'] = 'synced to emarsys';
            $logsArray['log_action'] = 'sync';
            $logsArray['emarsys_info'] = 'subscription information';
            $client = $this->emarsysDataHelper->getClient();

            $dt = (new \Zend_Date());
            if ($isTimeBased) {
                $timeRange = [$dt->subHour(1)->toString('YYYY-MM-dd'), $dt->addHour(1)->toString('YYYY-MM-dd')];
            }
            $storeId = $this->storeManager->getWebsite(current($websiteId))->getDefaultStore()->getId();
            $key_id = $this->customerResourceModel->getEmarsysFieldId('Magento Subscriber ID', $storeId);
            $optinFiledId = $this->customerResourceModel->getEmarsysFieldId('Opt-In', $storeId);
            $payload = [
                'distribution_method' => 'local',
                'origin' => 'all',
                'origin_id' => '0',
                'contact_fields' => [$key_id, $optinFiledId],
                'add_field_names_header' => 1,
                'time_range' => $timeRange,
                'notification_url' => $this->getExportsNotificationUrl($websiteId, $isTimeBased, $storeId)
            ];

            $logsArray['description'] = $this->getExportsNotificationUrl($websiteId, $isTimeBased, $storeId);
            $logsArray['message_type'] = 'Success';
            $this->logsHelper->logs($logsArray);

            $this->emarsysDataHelper->getEmarsysAPIDetails($storeId);
            $this->emarsysDataHelper->getClient();
            $response = $this->modelApi->post('contact/getchanges', $payload);

            $logsArray['description'] = print_r($response,true);
            $logsArray['message_type'] = 'Success';
            $this->logsHelper->logs($logsArray);

            if (isset($response['data']['id'])) {
                $this->setValue('export_id', $response['data']['id'], current($websiteId));
                $logsArray['description'] = $response['data']['id'];
                $logsArray['message_type'] = 'Success';
                $this->logsHelper->logs($logsArray);
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $storeId,
                'SyncContactsSubscriptionData::requestSubscriptionUpdates(helper/data)');
        }
    }

    /**
     * @param int $websiteId
     * @param bool $isTimeBased
     * @param $storeId
     * @return string
     */
    public function getExportsNotificationUrl($websiteId = 0, $isTimeBased = false, $storeId)
    {
        try {
            $oldEntryPoint = $this->registry->registry('custom_entry_point');
            if ($oldEntryPoint) {
                $this->registry->unregister('custom_entry_point');
            }
            $this->registry->register('custom_entry_point', 'index.php');

            if ($isTimeBased) {
                $url = $this->storeManager->getStore($storeId)->getBaseUrl() . 'emarsys/index/sync?_store=' . $storeId. '&secret=' . $this->scopeConfig->getValue('contacts_synchronization/emarsys_emarsys/notification_secret_key') . '&website_ids=' .implode(',', $websiteId) . '&timebased=1';
            }
            $this->registry->unregister('custom_entry_point');

            if ($oldEntryPoint) {
                $this->registry->register('custom_entry_point', $oldEntryPoint);
            }

            return $url;
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $storeId,
                'SyncContactsSubscriptionData::getExportsNotificationUrl(helper/data)');
        }
    }

    /**
     * @param $key
     * @param $value
     * @param $websiteId
     */
    public function setValue($key, $value, $websiteId)
    {
        try {
            $this->resourceConfig->saveConfig('emarsys_suite2/storage/' . $key, $value, 'websites', $websiteId);
            $this->_cacheTypeList->cleanType('config');
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'SyncContactsSubscriptionData::setValue(helper/data)');
        }
    }
}
