<?php
namespace Emarsys\Emarsys\Model\ResourceModel\Queue;

/**
 * Class Collection
 * @package Emarsys\Emarsys\Model\ResourceModel\Queue
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Emarsys\Emarsys\Model\Queue', 'Emarsys\Emarsys\Model\ResourceModel\Queue');
    }
}
