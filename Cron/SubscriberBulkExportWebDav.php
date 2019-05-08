<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Cron;

use Emarsys\Emarsys\Helper\Cron as EmarsysCronHelper;
use Magento\Framework\Json\Helper\Data;
use Emarsys\Emarsys\Model\WebDav\WebDav;
use Emarsys\Emarsys\Model\Logs;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class SubscriberBulkExportWebDav
 * @package Emarsys\Emarsys\Cron
 */
class SubscriberBulkExportWebDav
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
     * @var WebDav
     */
    protected $webDavModel;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager ;

    /**
     * CustomerBulkExportWebDav constructor.
     * @param EmarsysCronHelper $cronHelper
     * @param Data $jsonHelper
     * @param WebDav $webDavModel
     */
    public function __construct(
        EmarsysCronHelper $cronHelper,
        Data $jsonHelper,
        WebDav $webDavModel,
        Logs $emarsysLogs,
        StoreManagerInterface $storeManager
    ) {
        $this->cronHelper = $cronHelper;
        $this->jsonHelper = $jsonHelper;
        $this->webDavModel = $webDavModel;
        $this->emarsysLogs = $emarsysLogs;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        try {
            $currentCronInfo = $this->cronHelper->getCurrentCronInformation(
                EmarsysCronHelper::CRON_JOB_SUBSCRIBERS_BULK_EXPORT_WEBDAV
            );

            if (!$currentCronInfo) return;

            $data = $this->jsonHelper->jsonDecode($currentCronInfo->getParams());

            $this->webDavModel->syncFullContactUsingWebDav(
                EmarsysCronHelper::CRON_JOB_SUBSCRIBERS_BULK_EXPORT_WEBDAV,
                $data
            );
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'SubscriberBulkExportWebDav::execute()'
            );
        }
    }
}
