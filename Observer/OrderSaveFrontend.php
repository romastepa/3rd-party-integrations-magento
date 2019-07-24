<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2019 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Observer;

use Magento\Framework\Event\ObserverInterface;
use Emarsys\Emarsys\Model\OrderQueueFactory;
use Magento\Checkout\Model\Session;
use Magento\Newsletter\Model\SubscriberFactory;
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
     * @var OrderQueueFactory
     */
    protected $orderQueueFactory;

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
     * @param OrderQueueFactory $orderQueueFactory
     * @param Session $checkoutSession
     * @param SubscriberFactory $subscriberFactory
     * @param Order $orderModel
     * @param CustomerSession $customerSession
     * @param Logs $emarsysLogs
     */
    public function __construct(
        OrderQueueFactory $orderQueueFactory,
        Session $checkoutSession,
        SubscriberFactory $subscriberFactory,
        Order $orderModel,
        CustomerSession $customerSession,
        Logs $emarsysLogs
    ) {
        $this->orderQueueFactory = $orderQueueFactory;
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
        $orderIds = $observer->getEvent()->getOrderIds();
        $order = $this->order->load($orderIds[0]);
        $emailId = $order->getCustomerEmail();
        $checkoutNewsSub = false;

        //Set customer email, Id and order Ids in session
        $this->newOrderEmailAddress($observer);

        if (isset($this->checkoutSession->getData()['newsletter_sub_checkout'])) {
            $checkoutNewsSub = $this->checkoutSession->getData('newsletter_sub_checkout');
        }
        if ($checkoutNewsSub) {
            $this->subscriberFactory->create()->subscribe($emailId);
        }

        foreach ($orderIds as $orderId) {
            $orderQueue = $this->orderQueueFactory->create()
                ->load($orderId, 'entity_id');

            $orderQueue->setEntityId($orderId);
            $orderQueue->setEntityTypeId(1);
            $orderQueue->setWebsiteId($order->getStore()->getWebsiteId());
            $orderQueue->setStoreId($order->getStoreId());
            $orderQueue->save();
        }
    }

    /**
     * @param $observer
     * @return bool|void
     */
    public function newOrderEmailAddress($observer)
    {
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

        return true;
    }
}

