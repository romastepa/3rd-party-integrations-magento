<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\ResourceModel\ContactFieldOption;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package Emarsys\Emarsys\Model\ResourceModel\ContactFieldOption
 */
class Collection extends AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Emarsys\Emarsys\Model\ContactFieldOption',
            'Emarsys\Emarsys\Model\ResourceModel\ContactFieldOption'
        );
    }
}
