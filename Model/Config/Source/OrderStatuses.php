<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Config\Source;

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
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Config\Model\ResourceModel\Config $config,
        \Emarsys\Emarsys\Model\Logs $emarsysLogs,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_resource = $resource;
        $this->storeManager = $storeManager;
        $this->emarsysLogs = $emarsysLogs;
        $this->config = $config;
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
            $storeId = $this->storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'toOptionArray(orderStatus)');
        }
        $orderStatusesArray = [];
        foreach ($orderStatusesCollection as $order) {
            $orderStatusesArray[] = ['value' => $order['status'], 'label' => $order['label']];
        }
        return $orderStatusesArray;
    }
}
