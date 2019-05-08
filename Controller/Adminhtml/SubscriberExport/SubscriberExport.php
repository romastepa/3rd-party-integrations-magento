<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\SubscriberExport;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Model\ResourceModel\Customer;
use Magento\Framework\App\Request\Http;
use Emarsys\Emarsys\Helper\Data as DataHelper;
use Magento\Store\Model\ScopeInterface;
use Emarsys\Emarsys\Helper\Cron as EmarsysCronHelper;
use Emarsys\Emarsys\Model\EmarsysCronDetails;
use Emarsys\Emarsys\Model\Logs as EmarsysLogsModel;

/**
 * Class SubscriberExport
 * @package Emarsys\Emarsys\Controller\Adminhtml\SubscriberExport
 */
class SubscriberExport extends Action
{
    const MAX_SUBSCRIBERS_RECORDS = 100000;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Customer
     */
    protected $customerResourceModel;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @var EmarsysCronHelper
     */
    protected $cronHelper;

    /**
     * @var EmarsysCronDetails
     */
    protected $emarsysCronDetails;

    /**
     * @var EmarsysLogsModel
     */
    protected $emarsysLogs;

    /**
     * SubscriberExport constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Customer $customerResourceModel
     * @param Http $request
     * @param DataHelper $dataHelper
     * @param EmarsysCronHelper $cronHelper
     * @param EmarsysCronDetails $cronDetails
     * @param EmarsysLogsModel $emarsysLogs
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Customer $customerResourceModel,
        Http $request,
        DataHelper $dataHelper,
        EmarsysCronHelper $cronHelper,
        EmarsysCronDetails $cronDetails,
        EmarsysLogsModel $emarsysLogs
    ) {
        $this->storeManager = $storeManager;
        $this->customerResourceModel = $customerResourceModel;
        $this->request = $request;
        $this->dataHelper = $dataHelper;
        $this->cronHelper = $cronHelper;
        $this->emarsysCronDetails = $cronDetails;
        $this->emarsysLogs = $emarsysLogs;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $data = $this->request->getParams();
            $storeId = $data['storeId'];
            $scope = ScopeInterface::SCOPE_WEBSITES;
            $store = $this->storeManager->getStore($storeId);
            $websiteId = $store->getWebsiteId();
            $data['website'] = $websiteId;
            $websiteStoreIds = [];
            $websiteStoreIds[] = $storeId;
            $resultRedirect = $this->resultRedirectFactory->create();
            $returnUrl = $this->getUrl("emarsys_emarsys/subscriberexport/index", ["store" => $storeId]);

            //check emarsys enable for website
            if ($this->dataHelper->getEmarsysConnectionSetting($websiteId)) {
                $optInStatus = $this->customerResourceModel->getDataFromCoreConfig(
                    'contacts_synchronization/initial_db_load/initial_db_load',
                    $scope,
                    $websiteId
                );
                $data['initial_load'] = $optInStatus;

                if ($optInStatus == 'attribute') {
                    $subscribedStatus = $this->customerResourceModel->getDataFromCoreConfig(
                        'contacts_synchronization/initial_db_load/attribute_value',
                        $scope,
                        $websiteId
                    );
                    $data['subscribeStatus'] = $subscribedStatus;
                    $data['attributevalue'] = $subscribedStatus;
                }

                //get subscribers collection
                $subscriberCollection = $this->customerResourceModel->getSubscribedCustomerCollection(
                    $data,
                    implode(',', $websiteStoreIds),
                    false
                );
                if (!empty($subscriberCollection)) {
                    $cronJobScheduled = false;
                    $cronJobName = '';

                    if (count($subscriberCollection) <= self::MAX_SUBSCRIBERS_RECORDS) {
                        //export subscribers through API
                        $isCronjobScheduled = $this->cronHelper->checkCronjobScheduled(EmarsysCronHelper::CRON_JOB_SUBSCRIBERS_BULK_EXPORT_API, $storeId);
                        if (!$isCronjobScheduled) {
                            //no cron job scheduled yet, schedule a new cron job
                            $cron = $this->cronHelper->scheduleCronjob(EmarsysCronHelper::CRON_JOB_SUBSCRIBERS_BULK_EXPORT_API, $storeId);
                            $cronJobScheduled = true;
                            $cronJobName = EmarsysCronHelper::CRON_JOB_SUBSCRIBERS_BULK_EXPORT_API;
                        }
                    } else {
                        //export subscribers through WebDav
                        $isCronjobScheduled = $this->cronHelper->checkCronjobScheduled(EmarsysCronHelper::CRON_JOB_SUBSCRIBERS_BULK_EXPORT_WEBDAV, $storeId);
                        if (!$isCronjobScheduled) {
                            //no cron job scheduled yet, schedule a new cron job
                            $cron = $this->cronHelper->scheduleCronjob(EmarsysCronHelper::CRON_JOB_SUBSCRIBERS_BULK_EXPORT_WEBDAV, $storeId);
                            $cronJobScheduled = true;
                            $cronJobName = EmarsysCronHelper::CRON_JOB_SUBSCRIBERS_BULK_EXPORT_WEBDAV;
                        }
                    }

                    if ($cronJobScheduled) {
                        //format and encode data in json to be saved in the table
                        $params = $this->cronHelper->getFormattedParams($data);

                        //save details in cron details table
                        $this->emarsysCronDetails->addEmarsysCronDetails($cron->getScheduleId(), $params);

                        $this->messageManager->addSuccessMessage(
                            __(
                                'A cron named "%1" have been scheduled for subscribers export for the store %2.',
                                $cronJobName,
                                $store->getName()
                            ));
                    } else {
                        //cron job already scheduled
                        $this->messageManager->addErrorMessage(__('A cron is already scheduled to export subscribers for the store %1 ', $store->getName()));
                    }
                } else {
                    //no subscribers found for the store
                    $this->messageManager->addErrorMessage(__('No Subscribers Found for the Store %1.', $store->getName()));
                }
            } else {
                //emarsys is disabled for this website
                $this->messageManager->addErrorMessage(__('Emarsys is disabled for the website %1', $websiteId));
            }
        } catch (\Exception $e) {
            //add exception to logs
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'SubscriberExport::execute()'
            );
            //report error
            $this->messageManager->addErrorMessage(__('There was a problem while subscribers export. %1', $e->getMessage()));
        }

        return $resultRedirect->setPath($returnUrl);
    }
}
