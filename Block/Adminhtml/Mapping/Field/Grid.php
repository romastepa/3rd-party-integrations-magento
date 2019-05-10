<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Eav\Model\Entity\Type;
use Magento\Eav\Model\Entity\Attribute\Option;

/**
 * Class Grid
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping\Field
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Collection object
     *
     * @var \Magento\Framework\Data\Collection
     */
    protected $_collection;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var Data
     */
    protected $backendHelper;

    /**
     * @var Type
     */
    protected $entityType;

    /**
     * @var Option
     */
    protected $attribute;

    /**
     * Grid constructor.
     *
     * @param Context $context
     * @param Data $backendHelper
     * @param Type $entityType
     * @param Option $attribute
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        Type $entityType,
        Option $attribute,
        $data = []
    ) {
        $this->session = $context->getBackendSession();
        $this->entityType = $entityType;
        $this->attribute = $attribute;
        $this->backendHelper = $backendHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->session->setData('gridData', '');
        $storeId = $this->getRequest()->getParam('store');
        if (!isset($storeId)) {
            $storeId = 1;
        }
        $this->session->setData('store', $storeId);
    }

    /**
     * Apply sorting and filtering to collection
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $entitiesToMerge = ['customer_address', 'customer'];
        $entitiesData = [];
        if (!empty($entitiesToMerge)) {
            foreach ($entitiesToMerge as $entityCode) {
                $entity = $this->entityType->loadByCode($entityCode);
                $entitiesData['id'][] = $entity->getId();
            }
        }
        $entitiesData['id'] = array_unique($entitiesData['id']);

        $customCollection = $this->attribute->getCollection();
        $customCollection->addFieldToFilter('ea.entity_type_id', ['in' => $entitiesData['id']]);
        $customCollection->addFieldToFilter('ea.frontend_label', ['notnull' => true]);

        $customCollection->addFieldToFilter('ea.frontend_input', ['in' => ['select', 'multiselect']]);

        $customCollection->join(
            ['ea' => 'eav_attribute'],
            'ea.attribute_id = main_table.attribute_id',
            ['ea.attribute_code', 'ea.frontend_label', 'ea.frontend_input']
        );

        $customCollection->join(
            ['attrOptVal' => 'eav_attribute_option_value'],
            'main_table.option_id = attrOptVal.option_id',
            ['attrOptVal.value', 'attrOptVal.value_id']
        );
        $customCollection->addFieldToFilter('attrOptVal.store_id', ['eq' => 0]);
        $customCollection->setOrder('ea.frontend_label', 'ASC');
        $this->setCollection($customCollection);

        return parent::_prepareCollection();
    }

    /**
     * Initialize grid columns
     *
     * @return $this
     * @throws \Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'value',
            [
                'header' => __('Magento Fields'),
                'type' => 'varchar',
                'index' => 'value',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'renderer' => 'Emarsys\Emarsys\Block\Adminhtml\Mapping\Field\Renderer\Option',
            ]
        );
        $this->addColumn(
            'emarsys_contact_header',
            [
                'header' => __('Emarsys Fields'),
                'renderer' => 'Emarsys\Emarsys\Block\Adminhtml\Mapping\Field\Renderer\FieldOption',
                'filter' => false
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @param $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return '';
    }
}
