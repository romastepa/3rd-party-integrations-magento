<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\ResourceModel;

/**
 * Class Event
 * @package Emarsys\Emarsys\Model\ResourceModel
 */
class Event extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     */
    protected $storeRepository;
    /**
     * @var \Magento\Eav\Model\Entity\Attribute
     */
    protected $attribute;
    /**
     * @var \Magento\Eav\Model\Entity\Type
     */
    protected $entityType;

    /**
     * 
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Eav\Model\Entity\Type $entityType
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Eav\Model\Entity\Type $entityType,
        \Magento\Eav\Model\Entity\Attribute $attribute,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        $connectionName = null
    ) {
    
        $this->entityType = $entityType;
        $this->attribute = $attribute;
        $this->storeRepository = $storeRepository;
        $this->messageManager = $messageManager;
        parent::__construct($context, $connectionName);
    }

    /**
     * Define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_event_templates', 'id');
    }

    public function getEmarsysEvents($storeId)
    {
        $customerAttributes = $this->getConnection()->fetchAll("SELECT * FROM " . $this->getTable('emarsys_events') . " WHERE store_id =" . $storeId);
        return $customerAttributes;
    }

    public function getMagentoEventTemplateCount($storeId)
    {
        $customerAttributes = $this->getConnection()->fetchOne("SELECT count(*) FROM " . $this->getTable('emarsys_events') . " WHERE store_id =" . $storeId);
        return $customerAttributes;
    }

    public function checkEventMappingCount($storeId)
    {
        $customerAttributes = $this->getConnection()->fetchOne("SELECT count(*) FROM " . $this->getTable('emarsys_event_mapping') . " WHERE store_id =" . $storeId);
        return $customerAttributes;
    }

    /**
     * @param array $data
     */
    public function updateEventSchema($events = [], $storeId)
    {
        $this->getConnection()->query("DELETE FROM " . $this->getTable('emarsys_events') . " WHERE store_id = '" . $storeId . "' ");
        if (isset($events['data'])) {
            foreach ($events['data'] as $field) {
                $field['name'] = $this->getConnection()->quote($field['name']);
                $this->getConnection()->query("INSERT INTO " . $this->getTable("emarsys_events") . "(`id`,`event_id`,`emarsys_event`,`store_id`) VALUES('','" . $field['id'] . "'," . $field['name'] . ",'$storeId')");
            }
            return true;
        } else {
            return false;
        }
    }

    public function getEventMapping($value)
    {
        $query = "SELECT * FROM " . $this->getTable('emarsys_event_mapping') . " WHERE magento_event_template = '" . $value['magento_event_template'] . "' and store_id = " . $value['store_id'];
        $result = $this->getConnection()->fetchOne($query);
        return $result;
    }

    public function insertEventMapping($value)
    {
        $query = "INSERT INTO " . $this->getTable('emarsys_event_mapping') . "(id,magento_event_template,emarsys_events,store_id) VALUES('','" . $value['magento_event_template'] . "','" . $value['emarsys_events'] . "','" . $value['store_id'] . "') ";
        $result = $this->getConnection()->query($query);
        return $result;
    }

    public function updateEventMapping($value)
    {
        $query = "UPDATE " . $this->getTable('emarsys_event_mapping') . " SET emarsys_events='" . $value['emarsys_events'] . "' WHERE magento_event_template='" . $value['magento_event_template'] . "' AND store_id=" . $value['store_id'];
        $result = $this->getConnection()->query($query);
        return $result;
    }

    public function checkSelectedField($attributeCode, $eventId, $storeId)
    {
        $query = "SELECT count(*) FROM " . $this->getTable('emarsys_event_mapping') . " WHERE magento_event_template = '" . $attributeCode . "' AND emarsys_events = '" . $eventId . "' AND store_id = " . $storeId;
        $result = $this->getConnection()->fetchOne($query);
        return $result;
    }

    public function deleteMappingTable($storeId)
    {
        $query = "DELETE FROM " . $this->getTable('emarsys_event_mapping') . " WHERE store_id =" . $storeId;
        $result = $this->getConnection()->query($query);
    }

    public function getEmailAllVariables($attributeCode, $storeId)
    {
        $attributeCode = $this->getConnection()->quote($attributeCode);
        $query = "SELECT * FROM " . $this->getTable('emarsys_variable_mapping') . " WHERE magento_event_template = " . $attributeCode . " AND store_id = " . $storeId;
        $result = $this->getConnection()->fetchAll($query);
        return $result;
    }

    public function getRecommendedFields($storeId)
    {
        //recommended events
        $mappings = [
            '0' => [
                'emarsys_events' => '100000436',
                'magento_event_template' => 'order_new',
                'store_id' => $storeId
            ],
            '1' => [
                'emarsys_events' => '100000437',
                'magento_event_template' => 'order_new_guest',
                'store_id' => $storeId
            ],
            '2' => [
                'emarsys_events' => '100000438',
                'magento_event_template' => 'shipment_new',
                'store_id' => $storeId
            ]
        ];
        return $mappings;
    }

    public function deleteEvent($eventId, $storeId)
    {
        $query = "DELETE FROM " . $this->getTable('emarsys_events') . " WHERE event_id = '" . $eventId . "' AND store_id = " . $storeId;
        try {
            $result = $this->getConnection()->query($query);
            return $result;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
    }
}
