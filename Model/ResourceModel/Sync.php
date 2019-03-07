<?php

/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\ResourceModel;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class Sync
 * @package Emarsys\Emarsys\Model\ResourceModel
 */
class Sync extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var int
     */
    protected $_syncEnable;

    /**
     * @var int
     */
    protected $_syncId;

    /**
     * @var int
     */
    protected $_syncAutoCron;

    /**
     * @var
     */
    protected $_connection;

    /**
     * @var string
     */
    protected $_fields;

    /**
     * @var
     */
    protected $_where;

    /**
     * @var
     */
    protected $_title;

    /**
     * @var
     */
    protected $_coll;

    protected $date;

    protected $scopeConfigInterface;


    /**
     * 
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param DateTime $date
     * @param \Emarsys\Emarsys\Model\Logs $emarsysLogs
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        DateTime $date,
        \Emarsys\Emarsys\Model\Logs $emarsysLogs,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        $connectionName = null
    ) {
    
        $this->date = $date;
        $this->emarsysLogs = $emarsysLogs;
        $this->scopeConfigInterface = $scopeConfigInterface;
        parent::__construct($context, $connectionName);
    }

    /**
     * constructor
     */
    public function _construct()
    {
        $this->_init('emarsys_attribute_sync', 'id');
    }



    /**
     * 
     * @param type $entity
     * @param type $storeId
     * @return array
     */
    public function getAttributes($entity = null, $storeId)
    {
        $connection = $this->getConnection();

        if ($entity == 'product') {
            $results = $connection->fetchAll("SELECT * FROM " . $this->getTable('emarsys_emarsys_product_attributes')  . " where store_id =" . $storeId);
        }
        if ($entity == 'customer') {
            $results = $connection->fetchAll("SELECT * FROM " . $this->getTable('emarsys_emarsys_customer_attributes') . " where store_id =" . $storeId);
        }
        if ($entity == 'order') {
            $results = $connection->fetchAll("SELECT * FROM " . $this->getTable('emarsys_emarsys_order_attributes') . " where store_id =" . $storeId);
        }

        if ($entity == 'customproductattributes') {
            $results = $connection->fetchAll("SELECT * FROM " . $this->getTable('emarsys_custom_product_attributes') . " where store_id =" . $storeId);
        }

        return $results;
    }

    /**
     * 
     * @param type $syncId
     * @param int $storeId
     * @return array
     */
    public function getLastSyncDate($syncId = null, $storeId = null)
    {
        if ($storeId == null) {
            $storeId = 1;
        }
        $sql = "SELECT  DATE_FORMAT(max(`finished_at`),'%Y-%m-%d %H:%i:%s') as syncdate FROM " . $this->getTable('emarsys_syncstatus') . " WHERE `id` = ( SELECT MAX(`id`) FROM " . $this->getTable('emarsys_syncstatus') . "  WHERE `status`='SUCCESS' and  `sync_id` = " . $syncId . " and `store_id` = " . $storeId . ")";
        try {
            $lastsyncDate = $this->getConnection()->fetchOne($sql);
            if ($lastsyncDate == null || $lastsyncDate == '') {
                $lastsyncDate = $this->date->date('Y-m-d H:i:s', strtotime('-5 days'));
            }

            return $lastsyncDate;
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'getLastSyncDate');
        }
    }

    /**
     * 
     * @param type $path
     * @param type $scope
     * @param type $scopeId
     * @return array
     */
    public function getDataFromCoreConfig($path, $scope = null, $scopeId = null)
    {
        try {
            if ($scope && $scopeId) {
                return $this->scopeConfigInterface->getValue($path, $scope, $scopeId);
            } else {
                return  $this->scopeConfigInterface->getValue($path);
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $scopeId, 'getDataFromCoreConfig in Sync.php');
        }
    }
}
