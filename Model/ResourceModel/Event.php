<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\ResourceModel;

use Magento\{
    Eav\Model\Entity\Attribute,
    Eav\Model\Entity\Type,
    Framework\Model\ResourceModel\Db\Context,
    Store\Api\StoreRepositoryInterface
};

/**
 * Class Event
 * @package Emarsys\Emarsys\Model\ResourceModel
 */
class Event extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
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
        $connectionName = null
    ) {

        $this->entityType = $entityType;
        $this->attribute = $attribute;
        $this->storeRepository = $storeRepository;
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

    /**
     * @param $storeId
     * @return array
     */
    public function getEmarsysEvents($storeId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_events'))
            ->where("store_id = ?", $storeId);

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getMagentoEventTemplateCount($storeId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_events'), 'count(*)')
            ->where("store_id = ?", $storeId);

        return $this->getConnection()->fetchOne($select);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function checkEventMappingCount($storeId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_event_mapping'), 'count(*)')
            ->where("store_id = ?", $storeId);

        return $this->getConnection()->fetchOne($select);
    }

    /**
     * @param array $events
     * @param $storeId
     * @return bool
     */
    public function updateEventSchema($events = [], $storeId)
    {
        $this->getConnection()->delete(
            $this->getTable('emarsys_events'),
            $this->getConnection()->quoteInto("store_id = ?", $storeId)
        );
        if (isset($events['data'])) {
            foreach ($events['data'] as $field) {
                $this->getConnection()->insert($this->getTable("emarsys_events"), [
                    'event_id' => @$field['id'],
                    'emarsys_event' => @$field['name'],
                    'store_id' => $storeId
                ]);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $value
     * @return string
     */
    public function getEventMapping($value)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_event_mapping'))
            ->where("magento_event_template = ?", $value['magento_event_template'])
            ->where("store_id = ?", $value['store_id']);

        return $this->getConnection()->fetchOne($select);;
    }

    /**
     * @param $value
     * @return int
     */
    public function insertEventMapping($value)
    {
        return $this->getConnection()->insert($this->getTable("emarsys_event_mapping"), [
            'magento_event_template' => $value['magento_event_template'],
            'emarsys_events' => $value['emarsys_events'],
            'store_id' => $value['store_id']
        ]);
    }

    /**
     * @param $value
     * @return int
     */
    public function updateEventMapping($value)
    {
        return $this->getConnection()->update(
            $this->getTable('emarsys_event_mapping'),
            ['emarsys_events' => $value['emarsys_events']],
            ['store_id = ?' => $value['store_id'], 'magento_event_template = ?' => $value['magento_event_template']]
        );
    }

    /**
     * @param $magentoEventTemplate
     * @param $eventId
     * @param $storeId
     * @return string
     */
    public function checkSelectedField($magentoEventTemplate, $eventId, $storeId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_event_mapping'), 'count(*)')
            ->where("magento_event_template = ?", $magentoEventTemplate)
            ->where("emarsys_events = ?", $eventId)
            ->where("store_id = ?", $storeId);

        return $this->getConnection()->fetchOne($select);
    }

    /**
     * @param $storeId
     * @return int
     */
    public function deleteMappingTable($storeId)
    {
        return $this->getConnection()->delete(
            $this->getTable('emarsys_event_mapping'),
            $this->getConnection()->quoteInto("store_id = ?", $storeId)
        );
    }

    /**
     * @param $magentoEventTemplate
     * @param $storeId
     * @return array
     */
    public function getEmailAllVariables($magentoEventTemplate, $storeId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_variable_mapping'), 'count(*)')
            ->where("magento_event_template = ?", $magentoEventTemplate)
            ->where("store_id = ?", $storeId);

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @param $storeId
     * @return array
     */
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

    /**
     * @param $eventId
     * @param $storeId
     * @return int
     */
    public function deleteEvent($eventId, $storeId)
    {
        return $this->getConnection()->delete(
            $this->getTable('emarsys_events'),
            $this->getConnection()->quoteInto("store_id = ? AND ", $storeId)
            .  $this->getConnection()->quoteInto("event_id = ? AND ", $eventId)
        );
    }
}
