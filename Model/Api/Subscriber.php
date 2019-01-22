<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Api;

use Emarsys\Emarsys\{
    Model\ResourceModel\Customer as customerResourceModel,
    Model\QueueFactory,
    Helper\Data as EmarsysHelper,
    Helper\Logs,
    Helper\Cron as EmarsysCronHelper,
    Logger\Logger as EmarsysLogger
};
use Magento\{
    Framework\Stdlib\DateTime\DateTime,
    Framework\Message\ManagerInterface as MessageManagerInterface,
    Framework\App\ResourceConnection,
    Framework\Registry as Registry,
    Store\Model\StoreManagerInterface,
    Newsletter\Model\SubscriberFactory,
    Newsletter\Helper\Data as NewsletterHelperData
};

/**
 * Class Subscriber
 * @package Emarsys\Emarsys\Model\Api
 */
class Subscriber
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var customerResourceModel
     */
    protected $customerResourceModel;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

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
     * @var Registry
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
     * @var Logs
     */
    protected $logsHelper;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var NewsletterHelperData
     */
    protected $newsletterHelperData;

    /**
     * Subscriber constructor.
     * @param Api $api
     * @param customerResourceModel $customerResourceModel
     * @param DateTime $date
     * @param Logs $logsHelper
     * @param EmarsysHelper $emarsysHelper
     * @param StoreManagerInterface $storeManager
     * @param MessageManagerInterface $messageManager
     * @param ResourceConnection $resourceConnection
     * @param QueueFactory $queueModel
     * @param Registry $registry
     * @param EmarsysLogger $emarsysLogger
     * @param SubscriberFactory $subscriberFactory
     * @param NewsletterHelperData $newsletterHelperData
     */
    public function __construct(
        Api $api,
        customerResourceModel $customerResourceModel,
        DateTime $date,
        Logs $logsHelper,
        EmarsysHelper $emarsysHelper,
        StoreManagerInterface $storeManager,
        MessageManagerInterface $messageManager,
        ResourceConnection $resourceConnection,
        QueueFactory $queueModel,
        Registry $registry,
        EmarsysLogger $emarsysLogger,
        SubscriberFactory $subscriberFactory,
        NewsletterHelperData $newsletterHelperData
    ) {
        $this->api = $api;
        $this->emarsysHelper = $emarsysHelper;
        $this->customerResourceModel = $customerResourceModel;
        $this->logsHelper = $logsHelper;
        $this->date = $date;
        $this->storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->resourceConnection = $resourceConnection;
        $this->queueModel = $queueModel;
        $this->_registry = $registry;
        $this->emarsysLogger = $emarsysLogger;
        $this->subscriberFactory = $subscriberFactory;
        $this->newsletterHelperData = $newsletterHelperData;
    }

    /**
     * @param $subscribeId
     * @param $storeId
     * @param null $frontendFlag
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function syncSubscriber(
        $subscribeId,
        $storeId,
        $frontendFlag = null
    ) {
        $store = $this->storeManager->getStore($storeId);
        $websiteId = $store->getWebsiteId();

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

        $objSubscriber = $this->subscriberFactory->create()->load($subscribeId);

        $buildRequest = [];
        $buildRequest['key_id'] = $this->customerResourceModel->getKeyId(EmarsysHelper::CUSTOMER_EMAIL, $storeId);
        $emailKey = $this->customerResourceModel->getKeyId(EmarsysHelper::CUSTOMER_EMAIL, $storeId);
        if ($emailKey && $objSubscriber->getSubscriberEmail()) {
            $buildRequest[$emailKey] = $objSubscriber->getSubscriberEmail();
        }

        $subscriberIdKey = $this->customerResourceModel->getKeyId(EmarsysHelper::SUBSCRIBER_ID, $storeId);
        if ($subscriberIdKey && $objSubscriber->getId()) {
            $buildRequest[$subscriberIdKey] = $objSubscriber->getId();
        }

        $customerIdKey = $this->customerResourceModel->getKeyId(EmarsysHelper::CUSTOMER_ID, $storeId);
        if ($customerIdKey && $objSubscriber->getCustomerId()) {
            $buildRequest[$customerIdKey] = $objSubscriber->getCustomerId();
        }

        // Query to get opt-in Id in emarsys from magento table
        $optInEmarsysId = $this->customerResourceModel->getKeyId(EmarsysHelper::OPT_IN, $storeId);
        $subscriberStatus = $objSubscriber->getSubscriberStatus();

        if (in_array($subscriberStatus, [\Magento\Newsletter\Model\Subscriber::STATUS_NOT_ACTIVE, \Magento\Newsletter\Model\Subscriber::STATUS_UNCONFIRMED])) {
            $buildRequest[$optInEmarsysId] = '';
        } elseif ($subscriberStatus ==  \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED) {
            $buildRequest[$optInEmarsysId] = 1;
        } else {
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

            $optInResult = $this->api->createContactInEmarsys($buildRequest);

            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Create subscriber in Emarsys';
            $logsArray['action'] = 'Synced to Emarsys';
            $res = ' [PUT] ' . " contact/?create_if_not_exists=1 " . json_encode($optInResult, JSON_PRETTY_PRINT)
                . ' [confirmation url] ' . $this->newsletterHelperData->getConfirmationUrl($objSubscriber)
                . ' [unsubscribe url] ' . $this->newsletterHelperData->getUnsubscribeUrl($objSubscriber)
            ;
            if ($optInResult['status'] == '200') {
                $logsArray['message_type'] = 'Success';
                $logsArray['description'] = "Created subscriber '" . $objSubscriber->getSubscriberEmail() . "' in Emarsys succcessfully " . $res;
            } else {
                $this->emarsysHelper->syncFail($subscribeId, $websiteId, $storeId, 0, 2);
                $logsArray['message_type'] = 'Error';
                $logsArray['description'] = $objSubscriber->getSubscriberEmail() . " - " . $optInResult['body']['replyText'] . $res;
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
     * @throws \Exception
     */
    public function syncMultipleSubscriber($exportMode, $params, $logId = null)
    {
        $storeId = $params['storeId'];
        $store = $this->storeManager->getStore($storeId);
        $websiteId = $store->getWebsiteId();

        if (!$this->emarsysHelper->isContactsSynchronizationEnable($websiteId)) {
            return;
        }

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
        $emailKey = $this->customerResourceModel->getKeyId(EmarsysHelper::CUSTOMER_EMAIL, $storeId);
        $subscriberIdKey = $this->customerResourceModel->getKeyId(EmarsysHelper::SUBSCRIBER_ID, $storeId);
        $customerIdKey = $this->customerResourceModel->getKeyId(EmarsysHelper::CUSTOMER_ID, $storeId);
        $optInEmarsysId = $this->customerResourceModel->getKeyId(EmarsysHelper::OPT_IN, $storeId);

        $subscriberData = $this->prepareSubscribersInfo(
            $storeId,
            $exportMode,
            $emailKey,
            $subscriberIdKey,
            $customerIdKey,
            $optInEmarsysId
        );

        if (!empty($subscriberData)) {
            //Subscribers data present

            //create chunks for easy data sync
            $subscriberChunks = array_chunk($subscriberData, EmarsysHelper::BATCH_SIZE);
            foreach ($subscriberChunks as $subscriberChunk) {
                //prepare subscribers payload
                $buildRequest = $this->prepareSubscribersPayload($subscriberChunk, $emailKey);

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
                            $subscriberIdKey = $this->customerResourceModel->getKeyId(EmarsysHelper::SUBSCRIBER_ID, $storeId);
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
            $logsArray['message_type'] = 'Success';
            $logsArray['description'] = __('No Subscribers found for the store with store id %1.', $storeId);
            $this->logsHelper->logs($logsArray);
            $this->messageManager->addErrorMessage(
                __('No Subscribers found for the store with store id %1.', $storeId)
            );
            $errorStatus = false;
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
     * @param $emailKey
     * @param $subscriberIdKey
     * @param $customerIdKey
     * @param $optInEmarsysId
     * @return array
     */
    public function prepareSubscribersInfo(
        $storeId,
        $exportMode,
        $emailKey,
        $subscriberIdKey,
        $customerIdKey,
        $optInEmarsysId
    ) {
        $websiteStoreIds = [];
        $websiteStoreIds[] = $storeId;
        $subscriberData = [];

        if ($exportMode == EmarsysCronHelper::CRON_JOB_CUSTOMER_SYNC_QUEUE) {
            $newsLetSubTableName = $this->resourceConnection->getTableName('newsletter_subscriber');
            $queueCollection = $this->queueModel->create()->getCollection();
            $queueCollection->addFieldToSelect('entity_id');
            $queueCollection->addFieldToFilter('main_table.entity_type_id', 2);
            $queueCollection->addFieldToFilter('main_table.store_id', $storeId);
            $queueCollection->getSelect()->joinLeft(
                ['newsletter_subscriber' => $newsLetSubTableName],
                'main_table.entity_id = newsletter_subscriber.subscriber_id',
                ['subscriber_email', 'subscriber_confirm_code', 'subscriber_status', 'customer_id']
            );

            $this->updateLastModifiedContacts($queueCollection, $storeId);

            foreach ($queueCollection as $subscriber) {
                $values = [];
                $values[$emailKey] = $subscriber->getSubscriberEmail();
                $values[$subscriberIdKey] = $subscriber->getEntityId();
                if ($subscriber->getCustomerId()) {
                    $values[$customerIdKey] = $subscriber->getCustomerId();
                }

                if ($subscriber['subscriber_status'] != 1) {
                    $values[$optInEmarsysId] = 2;
                } else {
                    $values[$optInEmarsysId] = 1;
                }
                $subscriberData[] = $values;
            }
        } else {
            $subscriberCollection = $this->subscriberFactory->create()->getCollection()
                ->addFieldToFilter('store_id', $storeId);
            foreach ($subscriberCollection as $subscriber) {
                $values = [];
                $values[$emailKey] = $subscriber->getSubscriberEmail();
                $values[$subscriberIdKey] = $subscriber->getEntityId();
                if ($subscriber->getCustomerId()) {
                    $values[$customerIdKey] = $subscriber->getCustomerId();
                }

                $subscriberStatus = $subscriber->getSubscriberStatus();

                if (in_array($subscriberStatus, [\Magento\Newsletter\Model\Subscriber::STATUS_NOT_ACTIVE, \Magento\Newsletter\Model\Subscriber::STATUS_UNCONFIRMED])) {
                    $values[$optInEmarsysId] = '';
                } elseif ($subscriberStatus ==  \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED) {
                    $values[$optInEmarsysId] = 1;
                } else {
                    $values[$optInEmarsysId] = 2;
                }

                $subscriberData[] = $values;
            }
        }

        return $subscriberData;
    }

    /**
     * @param $subscriberData
     * @param $keyId
     * @return array
     */
    public function prepareSubscribersPayload($subscriberData, $keyId)
    {
        $buildRequest = [];
        if ($keyId) {
            $buildRequest['key_id'] = $keyId;
            $buildRequest['contacts'] = $subscriberData;
        }

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
            $collection->setPageSize(EmarsysHelper::BATCH_SIZE);
            $lastPageNumber = $collection->getLastPageNumber();

            while ($currentPageNumber <= $lastPageNumber) {
                if ($currentPageNumber != 1) {
                    $collection->setPageSize(EmarsysHelper::BATCH_SIZE)
                        ->setCurPage($currentPageNumber);
                }
                if (count($collection)) {
                    $subscriberIds = $collection->getColumnValues('entity_id');
                    if (count($subscriberIds)) {
                        $this->emarsysHelper->backgroudTimeBasedOptinSync($subscriberIds, $storeId);
                    }
                }
                $currentPageNumber = $currentPageNumber + 1;
            }
        } catch (\Exception $e) {
            $this->emarsysHelper->addErrorLog($e->getMessage(), $storeId, 'updateLastModifiedContacts($collection,$storeId)');
        }
    }
}
