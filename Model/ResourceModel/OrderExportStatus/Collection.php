<?php
namespace Emarsys\Emarsys\Model\ResourceModel\OrderExportStatus;

/**
 * Class Collection
 * @package Emarsys\Emarsys\Model\ResourceModel\OrderExportStatus
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Emarsys\Emarsys\Model\OrderExportStatus', 'Emarsys\Emarsys\Model\ResourceModel\OrderExportStatus');
    }
}
