<?php
namespace Emarsys\Emarsys\Model\ResourceModel;

/**
 * Class OrderQueue
 * @package Emarsys\Emarsys\Model\ResourceModel
 */
class OrderQueue extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('emarsys_order_queue', 'id');
    }
}
