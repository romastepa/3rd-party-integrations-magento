<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model;

use Magento\{
    Framework\Model\AbstractModel,
    Framework\Model\Context,
    Framework\Model\ResourceModel\AbstractResource,
    Framework\Registry,
    Framework\Data\Collection\AbstractDb,
    Framework\Stdlib\DateTime\DateTime,
    Framework\Mail\MessageInterface,
    Store\Model\StoreManagerInterface
};
use Emarsys\Emarsys\{
    Helper\Data as EmarsysHelperData,
    Helper\Logs as EmarsysLogsHelper,
    Model\ResourceModel\Customer as customerResourceModel,
    Model\Api\Api as EmarsysModelApiApi
};

/**
 * Class Transport
 * @package Emarsys\Emarsys\Model
 */
class SendEmail extends AbstractModel
{
    /**
     * @var EmarsysHelperData
     */
    protected $emarsysHelper;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var customerResourceModel
     */
    protected $customerResourceModel;

    /**
     * @var MessageInterface|\Zend_Mail
     */
    protected $_message;

    /**
     * @var EmarsysLogsHelper
     */
    protected $logs;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var EmarsysModelApiApi
     */
    protected $api;

    /**
     * @var EmarsyseventsFactory
     */
    protected $emarsyseventsFactory;

    /**
     * SendEmail constructor.
     * @param Context $context
     * @param Registry $registry
     * @param EmarsysHelperData $emarsysHelper
     * @param customerResourceModel $customerResourceModel
     * @param DateTime $date
     * @param MessageInterface $message
     * @param EmarsysLogsHelper $logs
     * @param StoreManagerInterface $storeManagerInterface
     * @param EmarsysModelApiApi $api
     * @param EmarsyseventsFactory $emarsyseventsFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        EmarsysHelperData $emarsysHelper,
        customerResourceModel $customerResourceModel,
        DateTime $date,
        MessageInterface $message,
        EmarsysLogsHelper $logs,
        StoreManagerInterface $storeManagerInterface,
        EmarsysModelApiApi $api,
        EmarsyseventsFactory $emarsyseventsFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->emarsysHelper = $emarsysHelper;
        $this->date = $date;
        $this->customerResourceModel = $customerResourceModel;
        $this->_message = $message;
        $this->logs = $logs;
        $this->storeManager = $storeManagerInterface;
        $this->api = $api;
        $this->emarsyseventsFactory = $emarsyseventsFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @param \Zend_Mail $message
     * @return bool
     * @throws \Exception
     */
    public function sendMail($message)
    {
        $errorStatus = false;
        $emarsysErrorStatus = false;
        $emarsysApiEventID = '';

        $storeId = $this->storeManager->getStore()->getId();
        try {
            $_emarsysPlaceholdersData = $message->getEmarsysData();

            if (isset($_emarsysPlaceholdersData['store_id'])) {
                $storeId = $_emarsysPlaceholdersData['store_id'];
            }
            $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();

            $logsArray['job_code'] = 'transactional_mail';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'Transactional Email Started';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['store_id'] = $storeId;
            $logsArray['website_id'] = $websiteId;
            $logId = $this->logs->manualLogs($logsArray, 1);
            $logsArray['id'] = $logId;

            //check emarsys module status
            if ($this->emarsysHelper->isEmarsysEnabled($websiteId)) {

                //check emarsys transaction emails enable
                if ($this->checkTransactionalMailEnabled($websiteId)) {
                    $emarsysPlaceholdersData = [];

                    if (is_array($_emarsysPlaceholdersData)) {
                        if (isset($_emarsysPlaceholdersData['emarsysPlaceholders'])) {
                            $emarsysPlaceholdersData = $_emarsysPlaceholdersData['emarsysPlaceholders'];
                        }
                        if (isset($_emarsysPlaceholdersData['emarsysEventId'])) {
                            $emarsysApiEventID = $_emarsysPlaceholdersData['emarsysEventId'];
                        }
                    }

                    //check emarsys event id present
                    if ($emarsysApiEventID != '') {
                        //mapping found for event
                        $this->api->setWebsiteId($websiteId);
                        $externalId = $message->getRecipients()[0];
                        $buildRequest = [];

                        $buildRequest['key_id'] = $this->customerResourceModel->getKeyId(EmarsysHelperData::CUSTOMER_EMAIL, $storeId);
                        $buildRequest[$buildRequest['key_id']] = $externalId;

                        $customerId = $this->customerResourceModel->checkCustomerExistsInMagento(
                            $externalId,
                            $websiteId
                        );

                        if (!empty($customerId)) {
                            $customerIdKey = $this->customerResourceModel->getKeyId(EmarsysHelperData::CUSTOMER_ID, $storeId);
                            $buildRequest[$customerIdKey] = $customerId;
                        }

                        $data = [
                            'email' => $externalId,
                            'store_id' => $storeId
                        ];
                        $subscribeId = $this->customerResourceModel->getSubscribeIdFromEmail($data);

                        if (!empty($subscribeId)) {
                            $subscriberIdKey = $this->customerResourceModel->getKeyId(EmarsysHelperData::SUBSCRIBER_ID, $storeId);
                            $buildRequest[$subscriberIdKey] = $subscribeId;
                        }

                        $emailKey = $this->customerResourceModel->getKeyId(EmarsysHelperData::CUSTOMER_EMAIL, $storeId);
                        $buildRequest['key_id'] = $emailKey;
                        $buildRequest[$emailKey] = $externalId;

                        //log information that is about to send for contact sync
                        $contactSyncReq = 'PUT ' . " contact/?create_if_not_exists=1 " . json_encode($buildRequest, JSON_PRETTY_PRINT);
                        $logsArray['emarsys_info'] = 'Send Contact to Emarsys';
                        $logsArray['description'] = $contactSyncReq;
                        $logsArray['action'] = 'Magento to Emarsys';
                        $logsArray['message_type'] = 'Success';
                        $logsArray['log_action'] = 'sync';
                        $this->logs->logs($logsArray);

                        //sync contact to emarsys
                        $response = $this->api->sendRequest(
                            'PUT',
                            'contact/?create_if_not_exists=1',
                            $buildRequest
                        );
                        if (($response['status'] == 200) || ($response['status'] == 400 && $response['body']['replyCode'] == 2009)) {
                            //contact synced to emarsys successfully

                            //log contact sync response
                            $contactSyncReq = 'PUT ' . " contact/?create_if_not_exists=1 " . json_encode($response, JSON_PRETTY_PRINT);
                            $logsArray['emarsys_info'] = 'Emarsys response from customer creation';
                            $logsArray['description'] = 'Created customer ' . $externalId . ' in Emarsys succcessfully ' . $contactSyncReq;
                            $logsArray['action'] = 'Synced to Emarsys';
                            $logsArray['message_type'] = 'Success';
                            $logsArray['log_action'] = 'True';
                            $this->logs->logs($logsArray);

                            $arrCustomerData = [
                                "key_id" => $buildRequest['key_id'],
                                "external_id" => $buildRequest[$buildRequest['key_id']],
                                "data" => $emarsysPlaceholdersData
                            ];

                            //log information that is about to send for email sync
                            $emailtriggerReq = 'POST ' . " event/$emarsysApiEventID/trigger: " . json_encode($arrCustomerData, JSON_PRETTY_PRINT);
                            $logsArray['emarsys_info'] = 'Transactional Mail request';
                            $logsArray['description'] = $emailtriggerReq;
                            $logsArray['action'] = 'Mail Sent';
                            $logsArray['message_type'] = 'Success';
                            $logsArray['log_action'] = 'True';
                            $this->logs->logs($logsArray);

                            //trigger email event
                            $emailEventResponse = $this->api->sendRequest(
                                'POST',
                                "event/$emarsysApiEventID/trigger",
                                $arrCustomerData
                            );

                            //check email event's response status
                            if ($emailEventResponse['status'] == 200) {
                                //email send successfully
                                $logsArray['emarsys_info'] = 'Transactional Mail response';
                                $logsArray['description'] = print_r($emailEventResponse, true);
                                $logsArray['action'] = 'Mail Sent';
                                $logsArray['message_type'] = 'Success';
                                $logsArray['log_action'] = 'True';
                                $this->logs->logs($logsArray);
                            } else {
                                //email send event failed
                                $emarsysErrorStatus = true;
                                $logsArray['emarsys_info'] = 'Transactional Mails';
                                $logsArray['description'] = 'Failed to send email from emarsys. Emarsys Event ID :' . $emarsysApiEventID . ', Store Id : ' . $storeId . ', Response : ' . print_r($emailEventResponse, true);
                                $logsArray['action'] = 'Mail Sent Fail';
                                $logsArray['message_type'] = 'Error';
                                $logsArray['log_action'] = 'False';
                                $this->logs->logs($logsArray);
                            }
                        } else {
                            //failed to sync contact to emarsys
                            $logsArray['emarsys_info'] = 'Transactional Mails';
                            $logsArray['description'] = 'Failed to Sync Contact to Emarsys. Emarsys Event ID :' . $emarsysApiEventID . ', Store Id : ' . $storeId . ' Request: ' . print_r($buildRequest, true) . '\n Response: ' . print_r($response, true);
                            $logsArray['action'] = 'Mail Sent Fail';
                            $logsArray['message_type'] = 'Error';
                            $logsArray['log_action'] = 'False';
                            $this->logs->logs($logsArray);
                        }
                    } else {
                        //no mapping found for emarsys event
                        $errorStatus = true;
                        $logsArray['emarsys_info'] = 'Transactional Mails';
                        $logsArray['description'] = 'No Mapping Found for the Emarsys Event ID : ' . $emarsysApiEventID
                            . '. Email sent from Magento for the store .' . $storeId
                            . '(' . \Zend_Json::encode($_emarsysPlaceholdersData) . ')';
                        $logsArray['action'] = 'Mail Sent';
                        $logsArray['message_type'] = 'Error';
                        $logsArray['log_action'] = 'True';
                        $this->logs->logs($logsArray);
                    }
                } else {
                    //emarsys transaction emails disable
                    $errorStatus = true;
                    $logsArray['emarsys_info'] = 'Transactional Mails';
                    $logsArray['description'] = 'Emarsys Transaction Email Either Disabled or Some Extension Conflict (if enabled). Email sent from Magento for store id ' . $storeId;
                    $logsArray['action'] = 'Mail Sent';
                    $logsArray['message_type'] = 'Success';
                    $logsArray['log_action'] = 'True';
                    $this->logs->logs($logsArray);
                }
            } else {
                //emarsys module disabled
                $errorStatus = true;
                $logsArray['emarsys_info'] = 'Transactional Mails';
                $logsArray['description'] = 'Emarsys is not enabled. Email sent from Magento for Store Id ' . $storeId;
                $logsArray['action'] = 'Mail Sent';
                $logsArray['message_type'] = 'Error';
                $logsArray['log_action'] = 'True';
                $this->logs->logs($logsArray);
            }
        } catch (\Exception $e) {
            //log exception
            $errorStatus = true;
            $logsArray['emarsys_info'] = 'Emarsys Transactional Email Error';
            $logsArray['description'] = $e->getMessage() . " Due to this error, Email Sent From Magento for Store Id " . $storeId;
            $logsArray['action'] = 'Mail Sending Fail';
            $logsArray['message_type'] = 'Error';
            $logsArray['log_action'] = 'Fail';
            $this->logs->logs($logsArray);
        }

        $emarsysEvent = '';
        $emarsysEventCollection = $this->emarsyseventsFactory->create()->getCollection()
            ->addFieldToFilter('store_id', ['eq' => $storeId])
            ->addFieldToFilter('event_id', ['eq' => $emarsysApiEventID]);
        if ($emarsysEventCollection->getSize()) {
            $emarsysEvent = $emarsysEventCollection->getFirstItem()->getEmarsysEvent();
            $emarsysEvent = 'Template Name: ' . ucwords(str_replace('_', ' ', $emarsysEvent));
        }

        if ($errorStatus || $emarsysErrorStatus) {
            $logsArray['status'] = 'error';
            $logsArray['messages'] = __('Error while sending Transactional Email. %1', $emarsysEvent);
        } else {
            $logsArray['status'] = 'success';
            $logsArray['messages'] = __('Transactional Email Completed. %1', $emarsysEvent);
        }

        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        $this->logs->manualLogs($logsArray);

        return $errorStatus;
    }

    /**
     * @param $websiteId
     * @return mixed
     */
    public function checkTransactionalMailEnabled($websiteId)
    {
        $status = $this->emarsysHelper->getConfigValue(
            'transaction_mail/transactionmail/enable_customer', 'websites', $websiteId
        );

        return $status;
    }
}
