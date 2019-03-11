<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Emarsys\Emarsys\Helper\Data;
use Magento\Framework\App\Cache\Manager;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\Timezone as TimeZone;
use Symfony\Component\Config\Definition\Exception\Exception;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class Product
 * @package Emarsys\Emarsys\Model\ResourceModel
 */
class Emrattribute extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('emarsys_emarsys_product_attributes', 'id');
    }

}
