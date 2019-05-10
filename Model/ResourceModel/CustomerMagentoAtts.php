<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\ResourceModel;

/**
 * Class CustomerMagentoAtts
 * @package Emarsys\Emarsys\Model\ResourceModel
 */
class CustomerMagentoAtts extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('emarsys_magento_customer_attributes', 'id');
    }
}
