<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Observer;

use Psr\Log\LoggerInterface;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class PlaceorderNewsletterSubscriber
 * @package Emarsys\Emarsys\Observer
 */
class PlaceorderNewsletterSubscriber implements ObserverInterface
{
    /**
     * PlaceorderNewsletterSubscriber constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Emarsys\Emarsys\Model\Logs $emarsysLogs
     * @param \Emarsys\Emarsys\Helper\Data $dataHelper
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel
     * @param \Magento\Sales\Model\Order $orderModel
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Emarsys\Emarsys\Model\Logs $emarsysLogs,
        \Emarsys\Emarsys\Helper\Data $dataHelper,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel,
        \Magento\Sales\Model\Order $orderModel
    ) {
        $this->_order = $orderModel;
        $this->emarsysLogs = $emarsysLogs;
        $this->customerResourceModel = $customerResourceModel;
        $this->_storeManager = $storeManager;
        $this->dataHelper = $dataHelper;
        $this->_subscriberFactory = $subscriberFactory;
        $this->_checkoutSession = $checkoutSession;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $orderID = $observer->getEvent()->getOrderIds()[0];
            $order = $this->_order->load($orderID);
            $emailID = $order->getCustomerEmail();
            $checkoutNewsSub = $this->_checkoutSession->getData()['newsletter_sub_checkout'];
            if ($checkoutNewsSub) {
                $this->_subscriberFactory->create()->subscribe($emailID);
            }
        } catch (\Exception $e) {
            $storeId = $this->_storeManager->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'PlaceorderNewsletterSubscriber');
        }
    }
}
