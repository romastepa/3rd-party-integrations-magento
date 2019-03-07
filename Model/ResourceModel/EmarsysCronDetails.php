<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\ResourceModel;

/**
 * Class EmarsysCronDetails
 * @package Emarsys\Emarsys\Model\ResourceModel
 */
class EmarsysCronDetails extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected $_isPkAutoIncrement = false;

    /**
     * Model Initialization
     * @return void
     */
    protected function _construct()
    {
        $this->_init('emarsys_cron_details', 'schedule_id');
    }
}
