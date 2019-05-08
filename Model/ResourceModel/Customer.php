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
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\CustomerFactory;
use Emarsys\Emarsys\Model\Logs as EmarsysModelLog;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

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
     * Customer constructor.
     * @param Context $context
     * @param Type $entityType
     * @param Attribute $attribute
     * @param TimezoneInterface $timezoneInterface
     * @param CustomerFactory $customerModel
     * @param EmarsysModelLog $emarsysLogs
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfigInterface
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
        $connectionName = null
    ) {
        $this->entityType = $entityType;
        $this->_timezoneInterface = $timezoneInterface;
        $this->attribute = $attribute;
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->emarsysLogs = $emarsysLogs;
        $this->customerModel = $customerModel;
        $this->storeManager = $storeManager;
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
     * @param type $storeId
     * @return array
     */
    public function checkCustomerMapping($storeId)
    {
        $customerAttributes = $this->getConnection()->fetchOne("SELECT count(*) FROM " . $this->getTable('emarsys_customer_field_mapping') . " WHERE store_id =" . $storeId);
        return $customerAttributes;
    }

    /**
     * truncate the mapping table
     * @param $storeId
     */
    public function truncateMappingTable($storeId)
    {
        $this->getConnection()->query("DELETE FROM  " . $this->getTable("emarsys_customer_field_mapping") . " WHERE store_id = $storeId");
    }

    /**
     * @param array $contactFields
     * @param $storeId
     * @return bool
     */
    public function updateCustomerSchema($contactFields = [], $storeId)
    {
        $this->getConnection()->query("DELETE FROM " . $this->getTable("emarsys_contact_field" . " WHERE store_id = '" . $storeId . "' "));
        if (isset($contactFields['data'])) {
            foreach ($contactFields['data'] as $field) {
                $this->getConnection()->query("INSERT INTO " . $this->getTable("emarsys_contact_field") . "(`emarsys_field_id`,`name`,`type`,`string_id`,`language_code`,`store_id`) VALUES('" . $field['id'] . "','" . $field['name'] . "','" . $field['application_type'] . "','" . $field['string_id'] . "','en','$storeId')");
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
        $query = "SELECT * FROM " . $this->getTable('emarsys_contact_field') . " WHERE store_id=" . $storeId;
        try {
            $result = $this->getConnection()->fetchAll($query);
            return $result;
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'getEmarsysContactFields(ResourceModel)');
        }
    }

    /**
     * @param $attributeCode
     * @return string
     */
    public function getCustomerAttributeId($attributeCode)
    {
        if (strpos($attributeCode, 'BILLADD_') !== false) {
            $entityTypeId = 2;
            $attributeCode = str_replace('BILLADD_', '', $attributeCode);
        } else {
            $entityTypeId = 1;
        }
        $attributeCode = $this->getConnection()->quote($attributeCode);
        $attributeId = $this->getConnection()->fetchOne("SELECT attribute_id FROM " . $this->getTable('eav_attribute') . " where attribute_code = " . $attributeCode . " and entity_type_id = $entityTypeId ");
        if ($attributeId) {
            return $attributeId;
        } else {
            return '';
        }
    }

    /**
     * @param $magentoAttributeCode
     * @param $emarsysContactId
     * @param $storeId
     * @param $entityType
     * @return string
     */
    public function checkSelectedField($magentoAttributeCode, $emarsysContactId, $storeId, $entityType)
    {
        $magentoDeletedCustomers = $this->getConnection()->fetchOne("SELECT count(*) as assigned FROM " . $this->getTable('emarsys_customer_field_mapping') . " ecfm inner join " . $this->getTable('eav_attribute') . " eav on eav.attribute_id = ecfm.magento_attribute_id WHERE eav.attribute_code = '$magentoAttributeCode' and ecfm.emarsys_contact_field='$emarsysContactId' and eav.entity_type_id='$entityType' and ecfm.store_id='" . $storeId . "'");
        return $magentoDeletedCustomers;
    }

    /**
     * @param $customAttCode
     * @param $emFieldId
     * @param $storeId
     * @return mixed
     */
    public function checkAttributeUsed($customAttCode, $emFieldId, $storeId)
    {
        $customAttCode = $this->getConnection()->quote($customAttCode);
        $stmt = $this->getConnection()->query("SELECT count(*) as assigned FROM " . $this->getTable('emarsys_customer_field_mapping') ." WHERE magento_custom_attribute_id = (SELECT id FROM " . $this->getTable('emarsys_magento_customer_attributes') ." WHERE attribute_code_custom = " . $customAttCode." AND store_id = '" . $storeId."' ) AND store_id = '" . $storeId."' AND emarsys_contact_field = '" . $emFieldId."' " );
        return $stmt->fetch();
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getEmarsysAttrCount($storeId)
    {
        $emarsysFieldCount = $this->getConnection()->fetchOne("SELECT count(*) FROM " . $this->getTable('emarsys_contact_field') . " WHERE store_id=" . $storeId . "");
        return $emarsysFieldCount;
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
            $query = "SELECT attribute_id FROM " . $this->getTable("eav_attribute") . " WHERE entity_type_id = 1 AND attribute_code = '" . $magentoCode . "'";
            $attributeId = $this->getConnection()->fetchOne($query);
            $result['magento'][$emarsysCode] = $this->getConnection()->fetchOne($query);
            $query = "SELECT emarsys_field_id FROM " . $this->getTable("emarsys_contact_field") . " WHERE string_id = '" . $emarsysCode . "' and  store_id =" . $storeId;
            $result['emarsys'][$emarsysCode] = $this->getConnection()->fetchOne($query);
        }

        return $result;
    }

    /**
     * @param $storeId
     * @return array
     */
    public function getCustomMappedCustomerAttribute($storeId)
    {
        $emarsysCodes = ['first_name', 'middle_name', 'last_name', 'email', 'gender', 'birth_date'];
        $emarsysContactFieldId = [];

        foreach ($emarsysCodes as $emarsysCode) {
            $query = "SELECT emarsys_field_id FROM " . $this->getTable("emarsys_contact_field") . " WHERE string_id = '" . $emarsysCode . "' and  store_id =" . $storeId;
            $id = $this->getConnection()->fetchOne($query);
            if ($id) {
                array_push($emarsysContactFieldId, $id);
            }
        }
        try {
            $customerAttributes = $this->getConnection()->fetchAll("SELECT * FROM " . $this->getTable('emarsys_customer_field_mapping') . " WHERE store_id =" . $storeId .
                " and emarsys_contact_field IS NOT NULL" . " and emarsys_contact_field NOT IN ("  . implode(',', $emarsysContactFieldId) . ") ");

            return $customerAttributes;
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'Customer::getCustomMappedCustomerAttribute(ResourceModel)');
        }
    }

    /**
     *
     * @param type $storeId
     * @return array
     */
    public function getMappedCustomerAttribute($storeId)
    {
        try {
            $customerAttributes = $this->getConnection()->fetchAll("SELECT * FROM " . $this->getTable('emarsys_customer_field_mapping') . " WHERE store_id =" . $storeId);
            return $customerAttributes;
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'getMappedCustomerAttribute(ResourceModel)');
        }
    }

    /**
     * @param $storeId
     * @param $fieldId
     * @return string
     */
    public function getEmarsysFieldName($storeId, $fieldId)
    {
        try {
            $emarsysFieldName = $this->getConnection()->fetchOne("SELECT name FROM " . $this->getTable('emarsys_contact_field') . " WHERE emarsys_field_id = '" . $fieldId . "' AND store_id =" . $storeId);
            return $emarsysFieldName;
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'getEmarsysFieldName(ResourceModel)');
        }
    }

    /**
     * @param $attributeId
     * @return string
     */
    public function getMagentoAttributeName($attributeId)
    {
        try {
            $query = "SELECT eav.attribute_code FROM " . $this->getTable('eav_attribute') . " eav INNER JOIN " . $this->getTable('eav_entity_attribute') . " eavea ON eav.attribute_id = eavea.attribute_id AND eav.attribute_id = '" . $attributeId . "'";
            $emarsysFieldName = $this->getConnection()->fetchOne($query);
            return $emarsysFieldName;
        } catch (\Exception $e) {
            $storeId = $this->storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'getMagentoAttributeName(ResourceModel)');
        }
    }

    /**
     * @param type $path
     * @param type $scope
     * @param type $scopeId
     * @return array
     */
    public function getDataFromCoreConfig($path, $scope = NULL, $scopeId = NULL)
    {
        try {
            if ($scope && $scopeId) {
                return $this->scopeConfigInterface->getValue($path, $scope, $scopeId);
            } else {
                return  $this->scopeConfigInterface->getValue($path);
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $scopeId, 'getDataFromCoreConfig in Customer.php');
        }
    }

    /**
     * @param $templateIdentifier
     * @param int $scopeId
     * @return string
     */
    public function getConfigPath($templateIdentifier, $scopeId = 0)
    {
        $query = "SELECT path FROM " . $this->getTable("core_config_data") . " WHERE value= '" . $templateIdentifier . "' AND scope_id= '" . $scopeId . "'";
        try {
            return $this->getConnection()->fetchOne($query);
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $scopeId, 'getConfigPath(ResourceModel)');
        }
    }


    /**
     * @param $storeId
     * @return array
     */
    public function fetchMappedFields($storeId)
    {
        $select = $this->getConnection()->select()
            ->from(
                ['ecfm' => $this->getTable('emarsys_customer_field_mapping')],
                ['ecfm.magento_attribute_id', 'ecfm.emarsys_contact_field']
            )
            ->join(
                ['ecf' => $this->getTable('emarsys_contact_field')],
                'ecf.emarsys_field_id = ecfm.emarsys_contact_field and ecf.store_id = ecfm.store_id',
                ['ecf.name', 'ecf.string_id', 'ecf.type']
            )->join(
                ['eav' => $this->getTable('eav_attribute')],
                'ecfm.magento_attribute_id = eav.attribute_id',
                ['eav.attribute_code', 'eav.frontend_label', 'eav.frontend_input', 'eav.source_model']
            )
            ->where('ecfm.store_id = ?', $storeId);
        $mappedFields = $this->getConnection()->fetchAll($select);
        return $mappedFields;
    }

    /**
     * @param $fieldName
     * @param $storeId
     * @return array|string
     */
    public function getEmarsysFieldId($fieldName, $storeId)
    {
        $fieldName = $this->getConnection()->quote($fieldName);
        $count = [];
        try {
            $query = "SELECT emarsys_field_id FROM " . $this->getTable('emarsys_contact_field') . " WHERE name = " . $fieldName . " AND store_id = " . $storeId;
            $count = $this->getConnection()->fetchOne($query);
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'getEmarsysFieldId(ResourceModel)');
        }
        return $count;
    }

    /**
     * @return array
     */
    public function getAllCustomerAttributes()
    {
        $entitiesToMerge = ['customer_address', 'customer'];
        $entitiesData = [];
        // get the entities objects and data
        if (!empty($entitiesToMerge)) {
            foreach ($entitiesToMerge as $entityCode) {
                $entity = $this->entityType->loadByCode($entityCode);
                $entitiesData['id'][] = $entity->getId();
                $entitiesData['additional'][] = $entity->getAdditionalAttributeTable();
            }
        }

        $entitiesData['id'] = array_unique($entitiesData['id']);
        $entitiesData['additional'] = array_unique($entitiesData['additional']);
        $customCollection = $this->attribute->getCollection();
        $customCollection->addFieldToFilter('main_table.entity_type_id', ['in' => $entitiesData['id']]);
        $customCollection->addFieldToFilter('frontend_label', ['notnull' => true]);

        if (!empty($entitiesData['additional'])) {
            foreach ($entitiesData['additional'] as $idx => $additionalTable) {
                $customCollection->join(
                    ['additional_table_' . $idx => $additionalTable],
                    'additional_table_' . $idx . '.attribute_id = main_table.attribute_id'
                );
            }
        }
        $customCollection->setOrder('main_table.entity_type_id', 'asc');
        return $customCollection->getData();
    }

    /**
     * get all customer based on website and date
     * @param type $data
     * @return array
     */
    public function getCustomerCollection($data, $storeId = null)
    {
        if (isset($data['fromDate']) && isset($data['toDate']) && $data['fromDate'] != '' && $data['toDate'] != '') {
            date_default_timezone_set($this->_timezoneInterface->getConfigTimezone());
            $fromDateUTC = gmdate("Y-m-d H:i:s", strtotime($data['fromDate']));
            $toDateUTC = gmdate("Y-m-d H:i:s", strtotime($data['toDate']));
            date_default_timezone_set($this->_timezoneInterface->getDefaultTimezone());

            if ($storeId) {
                $customers = $this->customerModel->create()
                    ->getCollection()
                    ->addFieldToFilter('created_at', ['from' => $fromDateUTC, 'to' => $toDateUTC])
                    ->addFieldToFilter('website_id', ['eq' => $data['website']])
                    ->addFieldToFilter('store_id', ['eq' => $data['storeId']]);
            } else {
                $customers = $this->customerModel->create()
                    ->getCollection()
                    ->addFieldToFilter('created_at', ['from' => $fromDateUTC, 'to' => $toDateUTC])
                    ->addFieldToFilter('website_id', ['eq' => $data['website']]);
            }
        } else {
            if ($storeId) {
                $customers = $this->customerModel->create()
                    ->getCollection()
                    ->addFieldToFilter('website_id', ['eq' => $data['website']])
                    ->addFieldToFilter('store_id', ['eq' => $data['storeId']]);
            } else {
                $customers = $this->customerModel->create()
                    ->getCollection()
                    ->addFieldToFilter('website_id', ['eq' => $data['website']]);
            }
        }

        if ($customers) {
            return $customers->getData();
        }

        return false;
    }

    /**
     * @param $attributeId
     * @return array
     */
    public function getAttributeName($attributeId)
    {
        try {
            $query = "SELECT eav.entity_type_id,eav.attribute_code FROM " . $this->getTable('eav_attribute') . " eav INNER JOIN " . $this->getTable('eav_entity_attribute') . " eavea ON eav.attribute_id = eavea.attribute_id AND eav.attribute_id = '" . $attributeId . "'";
            $emarsysFieldName = $this->getConnection()->fetchAll($query);
            return $emarsysFieldName;
        } catch (\Exception $e) {
            $storeId = $this->storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'getAttributeName(ResourceModel)');
        }
    }

    /**
     * Get collection of subscribed customer
     * @param type $data
     * @return array
     */
    public function getSubscribedCustomerCollection($data, $storeIds, $getAll)
    {
        if (isset($data['attributevalue']) && $data['attributevalue'] != '') {
            $query = "SELECT subscriber_id,subscriber_status,subscriber_email,store_id FROM " . $this->getTable('newsletter_subscriber') . " WHERE subscriber_status = '" . $data['attributevalue'] . "' AND store_id IN ($storeIds) ";
        } else {
            if ($getAll) {
                $query = "SELECT subscriber_id,subscriber_status,subscriber_email,store_id FROM " . $this->getTable('newsletter_subscriber') . " WHERE store_id IN ($storeIds)" ;
            } else {
                $query = "SELECT subscriber_id,subscriber_status,subscriber_email,store_id FROM " . $this->getTable('newsletter_subscriber') . " WHERE store_id IN ($storeIds) AND subscriber_status = 1 " ;
            }
        }
        $result = $this->getConnection()->fetchAll($query);
        return $result;
    }

    /**
     * @return array
     */
    public function getAllStoresId()
    {
        $query = "SELECT * FROM " . $this->getTable('store') . " WHERE is_active = 1 and store_id != 0";
        $result = $this->getConnection()->fetchAll($query);
        return $result;
    }

    /**
     * get all customer Id based on website and date
     * @param type $data
     * @return array
     */
    public function getCustomerIdCollectionForCron($data)
    {
        if (isset($data['fromDate']) && isset($data['toDate']) && $data['fromDate'] != '' && $data['toDate'] != '') {
            if (isset($data['subscribeStatus'])) {
                $query = "SELECT ce.entity_id FROM " . $this->getTable('customer_entity') . " ce LEFT JOIN " . $this->getTable('newsletter_subscriber') . " ns ON ce.entity_id = ns.customer_id LEFT JOIN " . $this->getTable('customer_address_entity') . " cae ON ce.entity_id = cae.parent_id
                            WHERE ns.subscriber_status='" . $data['subscribeStatus'] . "' AND ce.website_id = " . $data['website'] . " AND ce.updated_at BETWEEN '" . $data['fromDate'] . "'  AND '" . $data['toDate'] . "'";
            } else {
                $query = "SELECT ce.entity_id FROM " . $this->getTable('customer_entity') . " ce LEFT JOIN " . $this->getTable('customer_address_entity') . " cae ON ce.entity_id = cae.parent_id WHERE ce.website_id = " . $data['website'] . " AND ce.updated_at BETWEEN '" . $data['fromDate'] . "' AND '" . $data['toDate'] . "'";
            }
        } else if (isset($data['fromDate']) && isset($data['toDate']) && $data['fromDate'] == '' && $data['toDate'] == '') {
            if (isset($data['subscribeStatus'])) {
                $query = "SELECT ce.entity_id FROM " . $this->getTable('customer_entity') . " ce LEFT JOIN " . $this->getTable('newsletter_subscriber') . " ns ON ce.entity_id = ns.customer_id LEFT JOIN " . $this->getTable('customer_address_entity') . " cae ON ce.entity_id = cae.parent_id WHERE ns.subscriber_status='" . $data['subscribeStatus'] . "' AND ce.website_id = " . $data['website'];
            } else {
                $query = "SELECT ce.entity_id FROM " . $this->getTable('customer_entity') . " ce LEFT JOIN " . $this->getTable('customer_address_entity') . " cae ON ce.entity_id = cae.parent_id WHERE website_id = " . $data['website'];
            }
        }
        $result = $this->getConnection()->fetchAll($query);
        return $result;
    }

    /**
     * @param $code
     * @return mixed
     */
    public function getAttributeIdByCode($code)
    {
        $code = $this->getConnection()->quote($code);
        $stmt = $this->getConnection()->query("SELECT attribute_id FROM " . $this->getTable('eav_attribute') . " WHERE attribute_code= " . $code);
        return $stmt->fetch();
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getAttributeCodeById($id)
    {
        $stmt = $this->getConnection()->query("SELECT attribute_code FROM " . $this->getTable('eav_attribute') . " WHERE attribute_id= '" . $id . "'");
        return $stmt->fetch();
    }

    /**
     * @param $customerId
     * @return \Magento\Customer\Model\Address
     */
    public function getCustPriBillAddress($customerId)
    {
        $customerAddressData = $this->customerModel->create()->load($customerId);
        return $customerAddressData->getPrimaryBillingAddress();
    }

    /**
     * @param $customerId
     * @return \Magento\Customer\Model\Address
     */
    public function getCustPriShipAddress($customerId)
    {
        $customerAddressData = $this->customerModel->create()->load($customerId);
        return $customerAddressData->getPrimaryShippingAddress();
    }

    /**
     * @param $data
     * @return string
     */
    public function getSubscribeIdFromEmail($data)
    {
        $storeId = $this->storeManager->getStore()->getId();
        $data['email'] = $this->getConnection()->quote($data['email']);
        $query = "SELECT subscriber_id FROM " . $this->getTable('newsletter_subscriber') . " WHERE subscriber_email = " . $data['email'] . " AND store_id = " . $storeId;
        try {
            $result = $this->getConnection()->fetchOne($query);
        } catch (\Exception $e) {

            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'getSubscribeIdFromEmail(ResourceModel)');
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getLogsData()
    {
        $query = "SELECT created_at,description,message_type FROM " . $this->getTable('emarsys_log_details') . "  ORDER BY id DESC";
        try {
            $result = $this->getConnection()->fetchAll($query);
        } catch (\Exception $e) {
            $storeId = $this->storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'getLogsData(ResourceModel)');
        }
        return $result;
    }

    /**
     *
     * @param type $attributeName
     * @param type $storeId
     * @return array
     */
    public function getKeyId($attributeName, $storeId)
    {
        $attributeName = $this->getConnection()->quote($attributeName);
        $query = "SELECT emarsys_field_id FROM " . $this->getTable('emarsys_contact_field') . " WHERE name = " . $attributeName . " AND store_id = " . $storeId;
        try {
            $result = $this->getConnection()->fetchOne($query);
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'getKeyId(ResourceModel)');
        }
        return $result;
    }

    /**
     *
     * @param type $email
     * @param type $websiteId
     * @return array
     */
    public function checkCustomerExistsInMagento($email, $websiteId)
    {
        $email = $this->getConnection()->quote($email);
        $result = '';
        $query = "SELECT entity_id FROM " . $this->getTable('customer_entity') . " WHERE email = " . $email . " AND website_id = " . $websiteId;
        try {
            $result = $this->getConnection()->fetchOne($query);
        } catch (\Exception $e) {
            $storeId = $this->storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'checkCustomerExistsInMagento(ResourceModel)');
        }
        return $result;
    }

    /**
     *
     * @param type $storeId
     * @return array
     */
    public function customerMappingExists($storeId)
    {
        $stmt = $this->getConnection()->query("SELECT * FROM " . $this->getTable('emarsys_magento_customer_attributes') . " WHERE store_id = '" . $storeId."' ");
         try {
             $result = $stmt->fetch();
        } catch (\Exception $e) {
             $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'customerMappingExists(ResourceModel)');
        }

        return $result;
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
                        // if the attributes are of customer
                        $existStmt = $this->getConnection()->query("SELECT id FROM " . $this->getTable('emarsys_magento_customer_attributes') . " WHERE attribute_code = '" . $attribute['attribute_code']."' AND  entity_type_id = '" . $attribute['entity_type_id']. "' AND store_id = '" . $storeId. "'");
                        if (empty($existStmt->fetch()))
                        $stmt = $this->getConnection()->query("INSERT INTO " . $this->getTable('emarsys_magento_customer_attributes') . " (`attribute_code`,`attribute_code_custom`,`frontend_label`,`entity_type_id`,`store_id`) VALUES ('" . $attribute['attribute_code']. "','" . $attribute['attribute_code']. "','" . $attribute['frontend_label']. "','" . $attribute['entity_type_id']. "','" . $storeId. "')   ");
                    } elseif ($attribute['entity_type_id'] == 2) {
                        // if the attributes are of customer address
                        $existStmt = $this->getConnection()->query("SELECT id FROM " . $this->getTable('emarsys_magento_customer_attributes') . " WHERE attribute_code_custom = '" .'default_billing_' . $attribute['attribute_code']. "' AND  entity_type_id = '" . $attribute['entity_type_id']. "' AND store_id = '" . $storeId. "'");
                        if (empty($existStmt->fetch()))
                        $stmt = $this->getConnection()->query("INSERT INTO " . $this->getTable('emarsys_magento_customer_attributes') . " (`attribute_code`,`attribute_code_custom`,`frontend_label`,`entity_type_id`,`store_id`) VALUES ('" . $attribute['attribute_code']. "','default_billing_" . $attribute['attribute_code']. "','Default Billing " . $attribute['frontend_label']. "','" . $attribute['entity_type_id']. "','" . $storeId. "')   ");
                        $existStmt = $this->getConnection()->query("SELECT id FROM " . $this->getTable('emarsys_magento_customer_attributes') . " WHERE attribute_code_custom = '" . 'default_shipping_' . $attribute['attribute_code']. "' AND  entity_type_id = '" . $attribute['entity_type_id']. "' AND store_id = '" . $storeId. "'");
                        if (empty($existStmt->fetch()))
                        $stmt = $this->getConnection()->query("INSERT INTO " . $this->getTable('emarsys_magento_customer_attributes') . " (`attribute_code`,`attribute_code_custom`,`frontend_label`,`entity_type_id`,`store_id`) VALUES ('" . $attribute['attribute_code']. "','default_shipping_" . $attribute['attribute_code']. "','Default Shipping " . $attribute['frontend_label']. "','" . $attribute['entity_type_id']. "','" . $storeId. "')   ");
                    }
                }
            } catch (\Exception $e) {
                $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'insertCustomerMageAtts(ResourceModel)');
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
        $attributeCode = $this->getConnection()->quote($attributeCode);
        $stmt = $this->getConnection()->query("SELECT id FROM " . $this->getTable('emarsys_magento_customer_attributes') . " WHERE attribute_code_custom = " . $attributeCode. " AND store_id = " . $storeId. "");
        $result =$stmt->fetch();        
        return $result;
    }
    
    /**
     * 
     * @param type $attdata
     * @param type $storeId
     * @return array
     */
    public function getEmarsysFieldNameContact($attdata, $storeId)
    {
        $stmt = $this->getConnection()->query("SELECT * FROM " . $this->getTable('emarsys_contact_field') . " WHERE emarsys_field_id = " . $attdata['emarsys_contact_field']. " AND store_id = " . $storeId. " ");
        return $stmt->fetch();
    }
    
    /**
     * 
     * @param type $id
     * @param type $storeId
     * @return array
     */
    public function getMagentoAttributeCode($id, $storeId)
    {
        $stmt = $this->getConnection()->query("SELECT attribute_code, attribute_code_custom, entity_type_id FROM " . $this->getTable('emarsys_magento_customer_attributes') . " WHERE id = '" . $id. "' AND store_id = '" . $storeId. "' ");
        return $stmt->fetch();
    }
}
