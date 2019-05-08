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

/**
 * Class CustomerBulkExportApi
 * @package Emarsys\Emarsys\Cron
 */
class CustomerBulkExportApi
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
     * CustomerBulkExportApi constructor.
     * @param EmarsysCronHelper $cronHelper
     * @param Data $jsonHelper
     * @param Contact $contactModel
     * @param StoreManagerInterface $storeManagerInterface
     * @param EmarsysModelLogs $emarsysLogs
     */
    public function __construct(
        EmarsysCronHelper $cronHelper,
        Data $jsonHelper,
        Contact $contactModel,
        StoreManagerInterface $storeManagerInterface,
        EmarsysModelLogs $emarsysLogs
    ) {
        $this->cronHelper = $cronHelper;
        $this->jsonHelper = $jsonHelper;
        $this->contactModel = $contactModel;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->emarsysLogs = $emarsysLogs;
    }

    public function execute()
    {
        try {
            $storeId = $this->storeManagerInterface->getStore()->getId();

            $currentCronInfo = $this->cronHelper->getCurrentCronInformation(
                EmarsysCronHelper::CRON_JOB_CUSTOMER_BULK_EXPORT_API
            );

            if (!$currentCronInfo) return;

            $data = $this->jsonHelper->jsonDecode($currentCronInfo->getParams());

            $this->contactModel->syncFullContactUsingApi(
                EmarsysCronHelper::CRON_JOB_CUSTOMER_BULK_EXPORT_API,
                $data
            );
        } catch (\Excepiton $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $storeId,
                'CustomerBulkExportApi::execute()'
            );
        }
    }
}
