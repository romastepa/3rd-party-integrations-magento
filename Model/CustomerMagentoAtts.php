<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model;

/**
 * Class CustomerMagentoAtts
 * @package Emarsys\Emarsys\Model
 */
class CustomerMagentoAtts extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init('Emarsys\Emarsys\Model\ResourceModel\CustomerMagentoAtts');
    }
}

