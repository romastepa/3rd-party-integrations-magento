<?php
namespace Emarsys\Emarsys\Model\ResourceModel\OrderQueue;

/**
 * Class Collection
 * @package Emarsys\Emarsys\Model\ResourceModel\OrderQueue
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Emarsys\Emarsys\Model\OrderQueue', 'Emarsys\Emarsys\Model\ResourceModel\OrderQueue');
    }
}
