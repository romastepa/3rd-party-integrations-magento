<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\WebDav;

use Magento\Framework\DataObject;
use Emarsys\Emarsys\Helper\Data;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Emarsys\Emarsys\Helper\Logs;
use Emarsys\Emarsys\Helper\Cron as EmarsysCronHelper;
use Emarsys\Emarsys\Model\WebDav\Subscriber as WebDavSubscriber;
use Emarsys\Emarsys\Model\WebDav\Contact as WebDavContact;
use Magento\Store\Model\ScopeInterface;

/**
 * Class WebDav
 * @package Emarsys\Emarsys\Model\WebDav
 */
class WebDav extends DataObject
{
    /**
     * @var Data
     */
    protected $emarsysHelper;

    /**
     * @var Logs
     */
    protected $logsHelper;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var Subscriber
     */
    protected $webDavSubscriber;

    /**
     * @var Contact
     */
    protected $webDavContact;

    /**
     * @var EmarsysCronHelper
     */
    protected $cronHelper;

    /**
     * WebDav constructor.
     * @param Data $emarsysHelper
     * @param Logs $logsHelper
     * @param DateTime $date
     * @param Subscriber $webDavSubscriber
     * @param Contact $webDavContact
     * @param EmarsysCronHelper $cronHelper
     */
    public function __construct(
        Data $emarsysHelper,
        Logs $logsHelper,
        DateTime $date,
        WebDavSubscriber $webDavSubscriber,
        WebDavContact $webDavContact,
        EmarsysCronHelper $cronHelper
    ) {
        $this->emarsysHelper = $emarsysHelper;
        $this->logsHelper = $logsHelper;
        $this->date = $date;
        $this->webDavSubscriber = $webDavSubscriber;
        $this->webDavContact = $webDavContact;
        $this->cronHelper = $cronHelper;
    }

    /**
     * @param $exportMode
     * @param $data
     */
    public function syncFullContactUsingWebDav($exportMode, $data)
    {
        $websiteId = $data['website'];

        if (isset($data['store'])) {
            $storeId = $data['store'];
        } else {
            $storeId = $this->emarsysHelper->getFirstStoreIdOfWebsite($websiteId);
        }
        $scope = ScopeInterface::SCOPE_WEBSITES;
        $errorStatus = true;
        $jobDetails = $this->cronHelper->getJobDetail($exportMode);

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
        if ($this->emarsysHelper->getEmarsysConnectionSetting($websiteId)) {

            //webDav credentials from admin configurations
            $webDavCredentials = $this->emarsysHelper->collectWebDavCredentials($scope, $websiteId);
            if ($webDavCredentials && !empty($webDavCredentials)) {

                //export Data to WebDav
                $errorStatus = $this->exportDataToWebDav($exportMode, $data, $logId);

            } else {
                //Invalid WebDAV credentials
                $logsArray['emarsys_info'] = 'Invalid WebDAV credentials.';
                $logsArray['description'] = 'Invalid WebDAV credentials. Please check your settings and try again';
                $logsArray['message_type'] = 'Error';
                $this->logsHelper->manualLogsUpdate($logsArray);
            }
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

    /**
     * @param $exportMode
     * @param $data
     * @param $logId
     * @return bool
     */
    public function exportDataToWebDav($exportMode, $data, $logId)
    {
        $errorStatus = true;

        switch ($exportMode) {
            case EmarsysCronHelper::CRON_JOB_INITIAL_DB_LOAD:
                $customerExportStatus = $this->webDavContact->exportCustomerDataWebDav($data, $logId);
                $subscriberExportStatus = $this->webDavSubscriber->exportSubscribersDataWebDav($data, $logId);

                if ($subscriberExportStatus && $customerExportStatus) {
                    $errorStatus = false;
                }
                break;
            case EmarsysCronHelper::CRON_JOB_CUSTOMER_BULK_EXPORT_WEBDAV:
                $customerExportStatus = $this->webDavContact->exportCustomerDataWebDav($data, $logId);
                if ($customerExportStatus) {
                    $errorStatus = false;
                }
                break;
            case EmarsysCronHelper::CRON_JOB_SUBSCRIBERS_BULK_EXPORT_WEBDAV:
                $subscriberExportStatus = $this->webDavSubscriber->exportSubscribersDataWebDav($data, $logId);
                if ($subscriberExportStatus) {
                    $errorStatus = false;
                }
                break;
            default:
                $customerExportStatus = $this->webDavContact->exportCustomerDataWebDav($data, $logId);
                if ($customerExportStatus) {
                    $errorStatus = false;
                }
                break;
        }

        return $errorStatus;
    }
}
