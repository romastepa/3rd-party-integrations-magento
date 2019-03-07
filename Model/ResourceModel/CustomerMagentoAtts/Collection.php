<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\ResourceModel\CustomerMagentoAtts;

/**
 * Class Collection
 * @package Emarsys\Emarsys\Model\ResourceModel\CustomerMagentoAtts
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Initializes collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Emarsys\Emarsys\Model\CustomerMagentoAtts', 'Emarsys\Emarsys\Model\ResourceModel\CustomerMagentoAtts');
    }
}
