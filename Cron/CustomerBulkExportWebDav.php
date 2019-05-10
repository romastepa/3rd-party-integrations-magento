<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Cron;

use Emarsys\Emarsys\Helper\Cron as EmarsysCronHelper;
use Emarsys\Emarsys\Model\WebDav\WebDav;
use Emarsys\Emarsys\Model\Logs;

/**
 * Class CustomerBulkExportWebDav
 * @package Emarsys\Emarsys\Cron
 */
class CustomerBulkExportWebDav
{
    /**
     * @var EmarsysCronHelper
     */
    protected $cronHelper;

    /**
     * @var WebDav
     */
    protected $webDavModel;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * CustomerBulkExportWebDav constructor.
     * @param EmarsysCronHelper $cronHelper
     * @param WebDav $webDavModel
     * @param Logs $emarsysLogs
     */
    public function __construct(
        EmarsysCronHelper $cronHelper,
        WebDav $webDavModel,
        Logs $emarsysLogs
    ) {
        $this->cronHelper = $cronHelper;
        $this->webDavModel = $webDavModel;
        $this->emarsysLogs = $emarsysLogs;
    }

    public function execute()
    {
        try {
            $currentCronInfo = $this->cronHelper->getCurrentCronInformation(
                EmarsysCronHelper::CRON_JOB_CUSTOMER_BULK_EXPORT_WEBDAV
            );

            if (!$currentCronInfo) {
                return;
            }

            $data = \Zend_Json::decode($currentCronInfo->getParams());

            $this->webDavModel->syncFullContactUsingWebDav(
                EmarsysCronHelper::CRON_JOB_CUSTOMER_BULK_EXPORT_WEBDAV,
                $data
            );
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                0,
                'CustomerBulkExportWebDav::execute()'
            );
        }
    }
}
