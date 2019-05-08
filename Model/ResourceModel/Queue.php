<?php
namespace Emarsys\Emarsys\Model\ResourceModel;

/**
 * Class Queue
 * @package Emarsys\Emarsys\Model\ResourceModel
 */
class Queue extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('emarsys_suite2_queue', 'id');
    }
}
