<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\ResourceModel\Emarsysproductexport;

/**
 * Class Collection
 * @package Emarsys\Emarsys\Model\ResourceModel\Emarsysproductexport
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
        $this->_init('Emarsys\Emarsys\Model\Emarsysproductexport', 'Emarsys\Emarsys\Model\ResourceModel\Emarsysproductexport');
    }
}
