<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\ResourceModel;

/**
 * Class LogSchedule
 * @package Emarsys\Emarsys\Model\ResourceModel
 */
class LogSchedule extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Model Initialization
     * @return void
     */
    protected function _construct()
    {
        $this->_init('emarsys_log_cron_schedule', 'id');
    }
}
