<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Api;

use Emarsys\Emarsys\Model\ResourceModel\Customer as customerResourceModel;
use Emarsys\Emarsys\Helper\Data as EmarsysHelperData;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Emarsys\Emarsys\Helper\Logs;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Emarsys\Emarsys\Model\QueueFactory;
use Emarsys\Emarsys\Model\Logs as EmarsysModelLogs;
use Magento\Framework\Registry as Registry;
use Emarsys\Emarsys\Logger\Logger as EmarsysLogger;
use Magento\Newsletter\Model\SubscriberFactory;
use Emarsys\Emarsys\Helper\Cron as EmarsysCronHelper;

/**
 * Class Subscriber
 * @package Emarsys\Emarsys\Model\Api
 */
class Subscriber
{
    const CUSTOMER_EMAIL = 'Email';

    const SUBSCRIBER_ID = 'Magento Subscriber ID';

    const CUSTOMER_UNIQUE_ID = 'Magento Customer Unique ID';

    const BATCH_SIZE = 1000;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var
     */
    protected $customerResourceModel;

    /**
     * @var
     */
    protected $dataHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var MessageManagerInterface
     */
    protected $messageManager;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var QueueFactory
     */
    protected $queueModel;

    /**
     * @var EmarsysModelLogs
     */
    protected $emarsysLogs;

    /**
     * @var
     */
    protected $_registry;

    /**
     * @var EmarsysLogger
     */
    protected $emarsysLogger;

    /**
     * @var SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * Subscriber constructor.
     * @param Api $api
     * @param customerResourceModel $customerResourceModel
     * @param DateTime $date
     * @param Logs $logsHelper
     * @param EmarsysHelperData $dataHelper
     * @param StoreManagerInterface $storeManager
     * @param MessageManagerInterface $messageManager
     * @param ResourceConnection $resourceConnection
     * @param QueueFactory $queueModel
     * @param EmarsysModelLogs $emarsysLogs
     * @param Registry $registry
     * @param EmarsysLogger $emarsysLogger
     * @param SubscriberFactory $subscriberFactory
     */
    public function __construct(
        Api $api,
        customerResourceModel $customerResourceModel,
        DateTime $date,
        Logs $logsHelper,
        EmarsysHelperData $dataHelper,
        StoreManagerInterface $storeManager,
        MessageManagerInterface $messageManager,
        ResourceConnection $resourceConnection,
        QueueFactory $queueModel,
        EmarsysModelLogs $emarsysLogs,
        Registry $registry,
        EmarsysLogger $emarsysLogger,
        SubscriberFactory $subscriberFactory
    ) {
        $this->api = $api;
        $this->dataHelper = $dataHelper;
        $this->customerResourceModel = $customerResourceModel;
        $this->logsHelper = $logsHelper;
        $this->date = $date;
        $this->storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->resourceConnection = $resourceConnection;
        $this->queueModel = $queueModel;
        $this->emarsysLogs = $emarsysLogs;
        $this->_registry = $registry;
        $this->emarsysLogger = $emarsysLogger;
        $this->subscriberFactory = $subscriberFactory;
    }

    /**
     * @param $subscribeId
     * @param $storeId
     * @param null $frontendFlag
     * @param null $pageHandle
     * @return array
     */
    public function syncSubscriber($subscribeId, $storeId, $frontendFlag = null, $pageHandle = null, $websiteId = 1, $cron = 0, $subscriberEmailChangeFlag = false)
    {
        $_customerId = $this->_registry->registry('NewCustomerIdSet');

        $logsArray['job_code'] = 'subscriber';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = 'Subscriber is sync to Emarsys';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Manual';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $storeId;
        $logsArray['website_id'] = $websiteId;
        $logId = $this->logsHelper->manualLogs($logsArray);

        $this->api->setWebsiteId($websiteId);

        $objCustomer = $this->subscriberFactory->create()->load($subscribeId);
        $arrCustomer = $objCustomer->getData();

        $buildRequest = [];
        $keyField = $this->dataHelper->getContactUniqueField($websiteId);
        if ($subscriberEmailChangeFlag){
            $keyField = 'unique_id';
        }
        if ($keyField == 'email') {
            $buildRequest['key_id'] = $this->customerResourceModel->getKeyId('Email', $storeId);
            $buildRequest[$buildRequest['key_id']] = $arrCustomer['subscriber_email'];
        } elseif ($keyField == 'magento_id' && isset($_customerId)) {
            $buildRequest['key_id'] = $this->customerResourceModel->getKeyId('Magento Customer ID', $storeId);
            $buildRequest[$buildRequest['key_id']] = $_customerId;
        } elseif ($keyField == 'magento_id') {
            $buildRequest['key_id'] = $this->customerResourceModel->getKeyId('Magento Subscriber ID', $storeId);
            $buildRequest[$buildRequest['key_id']] = $subscribeId;
        } elseif ($keyField == 'unique_id') {
            $buildRequest['key_id'] = $this->customerResourceModel->getKeyId('Magento Customer Unique ID', $storeId);
            $buildRequest[$buildRequest['key_id']] = $arrCustomer['subscriber_email'] . "#" . $websiteId . "#" . $storeId;
        }

        $keyId = $this->customerResourceModel->getKeyId('Email', $storeId);
        $buildRequest[$keyId] = $arrCustomer['subscriber_email'];

        $keyId = $this->customerResourceModel->getKeyId('Magento Subscriber ID', $storeId);
        $buildRequest[$keyId] = $subscribeId;

        $keyId = $this->customerResourceModel->getKeyId('Magento Customer Unique ID', $storeId);
        $buildRequest[$keyId] = $arrCustomer['subscriber_email'] . "#" . $websiteId . "#" . $storeId;

        // Query to get opt-in Id in emarsys from magento table
        $optInEmarsysId = $this->customerResourceModel->getEmarsysFieldId('Opt-In', $storeId);

        $buildRequest[$optInEmarsysId] =  $objCustomer->getSubscriberStatus();
        if ($buildRequest[$optInEmarsysId] != 1) {
            $buildRequest[$optInEmarsysId] = 2;
        }

        $errorMsg = 0;
        if ((count($buildRequest) > 0) && (isset($buildRequest['key_id']))) {
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Send subscriber to Emarsys';
            $logsArray['action'] = 'Magento to Emarsys';
            $logsArray['message_type'] = 'Success';
            $logsArray['description'] = 'PUT ' . " contact/?create_if_not_exists=1 " . json_encode($buildRequest, JSON_PRETTY_PRINT);
            $logsArray['log_action'] = 'sync';
            $this->logsHelper->logs($logsArray);

            $optInResult = $this->api->sendRequest('PUT', 'contact/?create_if_not_exists=1', $buildRequest);

            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Create subscriber in Emarsys';
            $logsArray['action'] = 'Synced to Emarsys';
            $res = 'PUT ' . " contact/?create_if_not_exists=1 " . json_encode($optInResult, JSON_PRETTY_PRINT);
            if ($optInResult['status'] == '200') {
                $logsArray['message_type'] = 'Success';
                $logsArray['description'] = "Created subscriber '" . $objCustomer->getEmail() . "' in Emarsys succcessfully " . $res;
            } else {
                $this->dataHelper->syncFail($subscribeId, $websiteId, $storeId, $cron, 2);
                $logsArray['message_type'] = 'Error';
                $logsArray['description'] = $objCustomer->getEmail() . " - " . $optInResult['body']['replyText'] . $res;
                $errorMsg = 1;
            }
            $logsArray['log_action'] = 'sync';
            $this->logsHelper->logs($logsArray);
        }

        /**
         * Logs for Sync completed with / without Error
         */
        $logsArray['id'] = $logId;
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        if ($errorMsg == 1) {
            $logsArray['status'] = 'error';
            $logsArray['messages'] = 'Error in creating subscriber !!!';
        } else {
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Created subscriber in Emarsys';
        }
        $this->logsHelper->manualLogsUpdate($logsArray);

        if ($frontendFlag != '') {
            $responseData = [
                'apiResponseStatus' => $optInResult['status']
            ];
            return $responseData;
        }
    }

    /**
     * Sync Multiple Subscribers record to Emarsys
     * @param $exportMode
     * @param $params
     * @param null $logId
     * @return bool
     */
    public function syncMultipleSubscriber($exportMode, $params, $logId = null)
    {
        $storeId = $params['storeId'];
        $store = $this->storeManager->getStore($storeId);
        $websiteId = $store->getWebsiteId();

        //initial logging of the process
        $logsArray['job_code'] = 'subscriber';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = 'Subscriber is sync to Emarsys';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Manual';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $storeId;
        $logsArray['website_id'] = $websiteId;
        if (is_null($logId)) {
            $logId = $this->logsHelper->manualLogs($logsArray, 1);
        }
        $logsArray['id'] = $logId;
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['log_action'] = 'sync';
        $logsArray['action'] = 'contact sync';
        $errorStatus = true;

        //subscriber export starts
        $logsArray['emarsys_info'] = __('Subscriber Export Started');
        $logsArray['description'] = __('Subscriber Export Started for Store ID : %1', $storeId);
        $logsArray['message_type'] = 'Success';
        $this->logsHelper->logs($logsArray);

        //prepare subscribers data
        $subscriberData = $this->prepareSubscribersInfo($storeId, $exportMode);

        if (!empty($subscriberData)) {
            //Subscribers data present

            //create chunks for easy data sync
            $subscriberChunks = array_chunk($subscriberData, self::BATCH_SIZE);
            foreach ($subscriberChunks as $subscriberChunk) {
                //prepare subscribers payload
                $buildRequest = $this->prepareSubscribersPayload($subscriberChunk, $storeId);

                if (count($buildRequest) > 0) {
                    $logsArray['emarsys_info'] = 'Send subscriber to Emarsys';
                    $logsArray['action'] = 'Magento to Emarsys';
                    $logsArray['message_type'] = 'Success';
                    $logsArray['description'] = 'PUT ' . " contact/?create_if_not_exists=1 " . json_encode($buildRequest, JSON_PRETTY_PRINT);
                    $this->logsHelper->logs($logsArray);
                    $this->emarsysLogger->info($logsArray['description']);

                    //Send request to Emarsys with Customer's Data
                    $this->api->setWebsiteId($websiteId);
                    $result = $this->api->createContactInEmarsys($buildRequest);

                    $logsArray['emarsys_info'] = 'Create subscriber in Emarsys';
                    $logsArray['action'] = 'Synced to Emarsys';
                    $res = 'PUT ' . " contact/?create_if_not_exists=1 " . json_encode($result, JSON_PRETTY_PRINT);

                    if ($result['status'] == '200') {
                        //successful response from emarsys
                        $errorStatus = false;
                        $logsArray['message_type'] = 'Success';
                        $logsArray['description'] = "Created subscribers in Emarsys succcessfully " . $res;

                        if ($exportMode == EmarsysCronHelper::CRON_JOB_CUSTOMER_SYNC_QUEUE) {
                            //clean subscribers from the queue
                            $subscriberIdKey = $this->customerResourceModel->getKeyId(self::SUBSCRIBER_ID, $storeId);
                            foreach ($subscriberChunk as $value) {
                                $this->queueModel->create()->load($value[$subscriberIdKey], 'entity_id')->delete();
                            }
                        }
                        $this->messageManager->addSuccessMessage(__('Created subscribers in Emarsys succcessfully!!'));
                    } else {
                        //error response from emarsys
                        $logsArray['message_type'] = 'Error';
                        $logsArray['description'] = $result['body']['replyText'] . $res;
                        $this->messageManager->addErrorMessage(
                            __('Subscriber export have an error. Please check emarsys logs for more details!!')
                        );
                    }
                    $this->logsHelper->logs($logsArray);
                    $this->emarsysLogger->info($logsArray['description']);
                }
            }
        } else {
            //no Subscribers data found
            $logsArray['emarsys_info'] = 'No Subscribers Data Found.';
            $logsArray['action'] = 'Magento to Emarsys';
            $logsArray['message_type'] = 'Error';
            $logsArray['description'] = __('No Subscribers found for the store with store id %1.', $storeId);
            $this->logsHelper->logs($logsArray);
            $this->messageManager->addErrorMessage(
                __('No Subscribers found for the store with store id %1.', $storeId)
            );
        }

        if ($errorStatus) {
            $logsArray['status'] = 'error';
            $logsArray['messages'] = 'Error in creating subscriber !!!';
        } else {
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Created subscriber in Emarsys';
        }
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        $this->logsHelper->manualLogsUpdate($logsArray);

        return $errorStatus ? false : true;
    }

    /**
     * @param $storeId
     * @param $exportMode
     * @return array
     */
    public function prepareSubscribersInfo($storeId, $exportMode)
    {
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        $websiteStoreIds = [];
        $websiteStoreIds[] = $storeId;
        $subscriberData = [];

        $emailKey = $this->customerResourceModel->getKeyId(self::CUSTOMER_EMAIL, $storeId);
        $subscriberIdKey = $this->customerResourceModel->getKeyId(self::SUBSCRIBER_ID, $storeId);
        $uniqueIdKey = $this->customerResourceModel->getKeyId(self::CUSTOMER_UNIQUE_ID, $storeId);
        $optInEmarsysId = $this->customerResourceModel->getEmarsysFieldId('Opt-In', $storeId);

        if ($exportMode == EmarsysCronHelper::CRON_JOB_CUSTOMER_SYNC_QUEUE) {
            $newsLetSubTableName = $this->resourceConnection->getTableName('newsletter_subscriber');
            $queueCollection = $this->queueModel->create()->getCollection();
            $queueCollection->addFieldToSelect('entity_id');
            $queueCollection->addFieldToFilter('main_table.entity_type_id', 2);
            $queueCollection->addFieldToFilter('main_table.store_id', $storeId);
            $queueCollection->getSelect()->joinLeft(
                ['newsletter_subscriber' => $newsLetSubTableName],
                'main_table.entity_id = newsletter_subscriber.subscriber_id',
                ['subscriber_email', 'subscriber_confirm_code', 'subscriber_status']
            );

            if ($queueCollection->getData()) {
                $this->updateLastModifiedContacts($queueCollection, $storeId);
            }

            foreach ($queueCollection as $subscriber) {
                $values = [];
                $values[$emailKey] = $subscriber['subscriber_email'];
                $values[$subscriberIdKey] = $subscriber['entity_id'];
                $values[$uniqueIdKey] = $subscriber['subscriber_email'] . "#" . $websiteId . "#" . $storeId;

                if ($subscriber['subscriber_status'] != 1) {
                    $values[$optInEmarsysId] = '2';
                } else {
                    $values[$optInEmarsysId] = '1';
                }
                $subscriberData[] = $values;
            }
        } else {
            $subscriberCollection = $this->subscriberFactory->create()->getCollection()
                ->addFieldToFilter('store_id', $storeId);
            foreach ($subscriberCollection as $subscriber) {
                $values = [];
                $values[$emailKey] = $subscriber['subscriber_email'];
                $values[$subscriberIdKey] = $subscriber['subscriber_id'];
                $values[$uniqueIdKey] = $subscriber['subscriber_email'] . "#" . $websiteId . "#" . $storeId;

                if ($subscriber['subscriber_status'] != 1) {
                    $values[$optInEmarsysId] = '2';
                } else {
                    $values[$optInEmarsysId] = '1';
                }
                $subscriberData[] = $values;
            }
        }

        return $subscriberData;
    }

    /**
     * @param $subscriberData
     * @param $storeId
     * @return array
     */
    public function prepareSubscribersPayload($subscriberData, $storeId)
    {
        $store = $this->storeManager->getStore($storeId);
        $websiteId = $store->getWebsiteId();
        $buildRequest = [];
        switch ($this->dataHelper->getContactUniqueField($websiteId)) {
            case 'magento_id' :
                $buildRequest['key_id'] = $this->customerResourceModel->getKeyId(self::SUBSCRIBER_ID, $storeId);
                break;
            case 'unique_id' :
                $buildRequest['key_id'] = $this->customerResourceModel->getKeyId(self::CUSTOMER_UNIQUE_ID, $storeId);
                break;
            default :
                $buildRequest['key_id'] = $this->customerResourceModel->getKeyId(self::CUSTOMER_EMAIL, $storeId);
                break;
        }
        $buildRequest['contacts'] = $subscriberData;

        return $buildRequest;
    }

    /**
     * @param $collection
     * @param $storeId
     */
    public function updateLastModifiedContacts($collection, $storeId)
    {
        try {
            $currentPageNumber = 1;
            $collection->setPageSize(self::BATCH_SIZE);
            $lastPageNumber = $collection->getLastPageNumber();

            while ($currentPageNumber <= $lastPageNumber) {
                if ($currentPageNumber != 1) {
                    $collection->setPageSize(self::BATCH_SIZE)
                        ->setCurPage($currentPageNumber);
                }
                if (count($collection)) {
                    $subscriberIds = $collection->getColumnValues('entity_id');
                    if (count($subscriberIds)) {
                        $this->dataHelper->backgroudTimeBasedOptinSync($subscriberIds, $storeId);
                    }
                }
                $currentPageNumber = $currentPageNumber + 1;
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'updateLastModifiedContacts($collection,$storeId)');
        }
    }
}
