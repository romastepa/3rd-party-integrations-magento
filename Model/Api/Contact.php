<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Api;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Emarsys\Emarsys\Model\ResourceModel\Customer as customerResourceModel;
use Emarsys\Emarsys\Helper\Data as EmarsysHelperData;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Emarsys\Emarsys\Helper\Logs;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Emarsys\Emarsys\Helper\Country as EmarsysCountryHelper;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Model\QueueFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Emarsys\Emarsys\Helper\Cron as EmarsysCronHelper;
use Emarsys\Emarsys\Logger\Logger as EmarsysLogger;

/**
 * Class Contact
 * API class for Emarsys API wrappers
 *
 * @package Emarsys\Emarsys\Model\Api
 */
class Contact
{
    const BATCH_SIZE = 1000;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var
     */
    protected $customer;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var
     */
    protected $customerResourceModel;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var Logs
     */
    protected $logsHelper;

    /**
     * @var
     */
    protected $dataHelper;

    /**
     * @var CustomerCollection
     */
    protected $custColl;

    /**
     * @var EmarsysCountryHelper
     */
    protected $emarsysCountryHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var QueueFactory
     */
    protected $queueModel;

    /**
     * @var MessageManagerInterface
     */
    protected $messageManager;

    /**
     * @var EmarsysCronHelper
     */
    protected $cronHelper;

    /**
     * @var Subscriber
     */
    protected $subscriberApi;

    /**
     * @var EmarsysLogger
     */
    protected $emarsysLogger;

    /**
     * Contact constructor.
     * @param Api $api
     * @param Customer $customer
     * @param CustomerFactory $customerFactory
     * @param customerResourceModel $customerResourceModel
     * @param DateTime $date
     * @param Logs $logsHelper
     * @param EmarsysHelperData $dataHelper
     * @param CustomerCollection $customerColl
     * @param EmarsysCountryHelper $emarsysCountryHelper
     * @param StoreManagerInterface $storeManager
     * @param QueueFactory $queueModel
     * @param MessageManagerInterface $messageManager
     * @param EmarsysCronHelper $cronhelper
     * @param Subscriber $subscriberApi
     * @param EmarsysLogger $emarsysLogger
     */
    public function __construct(
        Api $api,
        Customer $customer,
        CustomerFactory $customerFactory,
        customerResourceModel $customerResourceModel,
        DateTime $date,
        Logs $logsHelper,
        EmarsysHelperData $dataHelper,
        CustomerCollection $customerColl,
        EmarsysCountryHelper $emarsysCountryHelper,
        StoreManagerInterface $storeManager,
        QueueFactory $queueModel,
        MessageManagerInterface $messageManager,
        EmarsysCronHelper $cronhelper,
        Subscriber $subscriberApi,
        EmarsysLogger $emarsysLogger
    ) {
        $this->api = $api;
        $this->customer = $customer;
        $this->customerFactory = $customerFactory;
        $this->customerResourceModel = $customerResourceModel;
        $this->date = $date;
        $this->logsHelper = $logsHelper;
        $this->dataHelper = $dataHelper;
        $this->custColl = $customerColl;
        $this->emarsysCountryHelper = $emarsysCountryHelper;
        $this->storeManager = $storeManager;
        $this->queueModel = $queueModel;
        $this->messageManager = $messageManager;
        $this->cronHelper = $cronhelper;
        $this->subscriberApi = $subscriberApi;
        $this->emarsysLogger = $emarsysLogger;
    }

    /**
     * @param $customerId
     * @param $websiteId
     * @param $storeId
     * @param int $cron
     */
    public function syncContact($customerId, $websiteId, $storeId, $cron = 0, $forceMagentoIDAsKeyID = false,
                                 $subscriberId = 0)
    {
        $objCustomer = $this->customer->load($customerId);
        $arrCustomer = $objCustomer->getData();

        $logsArray['job_code'] = 'customer';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = 'Customer is sync to Emarsys';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Manual';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $storeId;
        $logsArray['website_id'] = $websiteId;
        $logId = $this->logsHelper->manualLogs($logsArray);
        $this->api->setWebsiteId($websiteId);

        $buildRequest = [];
        $keyField = $this->dataHelper->getContactUniqueField($websiteId);

        if ($forceMagentoIDAsKeyID) {
            $keyField = 'unique_id';
        }
        if ($keyField == 'email') {
            $buildRequest['key_id'] = $this->customerResourceModel->getKeyId('Email', $storeId);
            $buildRequest[$buildRequest['key_id']] = $objCustomer->getEmail();
        } elseif ($keyField == 'magento_id') {
            if($subscriberId > 0){
                $buildRequest['key_id'] = $this->customerResourceModel->getKeyId('Magento Subscriber ID', $storeId);
                $buildRequest[$buildRequest['key_id']] = $subscriberId; // $subscriber->getId();
            } else {
                $buildRequest['key_id'] = $this->customerResourceModel->getKeyId('Magento Customer ID', $storeId);
                $buildRequest[$buildRequest['key_id']] = $customerId;
            }
        } elseif ($keyField == 'unique_id') {
            $buildRequest['key_id'] = $this->customerResourceModel->getKeyId('Magento Customer Unique ID', $storeId);
            $buildRequest[$buildRequest['key_id']] = $objCustomer->getEmail() . "#" . $websiteId . "#" . $storeId;
        }

        $keyId = $this->customerResourceModel->getKeyId('Email', $storeId);
        $buildRequest[$keyId] = $objCustomer->getEmail();

        $keyId = $this->customerResourceModel->getKeyId('Magento Customer ID', $storeId);
        $buildRequest[$keyId] = $customerId;

        $keyId = $this->customerResourceModel->getKeyId('Magento Customer Unique ID', $storeId);
        $buildRequest[$keyId] = $objCustomer->getEmail() . "#" . $websiteId . "#" . $storeId;

        $getEmarsysMappedFields = $this->customerResourceModel->fetchMappedFields($storeId);

        foreach ($getEmarsysMappedFields as $mappedField) {
            if (isset($arrCustomer[$mappedField['attribute_code']]) && $mappedField['emarsys_contact_field'] != 0) {
                if (!is_null($mappedField['source_model'])) {
                    if ($mappedField['frontend_input'] == 'multiselect') {
                    } else {
                        $optionId = $arrCustomer[$mappedField['attribute_code']];
                        /**
                         * Get Mapped Emarsys OptionId
                         */
                        $emarsysOptionId = $optionId;
                        if ($emarsysOptionId) {
                            $buildRequest[$mappedField['emarsys_contact_field']] = $emarsysOptionId;
                        }
                    }
                } else {
                    $buildRequest[$mappedField['emarsys_contact_field']] = $arrCustomer[$mappedField['attribute_code']];
                }
            }
        }

        //Fetch Customer's Mapped Address Attributes
        $customerMappedAddressAttributes = $this->getMappedCustomersAddressAttributes($objCustomer, $storeId);
        foreach ($customerMappedAddressAttributes as $key => $value) {
            $buildRequest[$key] = $value;
        }

        $errorMsg = 0;
        if (count($buildRequest) > 0) {
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Send Customer to Emarsys';
            $logsArray['action'] = 'Magento to Emarsys';
            $logsArray['message_type'] = 'Success';
            $logsArray['description'] = 'PUT ' . " contact/?create_if_not_exists=1 " . json_encode($buildRequest, JSON_PRETTY_PRINT);
            $logsArray['log_action'] = 'sync';
            $this->logsHelper->logs($logsArray);

            $result = $this->api->createContactInEmarsys($buildRequest);

            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Create customer in Emarsys';
            $logsArray['action'] = 'Synced to Emarsys';
            if ($result['status'] == '200') {
                $logsArray['message_type'] = 'Success';
                $res = 'PUT ' . " contact/?create_if_not_exists=1 " . json_encode($result, JSON_PRETTY_PRINT);
                $logsArray['description'] = 'Created customer ' . $objCustomer->getEmail() . ' in Emarsys succcessfully ' . $res;
                $this->dataHelper->syncSuccess($customerId, $websiteId, $storeId, $cron);
            } else {
                $this->dataHelper->syncFail($customerId, $websiteId, $storeId, $cron, 1);
                $logsArray['message_type'] = 'Error';
                $logsArray['description'] = $result['body']['replyText'];
                $errorMsg = 1;
            }
            $logsArray['log_action'] = 'sync';
            $this->logsHelper->logs($logsArray);
        } else {
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Create customer';
            $logsArray['action'] = 'Synced to Emarsys';
            $logsArray['message_type'] = 'error';
            $logsArray['description'] = 'Customer attribute mapping not working.';
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
            $logsArray['messages'] = 'Create customer in Emarsys with ERROR !!!';
        } else {
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Create customer in Emarsys';
        }
        $this->logsHelper->manualLogsUpdate($logsArray);
    }

    /**
     * @param $customer
     * @param $storeId
     * @param $mode
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function syncMultipleContacts($customer, $storeId, $mode)
    {
        $store = $this->storeManager->getStore($storeId);
        $websiteId = $store->getWebsiteId();
        $result = [];
        $result['error_status'] = true;

        $buildRequest = $this->prepareCustomerPayload($customer, $storeId);
        if (count($buildRequest) > 0) {
            //Send request to Emarsys with Customer's Data
            $this->api->setWebsiteId($websiteId);
            $result['api_response'] = $this->api->createContactInEmarsys($buildRequest);

            if ($result['api_response']['status'] == '200') {
                $result['error_status'] = false;

                if ($mode == EmarsysHelperData::ENTITY_EXPORT_MODE_AUTOMATIC) {
                    $custEmailIds = [];
                    foreach ($customer as $cust) {
                        $custEmailIds[] = $cust['3'];
                    }
                    $customerCollFail = $this->custColl;
                    $customerCollSuccess = $this->custColl;
                    $errIds = [];

                    if (isset($result['api_response']['body']['data']['errors'])) {
                        $errIds = array_keys($result['api_response']['body']['data']['errors']);
                        if (count($result['api_response']['body']['data']['errors'])) {
                            $dataDataColl = $customerCollFail->addAttributeToFilter('email', ["in" => $errIds]);
                            $custData = $dataDataColl->getData();
                            foreach ($custData as $custIndividualData) {
                                $this->dataHelper->syncFail(
                                    $custIndividualData['entity_id'],
                                    $custIndividualData['website_id'],
                                    $custIndividualData['store_id'],
                                    1,
                                    1
                                );
                            }
                        }
                    }

                    if (count($result['api_response']['body']['data']['ids'])) {
                        $successIds = array_diff($custEmailIds, $errIds);
                        $dataDataColl = $customerCollSuccess->addAttributeToFilter('email', ["in" => $successIds]);
                        $custData = $dataDataColl->getData();
                        foreach ($custData as $custIndividualData) {
                            $this->dataHelper->syncSuccess(
                                $custIndividualData['entity_id'],
                                $custIndividualData['website_id'],
                                $custIndividualData['store_id'],
                                1
                            );
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Fetch Customer's Mapped Address attributes values
     * @param $customer
     * @param $storeId
     * @return array
     */
    public function getMappedCustomersAddressAttributes($customer, $storeId)
    {
        $addressFields = [];
        $mappedAttributes = $this->customerResourceModel->getMappedCustomerAttribute($storeId);
        if (isset($mappedAttributes) && count($mappedAttributes) != '') {
            $primaryBilling  = $customer->getPrimaryBillingAddress();
            $primaryShipping = $customer->getPrimaryshippingAddress();
            $mappedCountries = $this->emarsysCountryHelper->getMapping($storeId);
            $headers = [];
            $headerIndex = [];

            foreach ($mappedAttributes as $attribute) {
                if ($attribute['emarsys_contact_field'] == NULL) {
                    continue;
                }
                $emarsysField = $this->customerResourceModel->getEmarsysFieldNameContact($attribute, $storeId);
                $headers[$attribute['magento_custom_attribute_id']] = $emarsysField['name'];
                $headerIndex[$attribute['emarsys_contact_field']] = $attribute['magento_custom_attribute_id'];
            }

            foreach ($headers as $key => $value) {
                //using the custom magento id from the emarsys_magento_customer_attributes table
                $attributeCode = $this->customerResourceModel->getMagentoAttributeCode($key, $storeId);
                if ($attributeCode['entity_type_id'] == 2) { // If the field type is Address
                    $isShippingAttr = (strpos($attributeCode['attribute_code_custom'], 'default_shipping_') !== false) ? true : false;
                    $isBillingAttr = (strpos($attributeCode['attribute_code_custom'], 'default_billing_') !== false) ? true : false;
                    $index = array_search($key, $headerIndex);
                    if ($index == 0) continue;
                    $attrValue = '';
                    if ($isShippingAttr) {
                        if (isset($customer['default_shipping']) && $customer['default_shipping'] != NULL && $customer['default_shipping'] != 0)  {
                            if ($primaryShipping) {
                                $attrValue = $primaryShipping->getData($attributeCode['attribute_code']);
                                if ($attributeCode['attribute_code'] == 'country_id') {
                                    $attrValue = (isset($mappedCountries[$attrValue]) ? $mappedCountries[$attrValue] : '');
                                } elseif ($attributeCode['attribute_code'] == 'street') {
                                    $attrValue = str_replace("\n", ',', $attrValue);
                                }
                            }
                        }
                    } elseif ($isBillingAttr) {
                        if (isset($customer['default_billing']) && $customer['default_billing'] != NULL && $customer['default_billing'] != 0) {
                            if ($primaryBilling) {
                                $attrValue = $primaryBilling->getData($attributeCode['attribute_code']);
                                if ($attributeCode['attribute_code'] == 'country_id') {
                                    $attrValue = (isset($mappedCountries[$attrValue]) ? $mappedCountries[$attrValue] : '');
                                } elseif ($attributeCode['attribute_code'] == 'street') {
                                    $attrValue = str_replace("\n", ',', $attrValue);
                                }
                            }
                        }
                    }
                    $addressFields[$index] = $attrValue;
                }
            }
        }

        return $addressFields;
    }

    /**
     * @param $customerId
     * @param $storeId
     * @return array
     */
    public function getCustomerPayload($customerId, $storeId)
    {
        $store = $this->storeManager->getStore($storeId);
        $websiteId = $store->getWebsiteId();

        $objCustomer = $this->customerFactory->create()->load($customerId);
        $arrCustomer = $objCustomer->getData();
        $customerData = [];

        $keyId = $this->customerResourceModel->getKeyId('Email', $storeId);
        $customerData[$keyId] = $objCustomer->getEmail();

        $keyId = $this->customerResourceModel->getKeyId('Magento Customer ID', $storeId);
        $customerData[$keyId] = $customerId;

        $keyId = $this->customerResourceModel->getKeyId('Magento Customer Unique ID', $storeId);
        $customerData[$keyId] = $objCustomer->getEmail() . "#" . $websiteId . "#" . $storeId;

        $getEmarsysMappedFields = $this->customerResourceModel->fetchMappedFields($storeId);

        foreach ($getEmarsysMappedFields as $mappedField) {
            if (isset($arrCustomer[$mappedField['attribute_code']]) && $mappedField['emarsys_contact_field'] != 0) {
                if (!is_null($mappedField['source_model'])) {
                    if ($mappedField['frontend_input'] == 'multiselect') {
                    } else {
                        $optionId = $arrCustomer[$mappedField['attribute_code']];
                        //Get Mapped Emarsys OptionId
                        if ($optionId) {
                            $customerData[$mappedField['emarsys_contact_field']] = $optionId;
                        }
                    }
                } else {
                    $customerData[$mappedField['emarsys_contact_field']] = $arrCustomer[$mappedField['attribute_code']];
                }
            }
        }
        //Fetch Customer's Mapped Address Attributes
        $customerMappedAddressAttributes = $this->getMappedCustomersAddressAttributes($objCustomer, $storeId);
        foreach ($customerMappedAddressAttributes as $key => $value) {
            $customerData[$key] = $value;
        }

        return $customerData;
    }

    public function prepareCustomerPayload($customerCollectionArray, $storeId)
    {
        $store = $this->storeManager->getStore($storeId);
        $websiteId = $store->getWebsiteId();
        $buildRequest = [];
        switch ($this->dataHelper->getContactUniqueField($websiteId)) {
            case 'magento_id' :
                $buildRequest['key_id'] = $this->customerResourceModel->getKeyId('Magento Customer ID', $storeId);
                break;
            case 'unique_id' :
                $buildRequest['key_id'] = $this->customerResourceModel->getKeyId('Magento Customer Unique ID', $storeId);
                break;
            default :
                $buildRequest['key_id'] = $this->customerResourceModel->getKeyId('Email', $storeId);
                break;
        }
        $buildRequest['contacts'] = $customerCollectionArray;

        return $buildRequest;
    }

    /**
     * @param $exportMode
     * @param $data
     * @param null $logId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function preparePayloadAndSyncMultipleContacts($exportMode, $data, $logId = null)
    {
        $websiteId = $data['website'];

        if (isset($data['storeId'])) {
            $storeId = $data['storeId'];
        } else {
            $storeId = $this->dataHelper->getFirstStoreIdOfWebsite($websiteId);
        }
        $fromDate = isset($data['fromDate']) ? $data['fromDate'] : '';
        $toDate = isset($data['toDate']) ? $data['toDate'] : '';

        $params = [
            'website' => $websiteId,
            'storeId' => $storeId,
            'fromDate' => $fromDate,
            'toDate' => $toDate
        ];
        $errorStatus = true;
        $jobDetails = $this->cronHelper->getJobDetail($exportMode);

        //initial logs for customer export
        $logsArray['job_code'] = $jobDetails['job_code'];
        $logsArray['status'] = 'started';
        $logsArray['messages'] = $jobDetails['job_title'] . ' initiated';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Manual';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $storeId;
        $logsArray['website_id'] = $websiteId;
        if (!$logId) {
            $logId = $this->logsHelper->manualLogs($logsArray, 1);
        }

        $logsArray['id'] = $logId;
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['log_action'] = 'sync';
        $logsArray['action'] = 'contact sync';

        //customer export starts
        $logsArray['emarsys_info'] = __('Customer Export Started');
        $logsArray['description'] = __('Customer Export Started for Store ID : %1', $storeId);
        $logsArray['message_type'] = 'Success';
        $this->logsHelper->logs($logsArray);

        //check customer attributes are mapped
        $mappedAttributes = $this->customerResourceModel->getMappedCustomerAttribute($storeId);
        if (isset($mappedAttributes) && count($mappedAttributes) != '') {
            $allCustomersPayload = [];
            if ($exportMode == EmarsysCronHelper::CRON_JOB_CUSTOMER_SYNC_QUEUE)  {
                $queueCollection = $this->queueModel->create()->getCollection();
                $queueCollection->addFieldToSelect('entity_id');
                $queueCollection->addFieldToFilter('entity_type_id', 1);

                //Prepare Customers Payload Array
                foreach ($queueCollection as $item) {
                    $allCustomersPayload[] = $this->getCustomerPayload($item->getEntityId(), $storeId);
                }
            } else {
                $customerCollection = $this->customerResourceModel->getCustomerCollection($params, $storeId);

                //Prepare Customers Payload Array
                foreach ($customerCollection as $customerData) {
                    $allCustomersPayload[] = $this->getCustomerPayload($customerData['entity_id'], $storeId);
                }
            }
            if (!empty($allCustomersPayload)) {
                //customers data present

                $customerChunks = array_chunk($allCustomersPayload, self::BATCH_SIZE);
                foreach ($customerChunks as $customerChunk) {
                    //prepare customers payload
                    $buildRequest = [];
                    $buildRequest = $this->prepareCustomerPayload($customerChunk, $storeId);
                    if (count($buildRequest) > 0) {
                        $logsArray['emarsys_info'] = 'Send customers to Emarsys';
                        $logsArray['action'] = 'Magento to Emarsys';
                        $logsArray['message_type'] = 'Success';
                        $logsArray['description'] = 'PUT ' . " contact/?create_if_not_exists=1 " . json_encode($buildRequest, JSON_PRETTY_PRINT);
                        $this->logsHelper->logs($logsArray);
                        $this->emarsysLogger->info($logsArray['description']);

                        //Send request to Emarsys with Customer's Data
                        $this->api->setWebsiteId($websiteId);
                        $result = $this->api->createContactInEmarsys($buildRequest);

                        $logsArray['emarsys_info'] = 'Create customers in Emarsys';
                        $logsArray['action'] = 'Synced to Emarsys';
                        $res = 'PUT ' . " contact/?create_if_not_exists=1 " . json_encode($result, JSON_PRETTY_PRINT);

                        if ($result['status'] == '200') {
                            $errorStatus = false;
                            $logsArray['message_type'] = 'Success';
                            $logsArray['emarsys_info'] = __('Created customers in Emarsys succcessfully');
                            $logsArray['description'] = "Created customers in Emarsys succcessfully " . $res;
                            $emailIdKey = $this->customerResourceModel->getKeyId("Email", $storeId);

                            if ($exportMode == EmarsysCronHelper::CRON_JOB_CUSTOMER_SYNC_QUEUE) {
                                $custEmailIds = [];
                                foreach ($customerChunk as $cust) {
                                    $custEmailIds[] = $cust[$emailIdKey];
                                }
                                $customerCollFail = $this->custColl;
                                $customerCollSuccess = $this->custColl;
                                $errIds = [];

                                if (isset($result['body']['data']['errors'])) {
                                    $errIds = array_keys($result['body']['data']['errors']);
                                    if (count($result['body']['data']['errors'])) {
                                        $dataDataColl = $customerCollFail->addAttributeToFilter('email', ["in" => $errIds]);
                                        $custData = $dataDataColl->getData();
                                        foreach ($custData as $custIndividualData) {
                                            $this->dataHelper->syncFail(
                                                $custIndividualData['entity_id'],
                                                $custIndividualData['website_id'],
                                                $custIndividualData['store_id'],
                                                1,
                                                1
                                            );
                                        }
                                    }
                                }

                                if (count($result['body']['data']['ids'])) {
                                    $successIds = array_diff($custEmailIds, $errIds);
                                    $dataDataColl = $customerCollSuccess->addAttributeToFilter('email', ["in" => $successIds]);
                                    $custData = $dataDataColl->getData();
                                    foreach ($custData as $custIndividualData) {
                                        $this->dataHelper->syncSuccess(
                                            $custIndividualData['entity_id'],
                                            $custIndividualData['website_id'],
                                            $custIndividualData['store_id'],
                                            1
                                        );
                                    }
                                }
                            }
                        } else {
                            //error response from emarsys
                            $logsArray['emarsys_info'] = __('Error while customer export.');
                            $logsArray['message_type'] = 'Error';
                            $logsArray['description'] = $result['body']['replyText'] . $res;
                            $this->messageManager->addErrorMessage(
                                __('Customers export have an error. Please check emarsys logs for more details!!')
                            );
                        }
                        $this->logsHelper->logs($logsArray);
                        $this->emarsysLogger->info($logsArray['description']);
                    }
                }
            } else {
                //no Customers data found
                $logsArray['emarsys_info'] = 'No Customers Found.';
                $logsArray['action'] = 'Magento to Emarsys';
                $logsArray['message_type'] = 'Error';
                $logsArray['description'] = __('No Customers for the store with store id %1.', $storeId);
                $this->logsHelper->logs($logsArray);
                $this->messageManager->addErrorMessage(
                    __('No Customers found for the store with store id %1.', $storeId)
                );
            }
        } else {
            $logsArray['emarsys_info'] = 'Attributes are not mapped';
            $logsArray['description'] = 'Failed to sync contacts. Customer attributes are not mapped.';
            $logsArray['action'] = 'synced to emarsys';
            $logsArray['message_type'] = 'Error';
            $this->logsHelper->logs($logsArray);
            $this->messageManager->addErrorMessage("Attributes are not mapped for this store view !!!");
        }

        if ($errorStatus) {
            $logsArray['status'] = 'error';
            $logsArray['messages'] = 'Customer export have an error. Please check';
        } else {
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Customer export completed';
        }
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        $this->logsHelper->manualLogsUpdate($logsArray);

        return $errorStatus ? false : true;
    }

    public function syncFullContactUsingApi($exportMode, $data)
    {
        $websiteId = $data['website'];

        if (isset($data['storeId'])) {
            $storeId = $data['storeId'];
        } else {
            $storeId = $this->dataHelper->getFirstStoreIdOfWebsite($websiteId);
        }
        $fromDate = isset($data['fromDate']) ? $data['fromDate'] : '';
        $toDate = isset($data['toDate']) ? $data['toDate'] : '';

        $params = [
            'website' => $websiteId,
            'storeId' => $storeId,
            'fromDate' => $fromDate,
            'toDate' => $toDate
        ];
        $errorStatus = true;
        $jobDetails = $this->cronHelper->getJobDetail($exportMode);

        //initial logs for customer export
        $logsArray['job_code'] = $jobDetails['job_code'];
        $logsArray['status'] = 'started';
        $logsArray['messages'] = $jobDetails['job_title'] . ' initiated';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Manual';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $storeId;
        $logsArray['website_id'] = $websiteId;
        $logId = $this->logsHelper->manualLogs($logsArray, 1);
        $logsArray['id'] = $logId;
        $logsArray['log_action'] = 'sync';
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['action'] = 'synced to emarsys';

        //check if emarsys enabled for the website
        if ($this->dataHelper->getEmarsysConnectionSetting($websiteId)) {
            $errorStatus = $this->exportDataToApi($exportMode, $params, $logId);
        } else {
            //Emarsys is disabled for the store
            $logsArray['emarsys_info'] = __('Emarsys is disabled');
            $logsArray['description'] = __('Emarsys is disabled for the store');
            $logsArray['message_type'] = 'Error';
            $this->logsHelper->logs($logsArray);
        }

        if ($errorStatus) {
            $logsArray['status'] = 'error';
            $logsArray['messages'] = 'Error while'. $jobDetails['job_title'] . ' !!!';
        } else {
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Successfully synced contacts !!!';
        }
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        $this->logsHelper->manualLogsUpdate($logsArray);

        return;
    }

    public function exportDataToApi($exportMode, $data, $logId)
    {
        $errorStatus = true;

        switch ($exportMode) {
            case EmarsysCronHelper::CRON_JOB_CUSTOMER_SYNC_QUEUE:
                $subscriberExportStatus = $this->subscriberApi->syncMultipleSubscriber($exportMode, $data, $logId);
                $customerExportStatus = $this->preparePayloadAndSyncMultipleContacts($exportMode, $data, $logId);

                if ($subscriberExportStatus && $customerExportStatus) {
                    $errorStatus = false;
                }
                break;
            case EmarsysCronHelper::CRON_JOB_CUSTOMER_BULK_EXPORT_API:
                $customerExportStatus = $this->preparePayloadAndSyncMultipleContacts($exportMode, $data, $logId);
                if ($customerExportStatus) {
                    $errorStatus = false;
                }
                break;
            default:
                $customerExportStatus = $this->preparePayloadAndSyncMultipleContacts($exportMode, $data, $logId);
                if ($customerExportStatus) {
                    $errorStatus = false;
                }
                break;
        }

        return $errorStatus;
    }
}
