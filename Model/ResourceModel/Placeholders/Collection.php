<?php
namespace Emarsys\Emarsys\Model\ResourceModel\Placeholders;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Emarsys\Emarsys\Model\Placeholders', 'Emarsys\Emarsys\Model\ResourceModel\Placeholders');
    }
}
