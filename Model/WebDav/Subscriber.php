<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\WebDav;

use Magento\{
    Framework\DataObject,
    Framework\Stdlib\DateTime\DateTime,
    Backend\App\Action\Context,
    Store\Model\StoreManagerInterface,
    Store\Model\ScopeInterface
};
use Emarsys\Emarsys\{
    Model\ResourceModel\Customer,
    Helper\Data as EmarsysHelperData,
    Helper\Logs
};

/**
 * Class Subscriber
 * @package Emarsys\Emarsys\Model\WebDav
 */
class Subscriber extends DataObject
{
    /**
     * @var EmarsysHelperData
     */
    protected $emarsysHelper;

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
     * @var WebDavExport
     */
    protected $webDavExport;

    /**
     * Subscriber constructor.
     *
     * @param EmarsysHelperData $emarsysHelper
     * @param Context $context
     * @param DateTime $date
     * @param StoreManagerInterface $storeManager
     * @param Customer $customerResourceModel
     * @param Logs $logsHelper
     * @param WebDavExport $webDavExport
     */
    public function __construct(
        EmarsysHelperData $emarsysHelper,
        Context $context,
        DateTime $date,
        StoreManagerInterface $storeManager,
        Customer $customerResourceModel,
        Logs $logsHelper,
        WebDavExport $webDavExport
    ) {
        $this->emarsysHelper = $emarsysHelper;
        $this->logsHelper = $logsHelper;
        $this->customerResourceModel = $customerResourceModel;
        $this->storeManager = $storeManager;
        $this->date = $date;
        $this->webDavExport = $webDavExport;
    }

    public function exportSubscribersDataWebDav($data, $logId = null)
    {
        $websiteId = $data['website'];

        if (isset($data['store'])) {
            $storeId = $data['store'];
        } else {
            $storeId = $this->emarsysHelper->getFirstStoreIdOfWebsite($websiteId);
        }

        $store = $this->storeManager->getStore($storeId);
        $storeCode = $store->getCode();
        $data['storeId'] = $storeId;
        $data['website'] = $websiteId;
        $scope = ScopeInterface::SCOPE_WEBSITES;
        $errorCount = true;

        $logsArray['job_code'] = 'initialdbload';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = 'Initial DB data initiated';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Manual';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $storeId;
        $logsArray['website_id'] = $websiteId;
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        if (!$logId) {
            $logId = $this->logsHelper->manualLogs($logsArray, 1);
        }
        $logsArray['id'] = $logId;
        $logsArray['log_action'] = 'sync';
        $logsArray['action'] = 'synced to emarsys';

        $websiteStoreIds = [];
        $websiteStoreIds[] = $storeId;

        $optInStatus = $data['initial_load'];
        if ($optInStatus == 'attribute') {
            $data['subscribeStatus'] = $data['attributevalue'];
        }
        $emarsysFieldNames = [EmarsysHelperData::CUSTOMER_EMAIL, EmarsysHelperData::SUBSCRIBER_ID];
        if ($optInStatus != '') {
            $emarsysFieldNames[] = EmarsysHelperData::OPT_IN;
        }

        $customerValues = $this->customerResourceModel->getSubscribedCustomerCollection(
            $data,
            implode(',', $websiteStoreIds),
            1
        );

        if ($customerValues) {
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
                    $outputFile = $this->emarsysHelper->getCustomerCsvFileName('subscribers', $storeCode);
                    $filePath = $this->emarsysHelper->getContactCsvGenerationPath($outputFile);
                    $handle = fopen($filePath, 'w');

                    //write header to subscribers csv
                    fputcsv($handle, $emarsysFieldNames);

                    foreach ($customerValues as $value) {
                        $values = [];
                        $values[] = $value['subscriber_email'];
                        $values[] = $value['subscriber_id'];

                        if ($optInStatus == 'true') {
                            $values[] = '1';
                        } elseif ($optInStatus == 'empty') {
                            $values[] = 0;
                        } elseif ($optInStatus == 'attribute') {
                            $values[] = '1';
                        }
                        //write subscribers data in csv
                        fputcsv($handle, $values);
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
                        //Subscriber file uploaded to server successfully
                        $errorCount = false;
                        $logsArray['emarsys_info'] = __('Subscriber file uploaded to server successfully for store %1', $store->getName());
                        $logsArray['description'] = 'Emarsys response: ' . $exportStatus['response_body'] . ' File Path: ' . $webDavUrl . $outputFile;
                        $logsArray['message_type'] = 'Success';
                    } else {
                        //Failed to upload file on server
                        $logsArray['emarsys_info'] = __('Failed to upload file on server for store %1', $store->getName());
                        $logsArray['description'] = $exportStatus['response_body'];
                        $logsArray['message_type'] = 'Error';
                    }
                    $this->logsHelper->logs($logsArray);
                } else {
                    //Failed to Login with WebDav Server
                    $logsArray['emarsys_info'] = 'Failed to Login with WebDav Server.';
                    $logsArray['description'] = 'Failed to Login with WebDav Server. Please check your settings and try again. ' . $checkWebDavConnection['response_body'];
                    $logsArray['message_type'] = 'Error';
                    $this->logsHelper->logs($logsArray);
                }
            } else {
                //Invalid WebDAV credentials
                $logsArray['status'] = 'error';
                $logsArray['messages'] = 'Invalid WebDAV credentials.';
                $this->logsHelper->manualLogsUpdate($logsArray);
            }
        } else {
            //No Subscribers found
            $logsArray['emarsys_info'] = __('No Subscribers found');
            $logsArray['description'] = __('No Subscribers found for store %1', $store->getName());
            $logsArray['message_type'] = 'Error';
            $this->logsHelper->logs($logsArray);
        }

        //set error/success state
        if ($errorCount) {
            $logsArray['status'] = 'error';
            $logsArray['messages'] = 'Error in Uploading Subscriber files. Please check.';
        } else {
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Initial DB data completed';
        }
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        $this->logsHelper->manualLogsUpdate($logsArray);

        return $errorCount ? false : true;
    }
}
