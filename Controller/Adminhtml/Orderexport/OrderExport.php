<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Orderexport;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Emarsys\Emarsys\Model\Order as EmarsysOrderModel;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Helper\Data as EmarsysDataHelper;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\Timezone as TimeZone;
use Emarsys\Emarsys\Helper\Cron as EmarsysCronHelper;
use Emarsys\Emarsys\Model\EmarsysCronDetails;
use Emarsys\Emarsys\Model\Logs;

/**
 * Class OrderExport
 * @package Emarsys\Emarsys\Controller\Adminhtml\Orderexport
 */
class OrderExport extends Action
{
    /**
     * @var Http
     */
    protected $request;

    /**
     * @var EmarsysOrderModel
     */
    protected $emarsysOrderModel;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var EmarsysDataHelper
     */
    protected $emarsysDataHelper;

    /**
     * @var MessageManagerInterface
     */
    protected $messageManager;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var TimeZone
     */
    protected $timezone;

    /**
     * @var EmarsysCronHelper
     */
    protected $cronHelper;

    /**
     * @var EmarsysCronDetails
     */
    protected $emarsysCronDetails;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * OrderExport constructor.
     * @param Context $context
     * @param Http $request
     * @param EmarsysOrderModel $emarsysOrderModel
     * @param StoreManagerInterface $storeManager
     * @param EmarsysDataHelper $emarsysDataHelper
     * @param DateTime $date
     * @param TimeZone $timezone
     * @param EmarsysCronHelper $cronHelper
     * @param EmarsysCronDetails $emarsysCronDetails
     * @param Logs $emarsysLogs
     */
    public function __construct(
        Context $context,
        Http $request,
        EmarsysOrderModel $emarsysOrderModel,
        StoreManagerInterface $storeManager,
        EmarsysDataHelper $emarsysDataHelper,
        DateTime $date,
        TimeZone $timezone,
        EmarsysCronHelper $cronHelper,
        EmarsysCronDetails $emarsysCronDetails,
        Logs $emarsysLogs
    ) {
        $this->request = $request;
        $this->emarsysOrderModel = $emarsysOrderModel;
        $this->storeManager = $storeManager;
        $this->emarsysDataHelper = $emarsysDataHelper;
        $this->messageManager = $context->getMessageManager();
        $this->date = $date;
        $this->timezone = $timezone;
        $this->cronHelper = $cronHelper;
        $this->emarsysCronDetails = $emarsysCronDetails;
        $this->emarsysLogs = $emarsysLogs;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        try {
            //collect params
            $data = $this->request->getParams();
            $storeId = $data['storeId'];
            $store = $this->storeManager->getStore($storeId);
            $websiteId = $store->getWebsiteId();
            $exportFromDate = $data['fromDate'];
            $exportTillDate = $data['toDate'];
            $resultRedirect = $this->resultRedirectFactory->create();
            $url = $this->getUrl("emarsys_emarsys/orderexport/index", ["store" => $storeId]);

            //validate date range (Bulk export)
            if (isset($exportFromDate) && $exportFromDate != '') {
                $toTimezone = $this->timezone->getDefaultTimezone();
                $fromDate = $this->timezone->date($exportFromDate)
                    ->setTimezone(new \DateTimeZone($toTimezone))
                    ->format('Y-m-d H:i:s');
                $magentoTime = $this->date->date('Y-m-d H:i:s');
                $currentTime = new \DateTime($magentoTime);
                $currentTime->format('Y-m-d H:i:s');
                $datetime2 = new \DateTime($fromDate);
                $interval = $currentTime->diff($datetime2);
                if ($interval->y > 2 || ($interval->y == 2 && $interval->m >= 1) || ($interval->y == 2 && $interval->d >= 1)) {
                    $this->messageManager->addErrorMessage(__('The time frame cannot be more than 2 years.'));

                    return $resultRedirect->setPath($url);
                }
            }

            //check emarsys enabled for the website
            if ($this->emarsysDataHelper->getEmarsysConnectionSetting($websiteId)) {

                //check smart insight enabled for the website
                if ($this->emarsysDataHelper->getCheckSmartInsight($websiteId)) {

                    //collect order collection
                    $orderCollection = $this->emarsysOrderModel->getOrderCollection(
                        EmarsysDataHelper::ENTITY_EXPORT_MODE_MANUAL,
                        $storeId,
                        $exportFromDate,
                        $exportTillDate
                    );

                    //collect creditmemo collection
                    $creditMemoCollection = $this->emarsysOrderModel->getCreditMemoCollection(
                        EmarsysDataHelper::ENTITY_EXPORT_MODE_MANUAL,
                        $storeId,
                        $exportFromDate,
                        $exportTillDate
                    );

                    //check sales collection exist
                    if ((!empty($orderCollection) && $orderCollection->getSize()) || ((!empty($creditMemoCollection)) && $creditMemoCollection->getSize())) {
                        $isCronjobScheduled = $this->cronHelper->checkCronjobScheduled(EmarsysCronHelper::CRON_JOB_SI_BULK_EXPORT, $storeId);
                        if (!$isCronjobScheduled) {
                            //no cron job scheduled yet, schedule a new cron job
                            $cron = $this->cronHelper->scheduleCronjob(EmarsysCronHelper::CRON_JOB_SI_BULK_EXPORT, $storeId);

                            //format and encode data in json to be saved in the table
                            $params = $this->cronHelper->getFormattedParams($data);

                            //save details cron details table
                            $this->emarsysCronDetails->addEmarsysCronDetails($cron->getScheduleId(), $params);

                            $this->messageManager->addSuccessMessage(
                                __(
                                    'A cron named "%1" have been scheduled for smart insight export for the store %2.',
                                    EmarsysCronHelper::CRON_JOB_SI_BULK_EXPORT,
                                    $store->getName()
                                ));
                        } else {
                            //cron job already scheduled
                            $this->messageManager->addErrorMessage(__('A cron is already scheduled to export orders for the store %1 ', $store->getName()));
                        }
                    } else {
                        //no sales data found for this store
                        $this->messageManager->addErrorMessage(__('No sales data found for the store %1 ', $store->getName()));
                    }
                } else {
                    //smart insight is disabled for this website
                    $this->messageManager->addErrorMessage(__('Smart Insight is disabled for the store %1.', $store->getName()));
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
                'OrderExport::execute()'
            );
            //report error
            $this->messageManager->addErrorMessage(__('There was a problem while orders export. %1', $e->getMessage()));
        }

        return $resultRedirect->setPath($url);
    }
}
