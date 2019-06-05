<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\ResourceModel;

/**
 * Class Logs
 * @package Emarsys\Emarsys\Model\ResourceModel
 */
class Logs extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('emarsys_log_details', 'id');
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getLogsData()
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getMainTable(), ['created_at', 'description', 'message_type'])
            ->order('id DESC');

        return $this->getConnection()->fetchAll($select);
    }
}
