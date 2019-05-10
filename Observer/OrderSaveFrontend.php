<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Model\OrderQueueFactory;
use Emarsys\Emarsys\Model\OrderExportStatusFactory;
use Magento\Checkout\Model\Session;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Customer\Model\Session as CustomerSession;
use Emarsys\Emarsys\Model\Logs;

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
     * @var SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * OrderSaveFrontend constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param OrderQueueFactory $orderQueueFactory
     * @param OrderExportStatusFactory $orderExportStatusFactory
     * @param Session $checkoutSession
     * @param SubscriberFactory $subscriberFactory
     * @param OrderFactory $orderFactory
     * @param Order $orderModel
     * @param CustomerSession $customerSession
     * @param Logs $emarsysLogs
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        OrderQueueFactory $orderQueueFactory,
        OrderExportStatusFactory $orderExportStatusFactory,
        Session $checkoutSession,
        SubscriberFactory $subscriberFactory,
        OrderFactory $orderFactory,
        Order $orderModel,
        CustomerSession $customerSession,
        Logs $emarsysLogs
    ) {
        $this->storeManager = $storeManager;
        $this->orderQueueFactory = $orderQueueFactory;
        $this->orderExportStatusFactory = $orderExportStatusFactory;
        $this->orderFactory = $orderFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->checkoutSession = $checkoutSession;
        $this->order = $orderModel;
        $this->customerSession = $customerSession;
        $this->emarsysLogs = $emarsysLogs;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orderID = $observer->getEvent()->getOrderIds()[0];
        $order = $this->order->load($orderID);
        $emailID = $order->getCustomerEmail();
        $checkoutNewsSub = '';

        //Set customer email, Id and order Ids in session
        $this->newOrderEmailAddress($observer);

        if (isset($this->checkoutSession->getData()['newsletter_sub_checkout'])) {
            $checkoutNewsSub = $this->checkoutSession->getData()['newsletter_sub_checkout'];
        }
        if ($checkoutNewsSub) {
            $this->subscriberFactory->create()->subscribe($emailID);
        }
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
    }

    /**
     * @param $observer
     */
    public function newOrderEmailAddress($observer)
    {
        try {
            $orderIds = $observer->getEvent()->getOrderIds();
            if (empty($orderIds) || !is_array($orderIds)) {
                return;
            }
            foreach ($orderIds as $orderId) {
                $order = $this->order->load($orderId);
                $this->customerSession->setWebExtendCustomerEmail($order->getCustomerEmail());
                if ($order->getCustomerId()) {
                    $this->customerSession->setWebExtendCustomerId($order->getCustomerId());
                }
            }
            $this->customerSession->setWebExtendNewOrderIds($orderIds);
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'newOrderEmailAddress()'
            );
        }

        return;
    }
}

