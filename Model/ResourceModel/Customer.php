<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\ResourceModel;

use Magento\{
    Framework\App\ObjectManager,
    Framework\Model\ResourceModel\Db\AbstractDb,
    Framework\Model\ResourceModel\Db\Context,
    Eav\Model\Entity\Type,
    Eav\Model\Entity\Attribute,
    Framework\Stdlib\DateTime\DateTime,
    Framework\Stdlib\DateTime\TimezoneInterface,
    Customer\Model\CustomerFactory,
    Store\Model\StoreManagerInterface,
    Framework\App\Config\ScopeConfigInterface
};
use Emarsys\Emarsys\Model\Logs as EmarsysModelLog;

/**
 * Class Customer
 * @package Emarsys\Emarsys\Model\ResourceModel
 */
class Customer extends AbstractDb
{
    /**
     * @var \Magento\Eav\Model\Entity\Type
     */
    protected $entityType;

    /**
     * @var TimezoneInterface
     */
    protected $_timezoneInterface;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute
     */
    protected $attribute;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customerModel;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var array
     */
    protected $notVisibleFields = [0, 27, 28, 29, 30, 33, 34, 36, 47, 48];

    /**
     * Date
     * @var DateTime
     */
    protected $dateTime;

    /**
     * Customer constructor.
     * @param Context $context
     * @param Type $entityType
     * @param Attribute $attribute
     * @param TimezoneInterface $timezoneInterface
     * @param CustomerFactory $customerModel
     * @param EmarsysModelLog $emarsysLogs
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param DateTime|null $dateTime
     * @param null $connectionName
     */
    public function __construct(
        Context $context,
        Type $entityType,
        Attribute $attribute,
        TimezoneInterface $timezoneInterface,
        CustomerFactory $customerModel,
        EmarsysModelLog $emarsysLogs,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfigInterface,
        DateTime $dateTime = null,
        $connectionName = null
    ) {
        $this->entityType = $entityType;
        $this->_timezoneInterface = $timezoneInterface;
        $this->attribute = $attribute;
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->emarsysLogs = $emarsysLogs;
        $this->customerModel = $customerModel;
        $this->storeManager = $storeManager;
        $this->dateTime = $dateTime ?: ObjectManager::getInstance()->get(DateTime::class);
        parent::__construct($context, $connectionName);
    }

    /**
     * Define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('emarsys_customer_field_mapping', 'id');
    }

    /**
     * Checking count of the mapping table
     * @param $storeId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkCustomerMapping($storeId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getMainTable(), 'count(*)')
            ->where('store_id = ?', $storeId);

        return $this->getConnection()->fetchOne($select);
    }

    /**
     * Truncate the mapping table
     * @param $storeId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function truncateMappingTable($storeId)
    {
        $this->getConnection()->delete($this->getMainTable(), $this->getConnection()->quoteInto('store_id = ?', $storeId));
    }

    /**
     * @param $custMageId
     * @param $magentoAttributeId
     * @param $storeId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteMapping($custMageId, $magentoAttributeId, $storeId)
    {
        $this->getConnection()->delete($this->getMainTable(),
            $this->getConnection()->quoteInto('store_id = ?', $storeId)
            . $this->getConnection()->quoteInto(' AND magento_custom_attribute_id = ?', $custMageId)
            . $this->getConnection()->quoteInto(' AND magento_attribute_id = ?', $magentoAttributeId)
        );
    }

    /**
     * @param array $contactFields
     * @param $storeId
     * @return bool
     */
    public function updateCustomerSchema($contactFields = [], $storeId)
    {
        $this->getConnection()->delete($this->getTable('emarsys_contact_field'), $this->getConnection()->quoteInto('store_id = ?', $storeId));
        if (isset($contactFields['data'])) {
            foreach ($contactFields['data'] as $field) {
                $this->getConnection()->insert($this->getTable('emarsys_contact_field'), [
                    "emarsys_field_id" => $field['id'],
                    "name" => $field['name'],
                    "type" => $field['application_type'],
                    "string_id" => $field['string_id'],
                    "language_code" => 'en',
                    "store_id" => $storeId,
                ]);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $storeId
     * @return array
     */
    public function getEmarsysContactFields($storeId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_contact_field'))
            ->where('store_id = ?', $storeId)
            ->where('emarsys_field_id not in (?)', $this->notVisibleFields);

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @param $attributeCode
     * @param $storeId
     * @return string
     */
    public function getCustomerAttributeId($attributeCode, $storeId)
    {
        if (strpos($attributeCode, 'default_billing_') !== false
            || strpos($attributeCode, 'default_shipping_') !== false
        ) {
            $entityTypeId = 2;
        } else {
            $entityTypeId = 1;
        }

        $select = $this->getConnection()
            ->select()
            ->from(['main_table' => $this->getTable('eav_attribute')], 'main_table.attribute_id')
            ->join(
                ['emca' => $this->getTable('emarsys_magento_customer_attributes')],
                $this->getConnection()->quoteInto('emca.attribute_code = main_table.attribute_code AND emca.entity_type_id = ?',  $entityTypeId)
                . $this->getConnection()->quoteInto(' AND emca.attribute_code_custom = ?', $attributeCode)
                . $this->getConnection()->quoteInto(' AND emca.store_id = ?', (int)$storeId),
                []
            )
            ->where('main_table.entity_type_id = ?', $entityTypeId);

        $attributeId = $this->getConnection()->fetchOne($select);

        if ($attributeId) {
            return $attributeId;
        } else {
            return 0;
        }
    }

    /**
     * @param $magentoCustomAttributeId
     * @param $emFieldId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkAttributeUsed($magentoCustomAttributeId, $storeId)
    {
        $select = $this->getConnection()
            ->select()
            ->from(['ecfm' => $this->getMainTable()], ['emarsys_contact_field'])
            ->where('ecfm.store_id = ?', (int)$storeId)
            ->where('ecfm.magento_custom_attribute_id = ?', $magentoCustomAttributeId);

        return $this->getConnection()->fetchRow($select);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getEmarsysAttrCount($storeId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_contact_field'), 'count(*)')
            ->where('store_id = ?', $storeId);

        return $this->getConnection()->fetchOne($select);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getRecommendedCustomerAttribute($storeId)
    {
        $emarsysCodes = [
            'first_name' => 'firstname',
            'middle_name' => 'middlename',
            'last_name' => 'lastname',
            'email' => 'email',
            'gender' => 'gender',
            'birth_date' => 'dob'
        ];

        foreach ($emarsysCodes as $emarsysCode => $magentoCode) {
            $select = $this->getConnection()
                ->select()
                ->from($this->getTable('eav_attribute'), 'attribute_id')
                ->where('entity_type_id = ?', 1)
                ->where('attribute_code = ?', $magentoCode);
            $result['magento'][$emarsysCode] = $this->getConnection()->fetchOne($select);

            $select = $this->getConnection()
                ->select()
                ->from($this->getTable('emarsys_contact_field'), 'emarsys_field_id')
                ->where('string_id = ?', $emarsysCode)
                ->where('store_id = ?', $storeId);
            $result['emarsys'][$emarsysCode] = $this->getConnection()->fetchOne($select);
        }

        return $result;
    }

    /**
     * @param $storeId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomMappedCustomerAttribute($storeId)
    {
        $emarsysCodes = ['first_name', 'middle_name', 'last_name', 'email', 'gender', 'birth_date'];

        $subSelect = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_contact_field'), 'emarsys_field_id')
            ->where('string_id in (?)', $emarsysCodes)
            ->where('store_id = ?', $storeId);

        $select = $this->getConnection()
            ->select()
            ->from($this->getMainTable())
            ->where('store_id = ?', $storeId)
            ->where('emarsys_contact_field IS NOT NULL')
            ->where('emarsys_contact_field NOT IN (?)', $subSelect);

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @param int $storeId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMappedCustomerAttribute($storeId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getMainTable())
            ->where('store_id = ?', $storeId);

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @param $storeId
     * @param $fieldId
     * @return string
     */
    public function getEmarsysFieldName($storeId, $fieldId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_contact_field'), 'name')
            ->where('emarsys_field_id = ?', $fieldId)
            ->where('store_id = ?', $storeId);

        return $this->getConnection()->fetchOne($select);
    }

    /**
     * @param string $path
     * @param string|null $scope
     * @param int|null $scopeId
     * @return array
     */
    public function getDataFromCoreConfig($path, $scope = NULL, $scopeId = NULL)
    {
        if ($scope && $scopeId) {
            return $this->scopeConfigInterface->getValue($path, $scope, $scopeId);
        } else {
            return $this->scopeConfigInterface->getValue($path);
        }
    }

    /**
     * @param $storeId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function fetchMappedFields($storeId)
    {
        $select = $this->getConnection()->select()
            ->from(
                ['ecfm' => $this->getMainTable()],
                ['ecfm.magento_attribute_id', 'ecfm.emarsys_contact_field']
            )
            ->join(
                ['ecf' => $this->getTable('emarsys_contact_field')],
                'ecf.emarsys_field_id = ecfm.emarsys_contact_field and ecf.store_id = ecfm.store_id',
                ['ecf.name', 'ecf.string_id', 'ecf.type']
            )
            ->join(
                ['eav' => $this->getTable('eav_attribute')],
                'ecfm.magento_attribute_id = eav.attribute_id',
                ['eav.attribute_code', 'eav.frontend_label', 'eav.frontend_input', 'eav.source_model']
            )
            ->where('ecfm.store_id = ?', $storeId);

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @param $fieldName
     * @param $storeId
     * @return string
     */
    public function getKeyId($fieldName, $storeId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_contact_field'), 'emarsys_field_id')
            ->where('name = ?', $fieldName)
            ->where('store_id = ?', $storeId);

        return $this->getConnection()->fetchOne($select);
    }

    /**
     * get all customer based on website and date
     *
     * @param type $data
     * @param null $storeId
     * @return array
     */
    public function getCustomerCollection($data, $storeId = null)
    {
        $customers = $this->customerModel->create()
            ->getCollection()
            ->addFieldToFilter('website_id', ['eq' => $data['website']]);

        if (isset($data['fromDate']) &&  !empty($data['fromDate'])) {
            date_default_timezone_set($this->_timezoneInterface->getConfigTimezone());
            $fromDateUTC = gmdate("Y-m-d H:i:s", strtotime($data['fromDate']));
            date_default_timezone_set($this->_timezoneInterface->getDefaultTimezone());

            $customers->addFieldToFilter('created_at', ['from' => $fromDateUTC]);
        }

        if (isset($data['toDate']) && !empty($data['toDate'])) {
            date_default_timezone_set($this->_timezoneInterface->getConfigTimezone());
            $toDateUTC = gmdate("Y-m-d H:i:s", strtotime($data['toDate']));
            date_default_timezone_set($this->_timezoneInterface->getDefaultTimezone());

            $customers->addFieldToFilter('created_at', ['to' => $toDateUTC]);
        }

        $customers->addFieldToFilter('website_id', ['eq' => $data['website']]);

        if ($storeId) {
            $customers->addFieldToFilter('store_id', ['eq' => $data['storeId']]);
        }

        return $customers;
    }

    /**
     * Get collection of subscribed customer
     * @param type $data
     * @param $storeIds
     * @param $getAll
     * @return array
     */
    public function getSubscribedCustomerCollection($data, $storeIds, $getAll)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('newsletter_subscriber'), ['subscriber_id', 'subscriber_status', 'subscriber_email', 'store_id'])
            ->where('store_id in (?)', $storeIds);

        if (isset($data['attributevalue']) && $data['attributevalue'] != '') {
            $select->where('subscriber_status = ?', $data['attributevalue']);
        } else {
            if (!$getAll) {
                $select->where('subscriber_status = ?', 1);
            }
        }

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @param $data
     * @return string
     */
    public function getSubscribeIdFromEmail($data)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('newsletter_subscriber'), 'subscriber_id')
            ->where('subscriber_email = ?', @$data['email'])
            ->where('store_id = ?', @$data['store_id']);

        return $this->getConnection()->fetchOne($select);
    }

    /**
     * @param int $status
     * @param array $subscriberIds
     *
     * @return void
     */
    public function updateStatusOfSubscribers($status, $subscriberIds)
    {
        $this->getConnection()->update(
            $this->getTable('newsletter_subscriber'),
            [
                'subscriber_status' => $status,
                'change_status_at' => $this->dateTime->gmtDate()
            ],
            $this->getConnection()->quoteInto('subscriber_id in (?)', $subscriberIds)
        );
    }

    /**
     * @return array
     */
    public function getLogsData()
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_log_details'), ['created_at', 'description', 'message_type'])
            ->order('id DESC');

        return $this->getConnection()->fetchAll($select);
    }

    /**
     *
     * @param type $email
     * @param type $websiteId
     * @return string
     */
    public function checkCustomerExistsInMagento($email, $websiteId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('customer_entity'), 'entity_id')
            ->where('email = ?', $email)
            ->where('website_id = ?', $websiteId);

        return $this->getConnection()->fetchOne($select);
    }

    /**
     *
     * @param type $storeId
     * @return array
     */
    public function customerMappingExists($storeId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_magento_customer_attributes'))
            ->where('store_id = ?', $storeId);

        return $this->getConnection()->fetchRow($select);
    }

    /**
     *
     * @param type $attributesData
     * @param type $storeId
     */
    public function insertCustomerMageAtts($attributesData, $storeId)
    {
        foreach ($attributesData as $attribute) {
            try {
                if ($attribute['frontend_label'] != '') {
                    if ($attribute['entity_type_id'] == 1) {
                        $select = $this->getConnection()
                            ->select()
                            ->from($this->getTable('emarsys_magento_customer_attributes'), 'id')
                            ->where('attribute_code = ?', $attribute['attribute_code'])
                            ->where('entity_type_id = ?', $attribute['entity_type_id'])
                            ->where('store_id = ?', $storeId);

                        if (empty($this->getConnection()->fetchOne($select))) {
                            $this->getConnection()->insert($this->getTable('emarsys_magento_customer_attributes'), [
                                'attribute_code' => $attribute['attribute_code'],
                                'attribute_code_custom' => $attribute['attribute_code'],
                                'frontend_label' => $attribute['frontend_label'],
                                'entity_type_id' => $attribute['entity_type_id'],
                                'store_id' => $storeId
                            ]);
                        }
                    } elseif ($attribute['entity_type_id'] == 2) {
                        // if the attributes are of customer address
                        $select = $this->getConnection()
                            ->select()
                            ->from($this->getTable('emarsys_magento_customer_attributes'), 'id')
                            ->where('attribute_code_custom = ?', 'default_billing_' . $attribute['attribute_code'])
                            ->where('entity_type_id = ?', $attribute['entity_type_id'])
                            ->where('store_id = ?', $storeId);

                        if (empty($this->getConnection()->fetchOne($select))) {
                            $this->getConnection()->insert($this->getTable('emarsys_magento_customer_attributes'), [
                                'attribute_code' => $attribute['attribute_code'],
                                'attribute_code_custom' => 'default_billing_' . $attribute['attribute_code'],
                                'frontend_label' => 'Default Billing ' . $attribute['frontend_label'] . '(' . $attribute['attribute_code'] . ')',
                                'entity_type_id' => $attribute['entity_type_id'],
                                'store_id' => $storeId
                            ]);
                        }

                        $select = $this->getConnection()
                            ->select()
                            ->from($this->getTable('emarsys_magento_customer_attributes'), 'id')
                            ->where('attribute_code_custom = ?', 'default_shipping_' . $attribute['attribute_code'])
                            ->where('entity_type_id = ?', $attribute['entity_type_id'])
                            ->where('store_id = ?', $storeId);

                        if (empty($this->getConnection()->fetchOne($select))) {
                            $this->getConnection()->insert($this->getTable('emarsys_magento_customer_attributes'), [
                                'attribute_code' => $attribute['attribute_code'],
                                'attribute_code_custom' => 'default_shipping_' . $attribute['attribute_code'],
                                'frontend_label' => 'Default Shipping ' . $attribute['frontend_label'] . '(' . $attribute['attribute_code'] . ')',
                                'entity_type_id' => $attribute['entity_type_id'],
                                'store_id' => $storeId
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->emarsysLogs->addErrorLog(
                    'insertCustomerMageAtts',
                    $e->getMessage(),
                    $storeId,
                    'insertCustomerMageAtts(ResourceModel)'
                );
            }
        }
    }

    /**
     *
     * @param type $attributeCode
     * @param type $storeId
     * @return array
     */
    public function getCustAttIdByCode($attributeCode, $storeId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_magento_customer_attributes'), 'id')
            ->where('attribute_code_custom = ?', $attributeCode)
            ->where('store_id = ?', $storeId);

        return $this->getConnection()->fetchOne($select);
    }

    /**
     *
     * @param int $attdata
     * @param int $storeId
     * @return array
     */
    public function getEmarsysFieldNameContact($attdata, $storeId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_contact_field'))
            ->where('emarsys_field_id = ?', @$attdata['emarsys_contact_field'])
            ->where('store_id = ?', $storeId);

        return $this->getConnection()->fetchRow($select);
    }

    /**
     *
     * @param int $id
     * @param int $storeId
     * @return array
     */
    public function getMagentoAttributeCode($id, $storeId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_magento_customer_attributes'), ['attribute_code', 'attribute_code_custom', 'entity_type_id'])
            ->where('id = ?', $id)
            ->where('store_id = ?', $storeId);

        return $this->getConnection()->fetchRow($select);
    }
}
