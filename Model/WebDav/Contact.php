<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\WebDav;

use Magento\{
    Store\Model\StoreManagerInterface,
    Store\Model\ScopeInterface,
    Framework\Stdlib\DateTime\DateTime,
    Customer\Model\CustomerFactory,
    Backend\App\Action\Context
};
use Emarsys\Emarsys\{
    Model\ResourceModel\Customer,
    Helper\Data as EmarsysHelper,
    Helper\Logs,
    Helper\Country as EmarsysCountryHelper
};

/**
 * Class Contact
 * @package Emarsys\Emarsys\Model\WebDav
 */
class Contact extends \Magento\Framework\DataObject
{
    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * @var CustomerFactory
     */
    protected $customer;

    /**
     * @var Logs
     */
    protected $logsHelper;

    /**
     * @var Customer
     */
    protected $customerResourceModel;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var EmarsysCountryHelper
     */
    protected $emarsysCountryHelper;

    /**
     * @var WebDavExport
     */
    protected $webDavExport;
    /**
     * @var Context
     */
    private $context;

    /**
     * Contact constructor.
     * @param EmarsysHelper $emarsysHelper
     * @param CustomerFactory $customer
     * @param Context $context
     * @param DateTime $date
     * @param StoreManagerInterface $storeManager
     * @param Customer $customerResourceModel
     * @param Logs $logsHelper
     * @param EmarsysCountryHelper $emarsysCountryHelper
     * @param WebDavExport $webDavExport
     * @param array $data
     */
    public function __construct(
        EmarsysHelper $emarsysHelper,
        CustomerFactory $customer,
        Context $context,
        DateTime $date,
        StoreManagerInterface $storeManager,
        Customer $customerResourceModel,
        Logs $logsHelper,
        EmarsysCountryHelper $emarsysCountryHelper,
        WebDavExport $webDavExport,
        array $data = []
    ) {
        $this->emarsysHelper = $emarsysHelper;
        $this->customer = $customer;
        $this->logsHelper = $logsHelper;
        $this->customerResourceModel = $customerResourceModel;
        $this->storeManager = $storeManager;
        $this->date = $date;
        $this->emarsysCountryHelper = $emarsysCountryHelper;
        $this->webDavExport = $webDavExport;
        $this->context = $context;
        parent::__construct($data);
    }

    /**
     * @param $data
     * @param null $logId
     * @return bool
     * @throws \Exception
     */
    public function exportCustomerDataWebDav($data, $logId = null)
    {
        $scope = ScopeInterface::SCOPE_WEBSITES;
        $websiteId = $data['website'];

        if (isset($data['store'])) {
            $storeId = $data['store'];
        } else {
            $storeId = $this->emarsysHelper->getFirstStoreIdOfWebsite($websiteId);
        }
        $store = $this->storeManager->getStore($storeId);
        $storeCode = $store->getCode();
        $websiteId = $store->getWebsiteId();
        $data['storeId'] = $storeId;
        $data['website'] = $websiteId;
        $errorCount = true;

        $logsArray['job_code'] = 'initialdbload';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = 'Initial DB data initiated';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Manual';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $storeId;
        $logsArray['website_id'] = $websiteId;
        if (!$logId) {
            $logId = $this->logsHelper->manualLogs($logsArray, 1);
        }
        $logsArray['id'] = $logId;
        $logsArray['log_action'] = 'sync';
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['action'] = 'synced to emarsys';

        //get customer collection for the store
        $customerCollection = $this->customerResourceModel->getCustomerCollection($data, $storeId);
        if ($customerCollection->getSize()) {
            //webDav credentials from admin configurations
            $webDavCredentials = $this->emarsysHelper->collectWebDavCredentials($scope, $websiteId);
            if ($webDavCredentials && !empty($webDavCredentials)) {
                $webDavUrl = $webDavCredentials['baseUri'];
                $webDavUser = $webDavCredentials['userName'];
                $webDavPass = $webDavCredentials['password'];

                //check webdav connection with credentials
                $checkWebDavConnection = $this->webDavExport->testWebDavConnection(
                    $webDavUrl,
                    $webDavUser,
                    $webDavPass
                );
                if ($checkWebDavConnection['status']) {
                    $mappedAttributes = $this->customerResourceModel->getMappedCustomerAttribute($storeId);
                    if (isset($mappedAttributes) && count($mappedAttributes) != '') {
                        $headers = [];
                        $headerIndex = [];
                        $indexCount = 0;
                        foreach ($mappedAttributes as $att) {
                            if ($att['emarsys_contact_field'] == NULL) {
                                continue;
                            }
                            $headers[$att['magento_custom_attribute_id']] = $this->customerResourceModel->getEmarsysFieldNameContact($att, $storeId);
                            $headerIndex[$indexCount] = $att['magento_custom_attribute_id'];
                            $indexCount++;
                        }

                        $headers['magento_customer_id'] = EmarsysHelper::CUSTOMER_ID;
                        $headerIndex[$indexCount] = 'magento_customer_id';
                        if (!in_array('Email', $headers)) {
                            $headers['email'] = EmarsysHelper::CUSTOMER_EMAIL;
                            $indexCount = $indexCount + 1;
                            $headerIndex[$indexCount] = 'email';
                        }

                        $outputFile = $this->emarsysHelper->getCustomerCsvFileName(
                            \Magento\Customer\Model\Customer::ENTITY,
                            $storeCode
                        );
                        $filePath = $this->emarsysHelper->getContactCsvGenerationPath($outputFile);
                        $handle = fopen($filePath, 'w');

                        //write header to customer csv
                        fputcsv($handle, $headers);

                        foreach ($customerCollection as $customerData) {
                            $customerValues = [];
                            //$customerLoad = $this->customer->create()->load($customerData['entity_id']);
                            $primaryBilling = $customerData->getPrimaryBillingAddress();
                            $primaryShipping = $customerData->getPrimaryShippingAddress();
                            $mappedCountries = $this->emarsysCountryHelper->getMapping($storeId);

                            foreach ($headers as $key => $value) {
                                //using the custom magento id from the emarsys_magento_customer_attributes table
                                $attributeCode = $this->customerResourceModel->getMagentoAttributeCode($key, $storeId);

                                //code for the custom defined attributes in the array starts
                                if ($value == EmarsysHelper::CUSTOMER_ID) {
                                    $index = array_search($key, $headerIndex);
                                    $customerValues[$index] = $customerData->getId();
                                } elseif ($value == EmarsysHelper::CUSTOMER_EMAIL) {
                                    $index = array_search($key, $headerIndex);
                                    $customerValues[$index] = $customerData->getEmail();
                                } elseif ($attributeCode['entity_type_id'] == 1) {
                                    //code for the custom defined attributes ends here
                                    $index = array_search($key, $headerIndex);
                                    $customerValues[$index] = $customerData->getData($attributeCode['attribute_code']);
                                } elseif ($attributeCode['entity_type_id'] == 2) {
                                    $isShippingAttr = (strpos($attributeCode['attribute_code_custom'], 'default_shipping_') !== false) ? true : false;
                                    $isBillingAttr = (strpos($attributeCode['attribute_code_custom'], 'default_billing_') !== false) ? true : false;
                                    $index = array_search($key, $headerIndex);
                                    $attrVal = '';

                                    if ($isShippingAttr) {
                                        if (isset($customerData['default_shipping']) && $customerData['default_shipping'] != NULL && $customerData['default_shipping'] != 0) {
                                            if ($primaryShipping) {
                                                $attrVal = $primaryShipping->getData($attributeCode['attribute_code']);
                                                if ($attributeCode['attribute_code'] == 'country_id') {
                                                    $attrVal = (isset($mappedCountries[$attrVal]) ? $mappedCountries[$attrVal] : '');
                                                } elseif ($attributeCode['attribute_code'] == 'street') {
                                                    $attrVal = str_replace("\n", ',', $attrVal);
                                                }
                                            }
                                        }
                                    } elseif ($isBillingAttr) {
                                        if (isset($customerData['default_billing']) && $customerData['default_billing'] != NULL && $customerData['default_billing'] != NULL) {
                                            if ($primaryBilling) {
                                                $attrVal = $primaryBilling->getData($attributeCode['attribute_code']);
                                                if ($attributeCode['attribute_code'] == 'country_id') {
                                                    $attrVal = (isset($mappedCountries[$attrVal]) ? $mappedCountries[$attrVal] : '');
                                                } elseif ($attributeCode['attribute_code'] == 'street') {
                                                    $attrVal = str_replace("\n", ',', $attrVal);
                                                }
                                            }
                                        }
                                    }
                                    $customerValues[$index] = $attrVal;
                                }
                            }
                            fputcsv($handle, $customerValues);
                        }

                        //export csv to webdav
                        $exportStatus = $this->webDavExport->apiExport(
                            $filePath,
                            $outputFile,
                            $webDavUrl,
                            $webDavUser,
                            $webDavPass
                        );

                        //remove csv file after export
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }

                        if ($exportStatus['status']) {
                            //customer file uploaded to server successfully
                            $errorCount = false;
                            $logsArray['emarsys_info'] = __('Customer file uploaded to server successfully for store %1', $store->getName());
                            $logsArray['description'] = 'Emarsys response: ' . $exportStatus['response_body'] . ' File Path: ' . $webDavUrl . $outputFile;
                            $logsArray['message_type'] = 'Success';
                        } else {
                            //Failed to upload Customer file on server
                            $logsArray['emarsys_info'] = __('Failed to upload Customer file on server  for store %1', $store->getName());
                            $logsArray['description'] = $exportStatus['response_body'];
                            $logsArray['message_type'] = 'Error';
                        }
                        $this->logsHelper->logs($logsArray);
                    } else {
                        //Attributes are not mapped for given store
                        $logsArray['emarsys_info'] = __('Attributes are not mapped for the store %1', $store->getName());
                        $logsArray['description'] = __('Failed to upload file on server. Attributes are not mapped for the store %1', $store->getName());
                        $logsArray['message_type'] = 'Error';
                        $this->logsHelper->logs($logsArray);
                    }
                } else {
                    //failed to login on webdav server
                    $logsArray['emarsys_info'] = 'Failed to Login with WebDav Server.';
                    $logsArray['description'] = 'Failed to Login with WebDav Server. Please check your settings and try again. ' . $checkWebDavConnection['response_body'];
                    $logsArray['message_type'] = 'Error';
                    $this->logsHelper->logs($logsArray);
                }
            } else {
                //Invalid WebDAV credentials
                $logsArray['emarsys_info'] = 'Invalid WebDAV credentials.';
                $logsArray['description'] = 'Invalid WebDAV credentials. Please check credentials and try again.';
                $logsArray['message_type'] = 'Error';
                $this->logsHelper->logs($logsArray);
            }
        } else {
            //No Customers Found
            $logsArray['emarsys_info'] = __('No Customers Found');
            $logsArray['description'] = __('No Customers Found for store %1', $store->getName());
            $logsArray['message_type'] = 'Error';
            $this->logsHelper->logs($logsArray);
        }

        //set error/success state
        if ($errorCount) {
            $logsArray['status'] = 'error';
            $logsArray['messages'] = 'Error in Uploading files. Please check.';
        } else {
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Initial DB data completed';
        }
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        $this->logsHelper->manualLogsUpdate($logsArray);

        return $errorCount ? false : true;
    }
}
