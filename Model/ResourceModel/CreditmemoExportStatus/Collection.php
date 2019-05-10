<?php
namespace Emarsys\Emarsys\Model\ResourceModel\CreditmemoExportStatus;

/**
 * Class Collection
 * @package Emarsys\Emarsys\Model\ResourceModel\CreditmemoExportStatus
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Emarsys\Emarsys\Model\CreditmemoExportStatus', 'Emarsys\Emarsys\Model\ResourceModel\CreditmemoExportStatus');
    }
}
