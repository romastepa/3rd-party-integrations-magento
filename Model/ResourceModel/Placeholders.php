<?php
namespace Emarsys\Emarsys\Model\ResourceModel;

/**
 * Class Placeholders
 * @package Emarsys\Emarsys\Model\ResourceModel
 */
class Placeholders extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('emarsys_placeholders_mapping', 'id');
    }
}
