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
class Product extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var Data
     */
    protected $dataHelper;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var Manager
     */
    protected $cacheManager;
    /**
     * @var
     */
    protected $sync;
    /**
     * @var
     */
    protected $resourceModelSync;
    /**
     * @var
     */
    protected $productFactory;
    /**
     * @var
     */
    protected $scopeConfigInterface;

    /**
     *
     * @param Context $context
     * @param Data $dataHelper
     * @param Manager $cacheManager
     * @param \Emarsys\Emarsys\Model\ResourceModel\Sync $resourceModelSync
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param TimeZone $timezone
     * @param \Emarsys\Emarsys\Model\Logs $emarsysLogs
     * @param Data $emarsysHelper
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param Logger $logger
     * @param null $connectionName
     */
    public function __construct(
        Context $context,
        Data $dataHelper,
        Manager $cacheManager,
        \Emarsys\Emarsys\Model\ResourceModel\Sync $resourceModelSync,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        TimeZone $timezone,
        \Emarsys\Emarsys\Model\Logs $emarsysLogs,
        \Emarsys\Emarsys\Helper\Data $emarsysHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        Logger $logger,
        $connectionName = null
    ) {
    

        $this->dataHelper = $dataHelper;
        $this->timezone = $timezone;
        $this->resourceModelSync = $resourceModelSync;
        $this->date = $date;
        $this->emarsysLogs = $emarsysLogs;
        $this->emarsysHelper = $emarsysHelper;
        $this->cacheManager = $cacheManager;
        $this->_storeManager = $storeManager;
        $this->productFactory = $productFactory;
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->logger = $logger;
        parent::__construct($context, $connectionName);
    }

    /**
     * @param null $storeId
     * Truncate Mapping Table
     */
    public function truncateMappingTable($storeId = null)
    {
        $this->getConnection()->query("DELETE FROM " . $this->getTable("emarsys_product_mapping") . " WHERE store_id = $storeId");
    }

    /**
     * @param null $storeId
     * Truncate Mapping Table
     */
    public function deleteUnmappedRows($storeId = null)
    {
        $this->getConnection()->query("DELETE FROM " . $this->getTable("emarsys_product_mapping") . " WHERE store_id = $storeId AND emarsys_attr_code = 0");
    }
    
    /**
     * Define main table
     * @return void
     */
    protected function _construct()
    {
        $this->_init('emarsys_product_mapping', 'emarsys_contact_field');
    }

    /**
     *
     * @param type $storeId
     * @return array
     */
    public function getEmarsysAttrCount($storeId)
    {
        try {
            $emarsysCount = $this->getConnection()->fetchOne("SELECT count(*) FROM " . $this->getTable('emarsys_emarsys_product_attributes') . " WHERE store_id=$storeId");
            return $emarsysCount;
        } catch (\Exception $e) {
            return $e->Message();
        }
    }

    /**
     * Checking count of the mapping table
     * @param type $storeId
     * @return array
     */
    public function checkProductMapping($storeId)
    {
        $productAttributes = $this->getConnection()->fetchOne("SELECT count(*) FROM " . $this->getTable('emarsys_product_mapping') . " WHERE store_id =" . $storeId);
        return $productAttributes;
    }

    /**
     *
     * @param type $storeId
     */
    public function updateProductSchema($storeId)
    {
        $productFields = [];
        $productFields[] = ['Item', 'Item', 'String'];
        $productFields[] = ['Title', 'Title', 'String'];
        $productFields[] = ['Link', 'Link', 'URL'];
        $productFields[] = ['Image', 'Image', 'URL'];
        $productFields[] = ['Zoom_image', 'Zoom image', 'URL'];
        $productFields[] = ['Category', 'Category', 'StringValue'];
        $productFields[] = ['Available', 'Available', 'Boolean'];
        $productFields[] = ['Description', 'Description', 'String'];
        $productFields[] = ['Price', 'Price', 'Float'];
        $productFields[] = ['Msrp', 'Msrp', 'Float'];
        $productFields[] = ['Album', 'Album', 'String'];
        $productFields[] = ['Actor', 'Actor', 'String'];
        $productFields[] = ['Artist', 'Artist', 'String'];
        $productFields[] = ['Author', 'Author', 'String'];
        $productFields[] = ['Brand', 'Brand', 'String'];
        $productFields[] = ['Year', 'Year', 'Integer'];
        
        foreach ($productFields as $productField) {
            $code = $productField[0];
            $label = $productField[1];
            $field_type = $productField[2];
            $existStmt = $this->getConnection()->query("SELECT code FROM " . $this->getTable('emarsys_emarsys_product_attributes') . " WHERE code = '" . $code. "' AND store_id = '" . $storeId . "' AND label = '" . $label . "' AND field_type = '" . $field_type . "'");
            if (empty($existStmt->fetch())) {
                $this->getConnection()->query("INSERT INTO " . $this->getTable("emarsys_emarsys_product_attributes") . " ( code, label, field_type, store_id) VALUES
                     ( '$code', '$label', '$field_type', '$storeId')
                ");
            }
        }
        return $productFields;
    }

    /**
     *
     * @return array
     */
    public function getProductAttributeLabelId($storeId)
    {
        $emarsysCodes = ['Item', 'Title', 'Link', 'Image', 'Category', 'Price'];
        $result = [];
        foreach ($emarsysCodes as $code) {
            $query = "SELECT id FROM " . $this->getTable("emarsys_emarsys_product_attributes") . " WHERE code = '" . $code . "' " . 'AND store_id =' . $storeId;
            $result[] = $this->getConnection()->fetchOne($query);
        }
        return $result;
    }

    public function getRequiredProductAttributesForExport($storeId)
    {
        $requiredMapping = [];
        $requiredMapping['sku'] = 'item'; // Mage_Attr_Code = Emarsys_Attr_Code
        $requiredMapping['name'] = 'title';
        $requiredMapping['quantity_and_stock_status'] = 'available';
        $requiredMapping['url_key'] = 'link';
        $requiredMapping['image'] = 'image';
        $requiredMapping['category_ids'] = 'category';
        $requiredMapping['price'] = 'price';
        $returnArray = [];
        foreach ($requiredMapping as $key => $value) {
            $attrData = [];
            $attrData['emarsys_contact_field'] = '';
            $attrData['magento_attr_code'] = $key;
            $attrData['emarsys_attr_code'] = $this->getEmarsysAttributeIdByCode($value, $storeId);
            $attrData['sync_direction'] = '';
            $attrData['store_id'] = $storeId;
            $returnArray[] = $attrData;
        }

        return $returnArray;
    }

    public function getEmarsysAttributeIdByCode($attrCode, $storeId)
    {
        $attrCode = $this->getConnection()->quote($attrCode);
        $emarsysAttributeId = '';
        $emarsysAttributeId = $this->getConnection()->fetchOne("SELECT id FROM " . $this->getTable('emarsys_emarsys_product_attributes') . " WHERE code = " . $attrCode. " AND store_id =" . $storeId); // Get this value from Emarsys Attributes Table based Code & Store ID
        return $emarsysAttributeId;
    }

    /**
     *
     * @param type $storeId
     * @return array
     */
    public function getMappedProductAttribute($storeId)
    {
        try {
            $select = $this->getConnection()
                ->select()
                ->from($this->getTable('emarsys_product_mapping'))
                ->where('store_id = ?', $storeId);

            $productAttributes = $this->getConnection()->fetchAll($select);
            $emarsysAttributeId = [];
            foreach ($productAttributes as $mapAttribute) {
                $emarsysAttributeId[] = $mapAttribute['emarsys_attr_code'];
            }

            $requiredMapping = $this->getRequiredProductAttributesForExport($storeId);
            foreach ($requiredMapping as $_requiredMapping) {
                if (!in_array($_requiredMapping['emarsys_attr_code'], $emarsysAttributeId)) {
                    $productAttributes[] = $_requiredMapping;
                } elseif ($_requiredMapping['magento_attr_code'] == 'quantity_and_stock_status'
                    && in_array($_requiredMapping['emarsys_attr_code'], $emarsysAttributeId)
                ) {
                    $key = array_search($_requiredMapping['emarsys_attr_code'], $emarsysAttributeId);
                    unset($productAttributes[$key]);
                    $productAttributes[] = $_requiredMapping;
                }
            }
            return array_values($productAttributes);
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'getMappedProductAttribute');
        }
    }

    /**
     *
     * @param type $storeId
     * @param type $fieldId
     * @return array
     */
    public function getEmarsysFieldName($storeId, $fieldId)
    {
        try {
            $emarsysFieldName = $this->getConnection()->fetchOne("SELECT label FROM " . $this->getTable('emarsys_emarsys_product_attributes') . " WHERE id = '" . $fieldId . "' AND store_id =" . $storeId);
            return trim(strtolower($emarsysFieldName));
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'getEmarsysFieldName');
        }
    }

    /**
     *
     * @param type $attributeId
     * @return array
     */
    public function getAttributeName($attributeId)
    {
        $attributeId = $this->getConnection()->quote($attributeId);
        try {
            $query = "SELECT entity_type_id,attribute_code FROM " . $this->getTable('eav_attribute') . "  WHERE entity_type_id = 4 AND attribute_code = " . $attributeId;
            $emarsysFieldName = $this->getConnection()->fetchAll($query);
            return $emarsysFieldName;
        } catch (\Exception $e) {
            $storeId = $this->_storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'getAttributeName');
        }
    }

    /**
     * @param $attributeCode
     * @param null $storeId
     * @return mixed
     */
    public function deleteExistingEmarsysAttr($attributeCode, $storeId = null)
    {
        try {
            $emarsysContactField = $this->getConnection()->fetchOne("SELECT emarsys_contact_field FROM " . $this->getTable('emarsys_product_mapping'). " WHERE emarsys_attr_code=$attributeCode AND store_id=$storeId");
            if(!empty($emarsysContactField)) {            
                $this->getConnection()->query("DELETE FROM " . $this->getTable("emarsys_product_mapping") . " WHERE store_id = $storeId AND emarsys_contact_field = $emarsysContactField");
            }
        } catch (\Exception $e) {
            return $e->Message();
        }
    }

    /**
     * @param $recommendedDatas
     * @param null $storeId
     * @return mixed
     */
    public function deleteRecommendedMappingExistingAttr($recommendedDatas, $storeId = null)
    {
        foreach ($recommendedDatas as $key => $recommendedData)
        {
            $attributeCode = $recommendedData['emarsys_attr_code'];
            try {
                $stm = "SELECT emarsys_contact_field FROM " . $this->getTable('emarsys_product_mapping') . " WHERE emarsys_attr_code=$attributeCode AND store_id=$storeId AND magento_attr_code != '". $key. "' ";
                $emarsysContactField = $this->getConnection()->fetchOne($stm);
                if (!empty($emarsysContactField)) {
                    $this->getConnection()->query("DELETE FROM " . $this->getTable("emarsys_product_mapping") . " WHERE store_id = $storeId AND emarsys_contact_field = $emarsysContactField");
                }
            } catch (\Exception $e) {
                return $e->Message();
            }
        }
    }
}
