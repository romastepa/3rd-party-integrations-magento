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
        $errorStatus = true;
        $magentoTemplateId = '';

        $storeId = $this->storeManager->getStore()->getId();
        try {
            $_emarsysPlaceholdersData = $message->getEmarsysData();

            if (isset($_emarsysPlaceholdersData['store_id'])) {
                $storeId = $_emarsysPlaceholdersData['store_id'];
            }
            if (isset($_emarsysPlaceholdersData['templateId'])) {
                $magentoTemplateId = $_emarsysPlaceholdersData['templateId'];
            }

            $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();

            $logsArray['job_code'] = 'transactional_mail';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'Transactional Email Started (' . $magentoTemplateId . ')';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['store_id'] = $storeId;
            $logsArray['website_id'] = $websiteId;
            $logId = $this->logs->manualLogs($logsArray, 1);
            $logsArray['id'] = $logId;

            //check emarsys module status
            if (!$this->emarsysHelper->isEmarsysEnabled($websiteId)) {
                //emarsys module disabled
                $logsArray['emarsys_info'] = 'Transactional Mails';
                $logsArray['description'] = 'Emarsys is disabled. Email sent from Magento for store id: '
                    . $storeId . '(' . $magentoTemplateId . ')';
                $logsArray['action'] = 'Mail Sent';
                $logsArray['message_type'] = 'Notice';
                $logsArray['log_action'] = 'True';
                $this->logs->logs($logsArray);

                return $errorStatus;
            }

            //check emarsys transaction emails enable
            if (!$this->checkTransactionalMailEnabled($websiteId)) {
                //emarsys transaction emails disable
                $logsArray['emarsys_info'] = 'Transactional Mails';
                $logsArray['description'] = 'Emarsys Transaction Email Either Disabled or Some Extension Conflict '
                    . '(if enabled). Email sent from Magento for store id: ' . $storeId . ' (' . $magentoTemplateId . ')';
                $logsArray['action'] = 'Mail Sent';
                $logsArray['message_type'] = 'Notice';
                $logsArray['log_action'] = 'True';
                $this->logs->logs($logsArray);
                $logsArray['status'] = 'success';
                $logsArray['messages'] = __('Transactional Email Completed. %1', $magentoTemplateId);
                $this->logs->manualLogs($logsArray);

                return $errorStatus;
            }

            $emarsysPlaceholdersData = [];
            $emarsysApiEventID = '';
            $magentoEventId = '';

            if (isset($_emarsysPlaceholdersData['emarsysPlaceholders'])) {
                $emarsysPlaceholdersData = $_emarsysPlaceholdersData['emarsysPlaceholders'];
            }
            if (isset($_emarsysPlaceholdersData['emarsysEventId'])) {
                $emarsysApiEventID = $_emarsysPlaceholdersData['emarsysEventId'];
            }

            if (isset($_emarsysPlaceholdersData['magentoEventId'])) {
                $magentoEventId = $_emarsysPlaceholdersData['magentoEventId'];
            }

            if (empty($magentoEventId)) {
                //emarsys transaction emails disable
                $logsArray['emarsys_info'] = 'Transactional Mails';
                $logsArray['description'] = 'Email sent from Magento for store id: ' . $storeId . ' (' . $magentoTemplateId . ')';
                $logsArray['action'] = 'Mail Sent';
                $logsArray['message_type'] = 'Notice';
                $logsArray['log_action'] = 'True';
                $this->logs->logs($logsArray);
                $logsArray['status'] = 'success';
                $logsArray['messages'] = __('Transactional Email Completed. %1', $magentoTemplateId);
                $this->logs->manualLogs($logsArray);

                return $errorStatus;
            }

            //check emarsys event id present
            if (empty($emarsysApiEventID)) {
                //no mapping found for emarsys event
                $logsArray['emarsys_info'] = 'Transactional Mails';
                $logsArray['description'] = 'No Mapping Found for the Email Template: ' . $magentoTemplateId
                    . ' Email sent from Store: ' . $storeId
                    . '(' . \Zend_Json::encode($_emarsysPlaceholdersData) . ')';
                $logsArray['action'] = 'Mail Sent';
                $logsArray['message_type'] = 'Error';
                $logsArray['log_action'] = 'True';
                $this->logs->logs($logsArray);
                $logsArray['status'] = 'error';
                $logsArray['messages'] = __('Transactional Email Completed. %1', $magentoTemplateId);
                $this->logs->manualLogs($logsArray);

                return $errorStatus;
            }
            //mapping found for event
            $this->api->setWebsiteId($websiteId);
            /** @var \Zend\Mail\Message $zendMessage */
            if (is_callable([$message, 'getZendMessage'])
                && method_exists($message, 'getZendMessage')
                && $zendMessage = $message->getZendMessage()
            ) {
                /** @var \Zend\Mail\AddressList $addressList */
                $addressList = $zendMessage->getTo();
                $externalId = $addressList->current()->getEmail();
            } else {
                $externalId = $message->getRecipients()[0];
            }

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
                $contactSyncReq = 'PUT ' . " contact/?create_if_not_exists=1 " . \Zend_Json::encode($response, JSON_PRETTY_PRINT);
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
                $logsArray['emarsys_info'] = 'Transactional Mail request';
                $logsArray['description'] = 'POST ' . " event/$emarsysApiEventID/trigger: " . \Zend_Json::encode($arrCustomerData);
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
                    $errorStatus = false;
                    $logsArray['emarsys_info'] = 'Transactional Mail response';
                    $logsArray['description'] = \Zend_Json::encode($emailEventResponse);
                    $logsArray['action'] = 'Mail Sent';
                    $logsArray['message_type'] = 'Success';
                    $logsArray['log_action'] = 'True';
                    $this->logs->logs($logsArray);
                } else {
                    //email send event failed
                    $logsArray['emarsys_info'] = 'Transactional Mails';
                    $logsArray['description'] = 'Failed to send email from emarsys. Emarsys Event ID :' . $emarsysApiEventID
                        . ', Due to this error, Email Sent From Magento for Store Id: ' . $storeId . ' (' . $magentoTemplateId . ')'
                        . ', Response : ' .  \Zend_Json::encode($emailEventResponse);
                    $logsArray['action'] = 'Mail Sent Fail';
                    $logsArray['message_type'] = 'Error';
                    $logsArray['log_action'] = 'False';
                    $this->logs->logs($logsArray);
                }
            } else {
                //failed to sync contact to emarsys
                $logsArray['emarsys_info'] = 'Transactional Mails';
                $logsArray['description'] = 'Failed to Sync Contact to Emarsys. Emarsys Event ID :' . $emarsysApiEventID
                    . ', Due to this error, Email Sent From Magento for Store Id: ' . $storeId . ' (' . $magentoTemplateId . ')'
                    . ' Request: ' . \Zend_Json::encode($buildRequest)
                    . '\n Response: ' . \Zend_Json::encode($response);
                $logsArray['action'] = 'Mail Sent Fail';
                $logsArray['message_type'] = 'Error';
                $logsArray['log_action'] = 'False';
                $this->logs->logs($logsArray);
            }
        } catch (\Exception $e) {
            //log exception
            $errorStatus = true;
            $logsArray['emarsys_info'] = 'Emarsys Transactional Email Error';
            $logsArray['description'] = $e->getMessage()
                . ". Due to this error, Email Sent From Magento for Store Id: " . $storeId . ' (' . $magentoTemplateId . ')';
            $logsArray['action'] = 'Mail Sending Fail';
            $logsArray['message_type'] = 'Error';
            $logsArray['log_action'] = 'Fail';
            $this->logs->logs($logsArray);
        }

        if ($errorStatus) {
            $logsArray['status'] = 'error';
            $logsArray['messages'] = __('Error while sending Transactional Email (%1), Email Sent From Magento', $magentoTemplateId);
        } else {
            $logsArray['status'] = 'success';
            $logsArray['messages'] = __('Transactional Email Completed. %1', $magentoTemplateId);
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
