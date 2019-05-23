<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Api;

use Magento\{
    Customer\Model\Address,
    Customer\Model\Customer,
    Customer\Model\CustomerFactory,
    Customer\Model\ResourceModel\Customer\Collection as CustomerCollection,
    Framework\Stdlib\DateTime\DateTime,
    Framework\Message\ManagerInterface as MessageManagerInterface,
    Store\Model\StoreManagerInterface
};
use Emarsys\Emarsys\{
    Helper\Data as EmarsysHelperData,
    Helper\Logs,
    Helper\Cron as EmarsysCronHelper,
    Helper\Country as EmarsysCountryHelper,
    Model\QueueFactory,
    Model\ResourceModel\Customer as customerResourceModel,
    Logger\Logger as EmarsysLogger
};

/**
 * Class Contact
 * API class for Emarsys API wrappers
 *
 * @package Emarsys\Emarsys\Model\Api
 */
class Contact
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Customer
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
     * @var EmarsysHelperData
     */
    protected $emarsysHelper;

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
     * @var array
     */
    protected $mappedCustomerAttribute = [];

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var int
     */
    protected $websiteId;

    /**
     * @var string
     */
    protected $exportMode;

    /**
     * Contact constructor.
     *
     * @param Api $api
     * @param Customer $customer
     * @param CustomerFactory $customerFactory
     * @param customerResourceModel $customerResourceModel
     * @param DateTime $date
     * @param Logs $logsHelper
     * @param EmarsysHelperData $emarsysHelper
     * @param CustomerCollection $customerColl
     * @param EmarsysCountryHelper $emarsysCountryHelper
     * @param StoreManagerInterface $storeManager
     * @param QueueFactory $queueModel
     * @param MessageManagerInterface $messageManager
     * @param EmarsysCronHelper $cronHelper
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
        EmarsysHelperData $emarsysHelper,
        CustomerCollection $customerColl,
        EmarsysCountryHelper $emarsysCountryHelper,
        StoreManagerInterface $storeManager,
        QueueFactory $queueModel,
        MessageManagerInterface $messageManager,
        EmarsysCronHelper $cronHelper,
        Subscriber $subscriberApi,
        EmarsysLogger $emarsysLogger
    ) {
        $this->api = $api;
        $this->customer = $customer;
        $this->customerFactory = $customerFactory;
        $this->customerResourceModel = $customerResourceModel;
        $this->date = $date;
        $this->logsHelper = $logsHelper;
        $this->emarsysHelper = $emarsysHelper;
        $this->custColl = $customerColl;
        $this->emarsysCountryHelper = $emarsysCountryHelper;
        $this->storeManager = $storeManager;
        $this->queueModel = $queueModel;
        $this->messageManager = $messageManager;
        $this->cronHelper = $cronHelper;
        $this->subscriberApi = $subscriberApi;
        $this->emarsysLogger = $emarsysLogger;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @param $websiteId
     * @param $storeId
     * @param int $cron
     * @param null|\Magento\Customer\Model\Address $customerAddress
     * @throws \Exception
     */
    public function syncContact($customer, $websiteId, $storeId, $cron = 0, $customerAddress = null)
    {
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

        $store = $this->storeManager->getStore($storeId);

        if (!($customer instanceof Customer)) {
            $customer = $this->customer->load($customer->getId());
        }

        $buildRequest = [];
        $emailKey = $this->customerResourceModel->getKeyId(EmarsysHelperData::CUSTOMER_EMAIL, $storeId);
        $buildRequest['key_id'] = $emailKey;
        if ($emailKey && $customer->getEmail()) {
            $buildRequest[$emailKey] = $customer->getEmail();
        }

        $customerIdKey = $this->customerResourceModel->getKeyId(EmarsysHelperData::CUSTOMER_ID, $storeId);
        if ($customerIdKey && $customer->getId()) {
            $buildRequest[$customerIdKey] = $customer->getId();
        }

        $errorMsg = 0;
        $getEmarsysMappedFields = $this->customerResourceModel->fetchMappedFields($storeId);

        if (empty($getEmarsysMappedFields)) {
            $errorMsg = 1;
        }

        foreach ($getEmarsysMappedFields as $mappedField) {
            if ($customer->getData($mappedField['attribute_code']) && $mappedField['emarsys_contact_field'] != 0) {
                if (!is_null($mappedField['source_model'])) {
                    if ($mappedField['frontend_input'] != 'multiselect') {
                        $optionId = $customer->getData($mappedField['attribute_code']);
                        /**
                         * Get Mapped Emarsys OptionId
                         */
                        $emarsysOptionId = $optionId;
                        if ($emarsysOptionId) {
                            $buildRequest[$mappedField['emarsys_contact_field']] = $emarsysOptionId;
                        }
                    }
                } else {
                    $buildRequest[$mappedField['emarsys_contact_field']] = $customer->getData($mappedField['attribute_code']);
                }
            }
        }

        //Fetch Customer's Mapped Address Attributes
        $customerMappedAddressAttributes = $this->getMappedCustomersAddressAttributes($customer, $storeId, $customerAddress);
        foreach ($customerMappedAddressAttributes as $key => $value) {
            $buildRequest[$key] = $value;
        }

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

                $confirmUrl = '';
                if ($customer->getConfirmation()) {
                    $confirmUrl = ' | Confirmation URL : '
                        . $store->getUrl(
                            'customer/account/confirm',
                            ['id' => $customer->getId(), 'key' => $customer->getConfirmation()]
                        );
                }

                $res = 'PUT ' . " contact/?create_if_not_exists=1 " . json_encode($result, JSON_PRETTY_PRINT);
                $logsArray['description'] = 'Created customer ' . $customer->getEmail() . ' in Emarsys succcessfully ' . $res . $confirmUrl;
                $this->emarsysHelper->syncSuccess($customer->getId(), $websiteId, $storeId, $cron);
            } else {
                $this->emarsysHelper->syncFail($customer->getId(), $websiteId, $storeId, $cron, 1);
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
            if (empty($getEmarsysMappedFields)) {
                $logsArray['messages'] = 'Create customer in Emarsys with ERROR! Mapping is empty!';
            }
        } else {
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Customer in Emarsys created';
        }
        $this->logsHelper->manualLogsUpdate($logsArray);
    }

    /**
     * Fetch Customer's Mapped Address attributes values
     *
     * @param $customer
     * @param int $storeId
     * @param null|\Magento\Customer\Model\Address $customerAddress
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMappedCustomersAddressAttributes($customer, $storeId, $customerAddress = null)
    {
        $addressFields = [];
        $mappedAttributes = $this->getMappedCustomerAttribute($storeId);
        if (count($mappedAttributes)) {
            $primaryShipping = $customer->getPrimaryShippingAddress();
            if (($customerAddress && $customerAddress->getDefaultShipping()) || !$primaryShipping) {
                $primaryShipping = $customerAddress;
            } elseif ($customerAddress && $primaryShipping->getId() == $customerAddress->getId()) {
                $primaryShipping = $customerAddress;
            }
            $primaryBilling = $customer->getPrimaryBillingAddress();
            if (($customerAddress && $customerAddress->getDefaultBilling()) || !$primaryBilling) {
                $primaryBilling = $customerAddress;
            } elseif ($customerAddress && $primaryBilling->getId() == $customerAddress->getId()) {
                $primaryBilling = $customerAddress;
            }

            $mappedCountries = $this->emarsysCountryHelper->getMapping($storeId);
            $headers = $headerIndex = [];

            foreach ($mappedAttributes as $attribute) {
                if (!$attribute['emarsys_contact_field']) {
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
                    $attrValue = '';
                    if ($isShippingAttr && $primaryShipping) {
                        $attrValue = $primaryShipping->getData($attributeCode['attribute_code']);
                    } elseif ($isBillingAttr && $primaryBilling) {
                        $attrValue = $primaryBilling->getData($attributeCode['attribute_code']);
                    }
                    if ($attributeCode['attribute_code'] == 'country_id') {
                        $attrValue = (isset($mappedCountries[$attrValue]) ? $mappedCountries[$attrValue] : '');
                    } elseif ($attributeCode['attribute_code'] == 'street') {
                        $attrValue = str_replace("\n", ',', $attrValue);
                    }

                    $addressFields[$index] = $attrValue;
                }
            }
        }

        return $addressFields;
    }

    /**
     * @param $objCustomer
     * @param $storeId
     * @param $emailKey
     * @param $customerIdKey
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Json_Exception
     */
    public function getCustomerPayload(
        $objCustomer,
        $storeId,
        $emailKey,
        $customerIdKey
    ) {
        $customerData = [];
        if ($emailKey && $objCustomer->getEmail()) {
            $customerData[$emailKey] = $objCustomer->getEmail();
        }

        if ($customerIdKey && $objCustomer->getId()) {
            $customerData[$customerIdKey] = $objCustomer->getId();
        }

        $getEmarsysMappedFields = $this->customerResourceModel->fetchMappedFields($storeId);

        foreach ($getEmarsysMappedFields as $mappedField) {
            if ($objCustomer->getData($mappedField['attribute_code']) && $mappedField['emarsys_contact_field'] != 0) {
                if (!is_null($mappedField['source_model'])) {
                    if ($mappedField['frontend_input'] != 'multiselect') {
                        $optionId = $objCustomer->getData($mappedField['attribute_code']);
                        //Get Mapped Emarsys OptionId
                        if ($optionId) {
                            $customerData[$mappedField['emarsys_contact_field']] = $optionId;
                        }
                    }
                } else {
                    $customerData[$mappedField['emarsys_contact_field']] = $objCustomer->getData($mappedField['attribute_code']);
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

    /**
     * @param $customerCollectionArray
     * @param $keyId
     * @return array
     */
    public function prepareCustomerPayload($customerCollectionArray, $keyId)
    {
        $buildRequest = [];
        if ($keyId) {
            $buildRequest['key_id'] = $keyId;
            $buildRequest['contacts'] = $customerCollectionArray;
        }

        return $buildRequest;
    }

    /**
     * @param $exportMode
     * @param $data
     * @param null $logId
     * @return bool
     * @throws \Exception
     */
    public function preparePayloadAndSyncMultipleContacts($exportMode, $data, $logId = null)
    {
        $websiteId = $data['website'];

        if (isset($data['storeId'])) {
            $storeId = $data['storeId'];
        } else {
            $storeId = $this->emarsysHelper->getFirstStoreIdOfWebsite($websiteId);
        }

        $this->exportMode = $exportMode;
        $this->storeId = $storeId;
        $this->websiteId = $websiteId;

        $params = [
            'website' => $websiteId,
            'storeId' => $storeId,
            'fromDate' => $data['fromDate'],
            'toDate' => $data['toDate']
        ];
        $success = false;
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

        $emailKey = $this->customerResourceModel->getKeyId(EmarsysHelperData::CUSTOMER_EMAIL, $storeId);
        $customerIdKey = $this->customerResourceModel->getKeyId(EmarsysHelperData::CUSTOMER_ID, $storeId);

        //check customer attributes are mapped
        $mappedAttributes = $this->getMappedCustomerAttribute($storeId);
        if (count($mappedAttributes)) {
            $allCustomersPayload = [];
            if ($exportMode == EmarsysCronHelper::CRON_JOB_CUSTOMER_SYNC_QUEUE)  {
                $queueCollection = $this->queueModel->create()->getCollection();
                $queueCollection->addFieldToSelect('entity_id');
                $queueCollection->addFieldToFilter('entity_type_id', 1);

                //Prepare Customers Payload Array
                foreach ($queueCollection as $item) {
                    $allCustomersPayload[] = $this->getCustomerPayload(
                        $this->customerFactory->create()->load($item->getEntityId()),
                        $storeId,
                        $emailKey,
                        $customerIdKey
                    );
                }

                $this->processBatch($allCustomersPayload, $emailKey, $logsArray);
            } else {
                $currentPageNumber = 1;
                $customerCollection = $this->customerResourceModel->getCustomerCollection($params, $storeId, $currentPageNumber);
                $lastPageNumber = $customerCollection->getLastPageNumber();

                //Prepare Customers Payload Array
                while ($currentPageNumber <= $lastPageNumber) {
                    if ($currentPageNumber != 1) {
                        $customerCollection = $this->customerResourceModel->getCustomerCollection(
                            $params,
                            $storeId,
                            $currentPageNumber
                        );
                    }
                    foreach ($customerCollection as $customerData) {
                        $allCustomersPayload[] = $this->getCustomerPayload(
                            $customerData,
                            $storeId,
                            $emailKey,
                            $customerIdKey
                        );
                    }

                    $success = $this->processBatch($allCustomersPayload, $logsArray);

                    $logsArray['emarsys_info'] = __('Processing data for store %1', $storeId);
                    $logsArray['description'] = __('%1 of %2', $currentPageNumber, $lastPageNumber);
                    $logsArray['message_type'] = $success ? 'Success' : 'False';
                    $this->logsHelper->logs($logsArray);
                    $currentPageNumber++;
                }
            }
        } else {
            $logsArray['emarsys_info'] = 'Attributes are not mapped';
            $logsArray['description'] = 'Failed to sync contacts. Customer attributes are not mapped.';
            $logsArray['action'] = 'synced to emarsys';
            $logsArray['message_type'] = 'Error';
            $this->logsHelper->logs($logsArray);
            $this->messageManager->addErrorMessage("Attributes are not mapped for this store view !!!");
        }

        if ($success) {
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Customer export completed';
        } else {
            $logsArray['status'] = 'error';
            $logsArray['messages'] = 'Customer export have an error. Please check';
        }
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        $this->logsHelper->manualLogsUpdate($logsArray);

        return $success;
    }

    public function processBatch($allCustomersPayload, $logsArray)
    {
         if (empty($allCustomersPayload)) {
            //no Customers data found
            $logsArray['emarsys_info'] = 'No Customers Found.';
            $logsArray['action'] = 'Magento to Emarsys';
            $logsArray['message_type'] = 'Error';
            $logsArray['description'] = __('No Customers for the store with store id %1.', $this->storeId);
            $this->logsHelper->logs($logsArray);
            $this->messageManager->addErrorMessage(__('No Customers found for the store with store id %1.', $this->storeId));
            return false;
        }

        $emailKey = $this->customerResourceModel->getKeyId(EmarsysHelperData::CUSTOMER_EMAIL, $this->storeId);

        $buildRequest = $this->prepareCustomerPayload($allCustomersPayload, $emailKey);
        if (count($buildRequest) > 0) {
            $logsArray['emarsys_info'] = 'Send customers to Emarsys';
            $logsArray['action'] = 'Magento to Emarsys';
            $logsArray['message_type'] = 'Success';
            $logsArray['description'] = 'PUT ' . " contact/?create_if_not_exists=1 " . json_encode($buildRequest, JSON_PRETTY_PRINT);
            $this->logsHelper->logs($logsArray);
            $this->emarsysLogger->info($logsArray['description']);

            //Send request to Emarsys with Customer's Data
            $this->api->setWebsiteId($this->websiteId);
            $result = $this->api->createContactInEmarsys($buildRequest);

            $logsArray['emarsys_info'] = 'Create customers in Emarsys';
            $logsArray['action'] = 'Synced to Emarsys';
            $res = 'PUT ' . " contact/?create_if_not_exists=1 " . json_encode($result, JSON_PRETTY_PRINT);

            if ($result['status'] == '200') {
                $logsArray['message_type'] = 'Success';
                $logsArray['emarsys_info'] = __('Created customers in Emarsys succcessfully');
                $logsArray['description'] = "Created customers in Emarsys succcessfully " . $res;

                if ($this->exportMode == EmarsysCronHelper::CRON_JOB_CUSTOMER_SYNC_QUEUE) {
                    $custEmailIds = [];
                    foreach ($allCustomersPayload as $cust) {
                        if (isset($cust[$emailKey])) {
                            $custEmailIds[] = $cust[$emailKey];
                        }
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
                                $this->emarsysHelper->syncFail(
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
                            $this->emarsysHelper->syncSuccess(
                                $custIndividualData['entity_id'],
                                $custIndividualData['website_id'],
                                $custIndividualData['store_id'],
                                1
                            );
                        }
                    }
                }
                $this->logsHelper->logs($logsArray);
                $this->emarsysLogger->info($logsArray['description']);
            } else {
                //error response from emarsys
                $logsArray['emarsys_info'] = __('Error while customer export.');
                $logsArray['message_type'] = 'Error';
                $logsArray['description'] = $result['body']['replyText'] . $res;
                $this->messageManager->addErrorMessage(
                    __('Customers export have an error. Please check emarsys logs for more details!!')
                );
                $this->logsHelper->logs($logsArray);
                $this->emarsysLogger->info($logsArray['description']);
                return false;
            }
        }

        return true;
    }

    /**
     * @param $exportMode
     * @param $data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function syncFullContactUsingApi($exportMode, $data)
    {
        $jobDetails = $this->cronHelper->getJobDetail($exportMode);

        $websiteId = $data['website'];

        $website = $this->storeManager->getWebsite($websiteId);

        if (!$this->emarsysHelper->isContactsSynchronizationEnable($websiteId)) {
            return;
        }

        if (isset($data['storeId'])) {
            $storeId = $data['storeId'];
        } else {
            $storeId = $this->emarsysHelper->getFirstStoreIdOfWebsite($websiteId);
        }
        $fromDate = (isset($data['fromDate']) && !empty($data['fromDate'])) ? $data['fromDate'] : '';
        $toDate = (isset($data['toDate']) && !empty($data['toDate'])) ? $data['toDate'] : $this->date->date('Y-m-d') . ' 23:59:59';

        $params = [
            'website' => $websiteId,
            'storeId' => $storeId,
            'fromDate' => $fromDate,
            'toDate' => $toDate
        ];
        $errorStatus = true;

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
        if ($this->emarsysHelper->getEmarsysConnectionSetting($websiteId) &&
            $website->getConfig(EmarsysHelperData::XPATH_EMARSYS_ENABLE_CONTACT_FEED)
        ) {
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
            $logsArray['messages'] = 'Something went wrong, please check logs';
        } else {
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Contacts successfully synced';
        }
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        $this->logsHelper->manualLogsUpdate($logsArray);

        return $errorStatus;
    }

    public function exportDataToApi($exportMode, $data, $logId)
    {
        $errorStatus = true;

        switch ($exportMode) {
            case EmarsysCronHelper::CRON_JOB_CUSTOMER_SYNC_QUEUE:
                $customerExportStatus = $this->preparePayloadAndSyncMultipleContacts($exportMode, $data, $logId);
                $subscriberExportStatus = $this->subscriberApi->syncMultipleSubscriber($exportMode, $data, $logId);

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

    /**
     * @param $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getMappedCustomerAttribute($storeId)
    {
        if (!isset($this->mappedCustomerAttribute[$storeId])) {
            $this->mappedCustomerAttribute[$storeId] = $this->customerResourceModel->getMappedCustomerAttribute($storeId);
        }

        return $this->mappedCustomerAttribute[$storeId];
    }
}
