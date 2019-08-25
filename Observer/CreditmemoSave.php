<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Observer;

use Emarsys\Emarsys\Model\OrderQueueFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class CreditmemoSave
 * @package Emarsys\Emarsys\Observer
 */
class CreditmemoSave implements ObserverInterface
{
    /**
     * @var OrderQueueFactory
     */
    protected $orderQueueModel;

    /**
     * CreditmemoSave constructor.
     * @param OrderQueueFactory $orderQueueFactory
     */
    public function __construct(
        OrderQueueFactory $orderQueueFactory
    ) {
        $this->orderQueueFactory = $orderQueueFactory;
    }

    public function execute(Observer $observer)
    {
        $orderQueue = $this->orderQueueFactory->create();

        $orderQueueData = $orderQueue->getCollection()
            ->addFieldToFilter('entity_id', $observer->getEvent()->getDataObject()->getId())
            ->addFieldToFilter('entity_type_id', 2);

        if ($orderQueueData->getSize()) {
            $orderQueue = $orderQueueData->getFirstItem();
        }

        $orderQueue->setEntityId($observer->getEvent()->getDataObject()->getId());
        $orderQueue->setEntityTypeId(2);
        $orderQueue->setWebsiteId($observer->getEvent()->getDataObject()->getOrder()->getStore()->getWebsiteId());
        $orderQueue->setStoreId($observer->getEvent()->getDataObject()->getOrder()->getStoreId());
        $orderQueue->save();
    }
}
