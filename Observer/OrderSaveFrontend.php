<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2019 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Model\OrderQueueFactory;
use Emarsys\Emarsys\Model\OrderExportStatusFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Class OrderSaveFrontend
 * @package Emarsys\Emarsys\Observer
 */
class OrderSaveFrontend implements ObserverInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var OrderQueueFactory
     */
    protected $orderQueueFactory;

    /**
     * @var OrderExportStatusFactory
     */
    protected $orderExportStatusFactory;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * OrderSaveFrontend constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param OrderQueueFactory $orderQueueFactory
     * @param OrderExportStatusFactory $orderExportStatusFactory
     * @param OrderFactory $orderFactory
     * @param Order $orderModel
     * @param LoggerInterface $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        OrderQueueFactory $orderQueueFactory,
        OrderExportStatusFactory $orderExportStatusFactory,
        OrderFactory $orderFactory,
        Order $orderModel,
        LoggerInterface $logger
    ) {
        $this->storeManager = $storeManager;
        $this->orderQueueFactory = $orderQueueFactory;
        $this->orderExportStatusFactory = $orderExportStatusFactory;
        $this->orderFactory = $orderFactory;
        $this->order = $orderModel;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $orderExportStatusData = $this->orderExportStatusFactory->create()->getCollection()->addFieldToFilter('order_id', $observer->getEvent()->getOrderIds()[0]);
            $orderExported = false;

            if (empty($orderExportStatusData->getData())) {
                $orderStatus = $this->orderExportStatusFactory->create();
            } else {
                $orderStatusData = $orderExportStatusData->getData();
                $orderStatus = $this->orderExportStatusFactory->create()->load($orderStatusData[0]['id']);
                if ($orderStatus->getExported() == 1) {
                    $orderExported = true;
                }
            }
            if ($orderExported == true) {
                return;
            }
            $order = $this->orderFactory->create()->load($observer->getEvent()->getOrderIds()[0]);
            $orderStatus->setOrderId($observer->getEvent()->getOrderIds()[0]);
            $orderStatus->setExported(0);
            $orderStatus->setStatusCode($order->getStatus());
            $orderStatus->save();
            $orderQueueData = $this->orderQueueFactory->create()->getCollection()
                ->addFieldToFilter('entity_id', $observer->getEvent()->getOrderIds()[0]);

            if (empty($orderQueueData->getData())) {
                $orderQueue = $this->orderQueueFactory->create();
            } else {
                $orderData = $orderQueueData->getData();
                $orderQueue = $this->orderQueueFactory->create()->load($orderData[0]['id']);
            }

            $orderQueue->setEntityId($observer->getEvent()->getOrderIds()[0]);
            $orderQueue->setEntityTypeId(1);
            $orderQueue->setWebsiteId($order->getStore()->getWebsiteId());
            $orderQueue->setStoreId($order->getStoreId());
            $orderQueue->save();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}

