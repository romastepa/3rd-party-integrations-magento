<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Cron;

use Emarsys\Emarsys\{
    Helper\Cron as EmarsysCronHelper,
    Model\Api\Contact,
    Model\Logs as EmarsysModelLogs
};

/**
 * Class CustomerBulkExportApi
 *
 * @package Emarsys\Emarsys\Cron
 */
class CustomerBulkExportApi
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
     * CustomerBulkExportApi constructor.
     *
     * @param EmarsysCronHelper $cronHelper
     * @param Contact $contactModel
     * @param EmarsysModelLogs $emarsysLogs
     */
    public function __construct(
        EmarsysCronHelper $cronHelper,
        Contact $contactModel,
        EmarsysModelLogs $emarsysLogs
    ) {
        $this->cronHelper = $cronHelper;
        $this->contactModel = $contactModel;
        $this->emarsysLogs = $emarsysLogs;
    }

    public function execute()
    {
        try {
            $currentCronInfo = $this->cronHelper->getCurrentCronInformation(
                EmarsysCronHelper::CRON_JOB_CUSTOMER_BULK_EXPORT_API
            );

            if (!$currentCronInfo) {
                return;
            }

            $data = \Zend_Json::decode($currentCronInfo->getParams());

            $this->contactModel->syncFullContactUsingApi(
                EmarsysCronHelper::CRON_JOB_CUSTOMER_BULK_EXPORT_API,
                $data
            );
        } catch (\Excepiton $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                0,
                'CustomerBulkExportApi::execute()'
            );
        }
    }
}
