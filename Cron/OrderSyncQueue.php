<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Cron;

use Emarsys\Emarsys\Model\Order as EmarsysModelOrder;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Model\Logs;

/**
 * Class OrderSyncQueue
 * @package Emarsys\Emarsys\Cron
 */
class OrderSyncQueue
{
    /**
     * @var EmarsysModelOrder
     */
    protected $emarsysOrderModel;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * OrderSyncQueue constructor.
     * @param EmarsysModelOrder $emarsysOrderModel
     * @param StoreManagerInterface $storeManager
     * @param Logs $emarsysLogs
     */
    public function __construct(
        EmarsysModelOrder $emarsysOrderModel,
        StoreManagerInterface $storeManager,
        Logs $emarsysLogs
    ) {
        $this->emarsysOrderModel = $emarsysOrderModel;
        $this->storeManager = $storeManager;
        $this->emarsysLogs = $emarsysLogs;
    }

    public function execute()
    {
        try {
            set_time_limit(0);
            $stores = $this->storeManager->getStores();
            foreach ($stores as $store) {
                $storeId = $store->getId();
                if ($storeId == 0) {
                    continue;
                }

                $this->emarsysOrderModel->syncOrders(
                    $storeId,
                    \Emarsys\Emarsys\Helper\Data::ENTITY_EXPORT_MODE_AUTOMATIC
                );
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'OrderSyncQueue::execute()'
            );
        }
    }
}
