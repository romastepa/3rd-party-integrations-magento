<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Product
 * @package Emarsys\Emarsys\Model\ResourceModel
 */
class Product extends AbstractDb
{
    /**
     * Define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('emarsys_product_mapping', 'emarsys_contact_field');
    }

    /**
     * Truncate Mapping Table
     * @param null $storeId
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function truncateMappingTable($storeId = null)
    {
        return $this->getConnection()->delete(
            $this->getMainTable(),
            $this->getConnection()->quoteInto("store_id = ?", $storeId)
        );
    }

    /**
     * @param null $storeId
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteUnmappedRows($storeId = null)
    {
        return $this->getConnection()->delete(
            $this->getMainTable(),
            ['store_id = ?' => $storeId, 'emarsys_attr_code = ?' => 0]
        );
    }

    /**
     * @param $attributeCode
     * @param null $storeId
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteExistingEmarsysAttr($attributeCode, $storeId = null)
    {
        return $this->getConnection()->delete(
            $this->getMainTable(),
            ['store_id = ?' => $storeId, 'emarsys_attr_code = ?' => $attributeCode]
        );
    }

    /**
     * @param $recommendedDatas
     * @param null $storeId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteRecommendedMappingExistingAttr($recommendedDatas, $storeId = null)
    {
        foreach ($recommendedDatas as $key => $recommendedData) {
            $attributeCode = $recommendedData['emarsys_attr_code'];

            $this->getConnection()->delete(
                $this->getMainTable(),
                ['store_id = ?' => $storeId, 'emarsys_attr_code = ?' => $attributeCode, 'magento_attr_code != ?' => $key]
            );
        }

        return true;
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getEmarsysAttrCount($storeId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_emarsys_product_attributes'), 'count(*)')
            ->where("store_id = ?", $storeId);

        return $this->getConnection()->fetchOne($select);
    }

    /**
     * @param $storeId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkProductMapping($storeId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getMainTable(), 'count(*)')
            ->where("store_id = ?", $storeId);

        return $this->getConnection()->fetchOne($select);
    }

    /**
     * @param $storeId
     * @return array
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
            $data = [
                'code' => $productField[0],
                'label' => $productField[1],
                'field_type' => $productField[2],
                'store_id' => $storeId
            ];
            $select = $this->getConnection()
                ->select()
                ->from($this->getTable('emarsys_emarsys_product_attributes'), 'code')
                ->where("code = ?", $productField[0])
                ->where("label = ?", $productField[1])
                ->where("field_type = ?", $productField[2])
                ->where("store_id = ?", $storeId);

            $result = $this->getConnection()->fetchOne($select);
            if (empty($result)) {
                $this->getConnection()->insert($this->getTable("emarsys_emarsys_product_attributes"), $data);
            }
        }
        return $productFields;
    }


    public function getProductAttributeLabelId($storeId)
    {
        $emarsysCodes = ['Item', 'Title', 'Link', 'Image', 'Category', 'Price'];
        $result = [];
        foreach ($emarsysCodes as $code) {
            $select = $this->getConnection()
                ->select()
                ->from($this->getTable('emarsys_emarsys_product_attributes'), 'id')
                ->where("code = ?", $code)
                ->where("store_id = ?", $storeId);

            $result[] = $this->getConnection()->fetchOne($select);
        }
        return $result;
    }

    /**
     * @param $storeId
     * @return array
     */
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

    /**
     * Get this value from Emarsys Attributes Table based Code & Store ID
     * @param $code
     * @param $storeId
     * @return mixed
     */
    public function getEmarsysAttributeIdByCode($code, $storeId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_emarsys_product_attributes'), 'id')
            ->where("code = ?", $code)
            ->where("store_id = ?", $storeId);

        return $this->getConnection()->fetchOne($select);
    }

    /**
     * @param type $storeId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMappedProductAttribute($storeId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getMainTable())
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
    }

    /**
     *
     * @param type $storeId
     * @param type $fieldId
     * @return array
     */
    public function getEmarsysFieldName($storeId, $fieldId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_emarsys_product_attributes'), 'label')
            ->where("id = ?", $fieldId)
            ->where("store_id = ?", $storeId);

        return trim(strtolower($this->getConnection()->fetchOne($select)));
    }
}
