<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Cron;

use Emarsys\Emarsys\{
    Helper\Cron as EmarsysCronHelper,
    Model\Api\Contact,
    Model\Logs as EmarsysModelLogs,
    Model\Api\Subscriber
};

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
     * @var Contact
     */
    protected $contactModel;

    /**
     * @var EmarsysModelLogs
     */
    protected $emarsysLogs;

    /**
     * @var Subscriber
     */
    protected $subscriberEmarsysApi;

    /**
     * SubscriberBulkExportApi constructor.
     * @param EmarsysCronHelper $cronHelper
     * @param Contact $contactModel
     * @param EmarsysModelLogs $emarsysLogs
     * @param Subscriber $subscriberEmarsysApi
     */
    public function __construct(
        EmarsysCronHelper $cronHelper,
        Contact $contactModel,
        EmarsysModelLogs $emarsysLogs,
        Subscriber $subscriberEmarsysApi
    ) {
        $this->cronHelper = $cronHelper;
        $this->contactModel = $contactModel;
        $this->emarsysLogs = $emarsysLogs;
        $this->subscriberEmarsysApi = $subscriberEmarsysApi;
    }

    public function execute()
    {
        try {
            set_time_limit(0);
            $currentCronInfo = $this->cronHelper->getCurrentCronInformation(
                EmarsysCronHelper::CRON_JOB_SUBSCRIBERS_BULK_EXPORT_API
            );

            if (!$currentCronInfo) {
                return;
            }

            $data = \Zend_Json::decode($currentCronInfo->getParams());

            //sync subscribers data to emarsys
            $this->subscriberEmarsysApi->syncMultipleSubscriber(
                EmarsysCronHelper::CRON_JOB_SUBSCRIBERS_BULK_EXPORT_API,
                $data
            );
        } catch (\Excepiton $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                0,
                'SubscriberBulkExportApi::execute()'
            );
        }
    }
}
