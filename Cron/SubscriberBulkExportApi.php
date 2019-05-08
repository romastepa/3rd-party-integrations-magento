<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Cron;

use Emarsys\Emarsys\Helper\Cron as EmarsysCronHelper;
use Magento\Framework\Json\Helper\Data;
use Emarsys\Emarsys\Model\Api\Contact;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Model\Logs as EmarsysModelLogs;
use Emarsys\Emarsys\Model\Api\Subscriber;

/**
 * Class SubscriberBulkExportApi
 * @package Emarsys\Emarsys\Cron
 */
class SubscriberBulkExportApi
{
    /**
     * @var EmarsysCronHelper
     */
    protected $cronHelper;

    /**
     * @var Data
     */
    protected $jsonHelper;

    /**
     * @var Contact
     */
    protected $contactModel;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var EmarsysModelLogs
     */
    protected $emarsysLogs;

    /**
     * SubscriberBulkExportApi constructor.
     * @param EmarsysCronHelper $cronHelper
     * @param Data $jsonHelper
     * @param Contact $contactModel
     * @param StoreManagerInterface $storeManagerInterface
     * @param EmarsysModelLogs $emarsysLogs
     * @param Subscriber $subscriberEmarsysApi
     */
    public function __construct(
        EmarsysCronHelper $cronHelper,
        Data $jsonHelper,
        Contact $contactModel,
        StoreManagerInterface $storeManagerInterface,
        EmarsysModelLogs $emarsysLogs,
        Subscriber $subscriberEmarsysApi
    ) {
        $this->cronHelper = $cronHelper;
        $this->jsonHelper = $jsonHelper;
        $this->contactModel = $contactModel;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->emarsysLogs = $emarsysLogs;
        $this->subscriberEmarsysApi = $subscriberEmarsysApi;
    }

    public function execute()
    {
        try {
            $storeId = $this->storeManagerInterface->getStore()->getId();

            //collect details from cron details table
            $currentCronInfo = $this->cronHelper->getCurrentCronInformation(
                EmarsysCronHelper::CRON_JOB_SUBSCRIBERS_BULK_EXPORT_API
            );

            if (!$currentCronInfo) return;

            //convert json into array format
            $data = $this->jsonHelper->jsonDecode($currentCronInfo->getParams());

            //sync subscribers data to emarsys
            $this->subscriberEmarsysApi->syncMultipleSubscriber(
                EmarsysCronHelper::CRON_JOB_SUBSCRIBERS_BULK_EXPORT_API,
                $data
            );
        } catch (\Excepiton $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $storeId,
                'SubscriberBulkExportApi::execute()'
            );
        }
    }
}
