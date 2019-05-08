<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Eav\Model\Entity\Type;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Store\Api\StoreRepositoryInterface;
use Emarsys\Emarsys\Helper\Data as EmarsysDataHelper;

/**
 * Class Order
 * @package Emarsys\Emarsys\Model\ResourceModel
 */
class Order extends AbstractDb
{
    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * @var Attribute
     */
    protected $attribute;

    /**
     * @var Type
     */
    protected $entityType;

    /**
     * @var EmarsysDataHelper
     */
    protected $emarsysDataHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var array
     */
    protected $emarsysOrderFields = [];

    /**
     * Order constructor.
     *
     * @param Context $context
     * @param Type $entityType
     * @param Attribute $attribute
     * @param StoreRepositoryInterface $storeRepository
     * @param null $connectionName
     */
    public function __construct(
        Context $context,
        Type $entityType,
        Attribute $attribute,
        StoreRepositoryInterface $storeRepository,
        EmarsysDataHelper $emarsysDataHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Emarsys\Emarsys\Model\Logs $emarsysLogs,
        $connectionName = null
    ) {
        $this->entityType = $entityType;
        $this->attribute = $attribute;
        $this->storeRepository = $storeRepository;
        $this->emarsysDataHelper = $emarsysDataHelper;
        $this->_storeManager = $storeManager;
        $this->emarsysLogs = $emarsysLogs;
        parent::__construct($context, $connectionName);
    }

    /**
     * Define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('emarsys_order_field_mapping', 'id');
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getEmarsysAttrCount($storeId)
    {
        $emarsysFieldCount = $this->getConnection()->fetchOne("SELECT count(*) FROM " .
            $this->getTable('emarsys_event_mapping') . " WHERE store_id=" . $storeId . "");
        return $emarsysFieldCount;
    }

    /**
     * @param $storeId
     * @return string
     */
    public function checkOrderMapping($storeId)
    {
        $customerAttributes = $this->getConnection()->fetchOne("SELECT count(*) FROM " .
            $this->getTable('emarsys_event_mapping') . " WHERE store_id =" . $storeId);
        return $customerAttributes;
    }

    /**
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function getSalesOrderColumnNames()
    {
        $stmt = $this->getConnection()->query("
            SELECT `COLUMN_NAME` 
            FROM `INFORMATION_SCHEMA`.`COLUMNS` 
            WHERE table_schema = DATABASE() 
            AND `TABLE_NAME`='" . $this->getTable('sales_order') . "'
        ");
        return $stmt->fetchAll();
    }

    /**
     * @param $data
     * @param $storeId
     */
    public function insertIntoMappingTable($data, $storeId)
    {
        foreach ($data as $key => $value) {
            $select = $this->getConnection()
                ->select()
                ->from($this->getMainTable())
                ->where('magento_column_name = ?', $value['COLUMN_NAME'])
                ->where('store_id = ?', $storeId);

            $result = $this->getConnection()->fetchAll($select);

            if (empty($result)) {
                $this->getConnection()->insert($this->getMainTable(), [
                    'magento_column_name' => $value['COLUMN_NAME'],
                    'store_id' => $storeId
                ]);
            }
        }
    }

    /**
     * @param $data
     * @param $storeId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function insertIntoMappingTableStaticData($data, $storeId)
    {
        foreach ($data as $key => $value) {
            $select = $this->getConnection()
                ->select()
                ->from($this->getMainTable())
                ->where('magento_column_name = ?', $value)
                ->where('store_id = ?', $storeId);

            $result = $this->getConnection()->fetchRow($select);

            $data = [];
            if (!empty($result)) {
                $data['id'] = $result['id'];
            }
            $data['magento_column_name'] = $value;
            $data['emarsys_order_field'] = $key;
            $data['store_id'] = $storeId;

            $this->getConnection()->insertOnDuplicate($this->getMainTable(), $data ,['emarsys_order_field']);
        }
    }

    /**
     * @param $data
     * @param $storeId
     * @throws \Zend_Db_Statement_Exception
     */
    public function insertIntoMappingTableCustomValue($data, $storeId)
    {
        foreach ($data as $key => $value) {
            $key = $this->getConnection()->quote($key);
            $value = $this->getConnection()->quote($value);
            $stmt = $this->getConnection()->query("SELECT magento_column_name FROM " .
                $this->getTable('emarsys_order_field_mapping') . " WHERE magento_column_name = " . $key . " ");
            $result = $stmt->fetch();
            if ($result == 0) {
                //insert the attribute
                //for the first time enter the empty records for the emarsys id
                $stmt = $this->getConnection()->query("INSERT INTO  " . $this->getTable('emarsys_order_field_mapping') .
                    " (magento_column_name,emarsys_order_field,store_id) VALUES (" . $key . "," . $value . ",'" . $storeId . "')  ");
            } else {
                //else update the attribute value
                $stmt = $this->getConnection()->query("UPDATE " . $this->getTable('emarsys_order_field_mapping') .
                    " SET emarsys_order_field = " . $value . " WHERE magento_column_name = " . $key . "");
            }
        }
    }

    /**
     * @param $storeId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function orderMappingExists($storeId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getMainTable())
            ->where('store_id = ?', $storeId);

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @return array
     */
    public function getEmarsysOrderFields($storeId)
    {

        if (!isset($this->emarsysOrderFields[$storeId])) {
            $heading = $this->emarsysDataHelper->getSalesOrderCsvDefaultHeader($storeId);
            $select = $this->getConnection()
                ->select()
                ->from($this->getMainTable())
                ->where('emarsys_order_field NOT IN (?)', $heading)
                ->where('emarsys_order_field != ?', '')
                ->where('store_id = ?', $storeId);

            $this->emarsysOrderFields[$storeId] = $this->getConnection()->fetchAll($select);
        }

        return $this->emarsysOrderFields[$storeId];
    }

    /**
     * @param $emarsysField
     * @param $orderId
     * @return mixed|void
     */
    public function getOrderColValue($emarsysField, $orderId, $storeId = 0)
    {
        $emarsysField = $this->getConnection()->quote($emarsysField);
        $heading = $this->emarsysDataHelper->getSalesOrderCsvDefaultHeader($storeId);
        if (in_array($emarsysField, $heading)) {
            return;
        }

        try {
            $stmt = $this->getConnection()->query("SELECT magento_column_name FROM " . $this->getTable('emarsys_order_field_mapping') . " WHERE emarsys_order_field = " . $emarsysField);
            $result = $stmt->fetch();
            if ($result['magento_column_name'] == 'created_at') { 
                $stmt = $this->getConnection()->query("SELECT " . $result['magento_column_name']. " as created_at FROM " . $this->getTable('sales_order') . " WHERE entity_id = '" . $orderId. "'  ");
            } elseif ($result['magento_column_name'] == 'updated_at') {
                $stmt = $this->getConnection()->query("SELECT " . $result['magento_column_name']. " as updated_at FROM " . $this->getTable('sales_order') . " WHERE entity_id = '" . $orderId. "'  ");
            } elseif ($result['magento_column_name'] == 'customer_dob') {
                $stmt = $this->getConnection()->query("SELECT DATE_FORMAT(" . $result['magento_column_name']. ",'%Y-%m-%d') as magento_column_value FROM " . $this->getTable('sales_order') . " WHERE entity_id = '" . $orderId. "'  ");
            } elseif ($result['magento_column_name'] == 'email') {
                $stmt = $this->getConnection()->query("SELECT customer_email as magento_column_value FROM " . $this->getTable('sales_order') . " WHERE entity_id = '" . $orderId. "'  ");
            } elseif ($result['magento_column_name'] == 'customer') {
                $stmt = $this->getConnection()->query("SELECT customer_id as magento_column_value FROM " . $this->getTable('sales_order') . " WHERE entity_id = '" . $orderId. "'  ");
            } else {
                $stmt = $this->getConnection()->query("SELECT " . $result['magento_column_name']. " as magento_column_value FROM " . $this->getTable('sales_order') . " WHERE entity_id = '" . $orderId. "'  ");
            }
        } catch (\Exception $e) {
            $storeId = $this->_storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'getOrderColValue()');
            return;
        }

        return $stmt->fetch();
    }

    /**
     * @return array
     */
    public function getStores()
    {
        $stmt = $this->getConnection()->query("SELECT * FROM " . $this->getTable('store') . " ");
        $result = $stmt->fetchAll();
        return $result;
    }
}

