<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2019 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Observer;

use Emarsys\Emarsys\Model\OrderQueueFactory as OrderQueueFactoryAlias;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class OrderSave
 * @package Emarsys\Emarsys\Observer
 */
class OrderSave implements ObserverInterface
{
    /**
     * @var OrderQueueFactoryAlias
     */
    private $orderQueueFactory;

    /**
     * OrderSave constructor.
     *
     * @param OrderQueueFactoryAlias $orderQueueFactory
     */
    public function __construct(
        OrderQueueFactoryAlias $orderQueueFactory
    ) {
        $this->orderQueueFactory = $orderQueueFactory;
    }

    public function execute(Observer $observer)
    {
        $orderQueue = $this->orderQueueFactory->create();
        $orderQueueData = $orderQueue->getCollection()
            ->addFieldToFilter('entity_id', $observer->getEvent()->getOrder()->getId())
            ->addFieldToFilter('entity_type_id', 1);

        if ($orderQueueData->getSize()) {
            $orderQueue = $orderQueueData->getFirstItem();
        }

        if ($observer->getEvent()->getOrder()->getState() == \Magento\Sales\Model\Order::STATE_CLOSED) {
            return true;
        }

        $orderQueue->setEntityId($observer->getEvent()->getOrder()->getId());
        $orderQueue->setEntityTypeId(1);
        $orderQueue->setWebsiteId($observer->getEvent()->getOrder()->getStore()->getWebsiteId());
        $orderQueue->setStoreId($observer->getEvent()->getOrder()->getStoreId());
        $orderQueue->save();
    }
}
