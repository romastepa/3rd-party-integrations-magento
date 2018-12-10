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
     * @param null $websiteId
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function truncateMappingTable($websiteId = null)
    {
        return $this->getConnection()->delete(
            $this->getMainTable(),
            $this->getConnection()->quoteInto("website_id = ?", $websiteId)
        );
    }

    /**
     * @param null $websiteId
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteUnmappedRows($websiteId = null)
    {
        return $this->getConnection()->delete(
            $this->getMainTable(),
            ['website_id = ?' => $websiteId, 'emarsys_attr_code = ?' => 0]
        );
    }

    /**
     * @param $attributeCode
     * @param null $websiteId
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteExistingEmarsysAttr($attributeCode, $websiteId = null)
    {
        return $this->getConnection()->delete(
            $this->getMainTable(),
            ['website_id = ?' => $websiteId, 'emarsys_attr_code = ?' => $attributeCode]
        );
    }

    /**
     * @param $recommendedDatas
     * @param null $websiteId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteRecommendedMappingExistingAttr($recommendedDatas, $websiteId = null)
    {
        foreach ($recommendedDatas as $key => $recommendedData) {
            $attributeCode = $recommendedData['emarsys_attr_code'];

            $this->getConnection()->delete(
                $this->getMainTable(),
                ['website_id = ?' => $websiteId, 'emarsys_attr_code = ?' => $attributeCode, 'magento_attr_code != ?' => $key]
            );
        }

        return true;
    }

    /**
     * @param $websiteId
     * @return string
     */
    public function getEmarsysAttrCount($websiteId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_emarsys_product_attributes'), 'count(*)')
            ->where("website_id = ?", $websiteId);

        return $this->getConnection()->fetchOne($select);
    }

    /**
     * @param $websiteId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkProductMapping($websiteId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getMainTable(), 'count(*)')
            ->where("website_id = ?", $websiteId);

        return $this->getConnection()->fetchOne($select);
    }

    /**
     * @param $websiteId
     * @return array
     */
    public function updateProductSchema($websiteId)
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
                'website_id' => $websiteId
            ];
            $select = $this->getConnection()
                ->select()
                ->from($this->getTable('emarsys_emarsys_product_attributes'), 'code')
                ->where("code = ?", $productField[0])
                ->where("label = ?", $productField[1])
                ->where("field_type = ?", $productField[2])
                ->where("website_id = ?", $websiteId);

            $result = $this->getConnection()->fetchOne($select);
            if (empty($result)) {
                $this->getConnection()->insert($this->getTable("emarsys_emarsys_product_attributes"), $data);
            }
        }
        return $productFields;
    }


    public function getProductAttributeLabelId($websiteId)
    {
        $emarsysCodes = ['Item', 'Title', 'Link', 'Image', 'Category', 'Price'];
        $result = [];
        foreach ($emarsysCodes as $code) {
            $select = $this->getConnection()
                ->select()
                ->from($this->getTable('emarsys_emarsys_product_attributes'), 'id')
                ->where("code = ?", $code)
                ->where("website_id = ?", $websiteId);

            $result[] = $this->getConnection()->fetchOne($select);
        }
        return $result;
    }

    /**
     * @param $websiteId
     * @return array
     */
    public function getRequiredProductAttributesForExport($websiteId)
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
            $attrData['emarsys_attr_code'] = $this->getEmarsysAttributeIdByCode($value, $websiteId);
            $attrData['sync_direction'] = '';
            $attrData['website_id'] = $websiteId;
            $returnArray[] = $attrData;
        }

        return $returnArray;
    }

    /**
     * Get this value from Emarsys Attributes Table based Code & Store ID
     * @param $code
     * @param $websiteId
     * @return mixed
     */
    public function getEmarsysAttributeIdByCode($code, $websiteId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_emarsys_product_attributes'), 'id')
            ->where("code = ?", $code)
            ->where("website_id = ?", $websiteId);

        return $this->getConnection()->fetchOne($select);
    }

    /**
     * @param int $websiteId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMappedProductAttribute($websiteId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getMainTable())
            ->where('website_id = ?', $websiteId);

        $productAttributes = $this->getConnection()->fetchAll($select);

        $emarsysAttributeId = [];
        foreach ($productAttributes as $mapAttribute) {
            $emarsysAttributeId[] = $mapAttribute['emarsys_attr_code'];
        }

        $requiredMapping = $this->getRequiredProductAttributesForExport($websiteId);
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
     * @param type $websiteId
     * @param type $fieldId
     * @return array
     */
    public function getEmarsysFieldName($websiteId, $fieldId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('emarsys_emarsys_product_attributes'), 'label')
            ->where("id = ?", $fieldId)
            ->where("website_id = ?", $websiteId);

        return trim(strtolower($this->getConnection()->fetchOne($select)));
    }
}
