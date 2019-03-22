<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2019 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Cron;

use Emarsys\Emarsys\Model\Order as EmarsysModelOrder;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * OrderSyncQueue constructor.
     *
     * @param EmarsysModelOrder $emarsysOrderModel
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        EmarsysModelOrder $emarsysOrderModel,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->emarsysOrderModel = $emarsysOrderModel;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    public function execute()
    {
        try {
            set_time_limit(0);
            $stores = $this->storeManager->getStores();
            foreach ($stores as $storeId => $store) {
                $this->emarsysOrderModel->syncOrders(
                    $storeId,
                    \Emarsys\Emarsys\Helper\Data::ENTITY_EXPORT_MODE_AUTOMATIC
                );
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
