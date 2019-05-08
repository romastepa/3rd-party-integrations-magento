<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Cron;

use Emarsys\Emarsys\Model\Order as EmarsysOrderModel;
use Emarsys\Emarsys\Helper\Cron as EmarsysCronHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Emarsys\Emarsys\Model\Logs;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class SmartInsightBulkExport
 * @package Emarsys\Emarsys\Cron
 */
class SmartInsightBulkExport
{
    /**
     * @var EmarsysCronHelper
     */
    protected $cronHelper;

    /**
     * @var JsonHelper
     */
    protected $jsonHelper;

    /**
     * @var EmarsysOrderModel
     */
    protected $emarsysOrderModel;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * SmartInsightBulkExport constructor.
     *
     * @param EmarsysCronHelper $cronHelper
     * @param JsonHelper $jsonHelper
     * @param EmarsysOrderModel $order
     * @param Logs $emarsysLogs
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        EmarsysCronHelper $cronHelper,
        JsonHelper $jsonHelper,
        EmarsysOrderModel $order,
        Logs $emarsysLogs,
        StoreManagerInterface $storeManager
    ) {
        $this->cronHelper = $cronHelper;
        $this->jsonHelper = $jsonHelper;
        $this->emarsysOrderModel =  $order;
        $this->emarsysLogs = $emarsysLogs;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        try {
            set_time_limit(0);
            $currentCronInfo = $this->cronHelper->getCurrentCronInformation(
                \Emarsys\Emarsys\Helper\Cron::CRON_JOB_SI_BULK_EXPORT
            );

            if ($currentCronInfo) {
                $data = $this->jsonHelper->jsonDecode($currentCronInfo->getParams());
                $storeId = $data['storeId'];
                $fromDate = $data['fromDate'];
                $toDate = $data['toDate'];

                $this->emarsysOrderModel->syncOrders(
                    $storeId,
                    \Emarsys\Emarsys\Helper\Data::ENTITY_EXPORT_MODE_MANUAL,
                    $fromDate,
                    $toDate
                );
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'SmartInsightBulkExport::execute()'
            );
        }
    }
}
