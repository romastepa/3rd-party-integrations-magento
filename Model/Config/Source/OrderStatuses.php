<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2019 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Config\Source;

use Psr\Log\LoggerInterface;

/**
 * Class OrderStatuses
 * @package Emarsys\Emarsys\Model\Config\Source
 */
class OrderStatuses
{
    /**
     * @var
     */
    protected $resource;
    /**
     * @var
     */
    protected $connection;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Config\Model\ResourceModel\Config $config
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Config\Model\ResourceModel\Config $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->_resource = $resource;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $connection = $this->_resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        $sql = "SELECT * FROM " . $this->config->getTable('sales_order_status');
        try {
            $orderStatusesCollection = $connection->fetchAll($sql);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
        $orderStatusesArray = [];
        foreach ($orderStatusesCollection as $order) {
            $orderStatusesArray[] = ['value' => $order['status'], 'label' => $order['label']];
        }
        return $orderStatusesArray;
    }
}
