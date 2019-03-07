<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Field\Renderer;

use Magento\Framework\DataObject;

/**
 * Class FieldOption
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping\Field\Renderer
 */
class FieldOption extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Customer\Collection
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $backendHelper;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Sync
     */
    protected $syncResourceModel;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Field
     */
    protected $resourceModelField;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * FieldOption constructor.
     * @param \Magento\Backend\Model\Session $session
     * @param \Emarsys\Emarsys\Model\ResourceModel\Customer\CollectionFactory $collectionFactory
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Emarsys\Emarsys\Model\ResourceModel\Field $resourceModelField
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Backend\Model\Session $session,
        \Emarsys\Emarsys\Model\ResourceModel\Customer\CollectionFactory $collectionFactory,
        \Magento\Backend\Helper\Data $backendHelper,
        \Emarsys\Emarsys\Model\ResourceModel\Field $resourceModelField,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->session = $session;
        $this->collectionFactory = $collectionFactory;
        $this->backendHelper = $backendHelper;
        $this->resourceModelField = $resourceModelField;
        $this->_storeManager = $storeManager;
    }

    /**
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        $optionId = $row->getData('option_id');
        $url = $this->backendHelper->getUrl('*/*/saveRow');
        $columnAttr = 'emarsys_field_option';
        $html = '<select name="emarsys_field_option" class="admin__control-select" style="width:350px;" onchange="changeValue(\'' . $url . '\', \'' . $optionId . '\', \'' . $columnAttr . '\', this.value)";>
            <option value=" ">Please Select</option>';
        $session = $this->session->getData();
        if (isset($session['store'])) {
            $storeId = $session['store'];
        } else {
            $storeId = 1;
        }
        $emarsysContactFields = $this->resourceModelField->getEmarsysFieldOption($storeId);
        foreach ($emarsysContactFields as $field) {
            $chkSelected = $this->resourceModelField->checkSelectedOption($optionId, $field['option_id'], $field['emarsys_field_id'], $storeId);
            $selected = '';
            if ($chkSelected == 1) {
                $selected = 'selected=selected';
            }
            $html .= '<option value="' . $field['emarsys_field_id'] . '-' . $field['option_id'] . '" ' . $selected . '>' . $field['type'] . '-' . $field['name'] . ':' . $field['option_name'] . '</option>';
        }
        $html .= '</select>';

        return $html;
    }
}
