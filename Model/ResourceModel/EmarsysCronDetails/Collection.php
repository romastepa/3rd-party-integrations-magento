<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\ResourceModel\EmarsysCronDetails;

/**
 * Class Collection
 * @package Emarsys\Emarsys\Model\ResourceModel\EmarsysCronDetails
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Emarsys\Emarsys\Model\EmarsysCronDetails', 'Emarsys\Emarsys\Model\ResourceModel\EmarsysCronDetails');
    }
}
