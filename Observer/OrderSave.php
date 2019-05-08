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
 * Class OrderSave
 * @package Emarsys\Emarsys\Observer
 */
class OrderSave implements ObserverInterface
{
    private $logger;

    protected $customerFactory;

    protected $orderQueueModel;

    protected $_responseFactory;

    protected $_url;

    /**
     * OrderSave constructor.
     * @param LoggerInterface $logger
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Emarsys\Emarsys\Model\OrderQueueFactory $orderQueueFactory
     * @param \Emarsys\Emarsys\Model\OrderExportStatusFactory $orderExportStatusFactory
     * @param \Magento\Framework\App\ResponseFactory $responseFactory
     * @param \Magento\Framework\UrlInterface $url
     */
    public function __construct(
        LoggerInterface $logger,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Emarsys\Emarsys\Model\OrderQueueFactory $orderQueueFactory,
        \Emarsys\Emarsys\Model\OrderExportStatusFactory $orderExportStatusFactory,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->logger = $logger;
        $this->_storeManager = $storeManager;
        $this->orderQueueFactory = $orderQueueFactory;
        $this->customerFactory = $customerFactory;
        $this->_responseFactory = $responseFactory;
        $this->orderExportStatusFactory = $orderExportStatusFactory;
        $this->_url = $url;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orderExportStatusData = $this->orderExportStatusFactory->create()->getCollection()->addFieldToFilter('order_id', $observer->getEvent()->getOrder()->getId());
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
        $orderStatus->setOrderId($observer->getEvent()->getOrder()->getId());
        $orderStatus->setExported(0);
        $orderStatus->setStatusCode($observer->getEvent()->getOrder()->getStatus());
        $orderStatus->save();
        $orderQueueData = $this->orderQueueFactory->create()->getCollection()->addFieldToFilter('entity_id', $observer->getEvent()->getOrder()->getId());
        if (empty($orderQueueData->getData())) {
            $orderQueue = $this->orderQueueFactory->create();
        } else {
            $orderData = $orderQueueData->getData();
            $orderQueue = $this->orderQueueFactory->create()->load($orderData[0]['id']);
        }

        $orderQueue->setEntityId($observer->getEvent()->getOrder()->getId());
        $orderQueue->setEntityTypeId(1);
        $orderQueue->setWebsiteId($observer->getEvent()->getOrder()->getStore()->getWebsiteId());
        $orderQueue->setStoreId($observer->getEvent()->getOrder()->getStoreId());
        $orderQueue->save();
    }
}
