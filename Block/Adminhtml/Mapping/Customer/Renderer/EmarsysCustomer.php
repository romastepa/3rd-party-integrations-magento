<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Customer\Renderer;

use Magento\Framework\DataObject;

/**
 * Class EmarsysCustomer
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping\Customer\Renderer
 */
class EmarsysCustomer extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
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
     * @var \Emarsys\Emarsys\Model\ResourceModel\Customer
     */
    protected $resourceModelCustomer;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * EmarsysCustomer constructor.
     * @param \Magento\Backend\Model\Session $session
     * @param \Emarsys\Emarsys\Model\ResourceModel\Customer\CollectionFactory $collectionFactory
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Emarsys\Emarsys\Model\ResourceModel\Customer $resourceModelCustomer
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Backend\Model\Session $session,
        \Emarsys\Emarsys\Model\ResourceModel\Customer\CollectionFactory $collectionFactory,
        \Magento\Backend\Helper\Data $backendHelper,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $resourceModelCustomer,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->session = $session;
        $this->collectionFactory = $collectionFactory;
        $this->backendHelper = $backendHelper;
        $this->resourceModelCustomer = $resourceModelCustomer;
        $this->_storeManager = $storeManager;
    }

    /**
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        $attributeCode = $row->getData('attribute_code');
        $entityTypeId = $row->getEntityTypeId();
        $url = $this->backendHelper->getUrl('*/*/saveRow');
        $columnAttr = 'emarsys_contact_field';
        $html = '<select name="' . $row->getData('attribute_code_custom') . '" class="admin__control-select emaryscustomervalues" style="width:200px;" >
                 <option value=" ">Please Select</option>';
        $session = $this->session->getData();
        if (isset($session['store'])) {
            $storeId = $session['store'];
        }
        $emarsysContactFields = $this->resourceModelCustomer->getEmarsysContactFields($storeId);
        foreach ($emarsysContactFields as $field) {
            $chkSelected = $this->resourceModelCustomer->checkAttributeUsed($row->getData('attribute_code_custom'), $field['emarsys_field_id'], $storeId);
            $selected = "";
            if ($chkSelected['assigned'] == 1) {
                $selected = 'selected = selected';
            }
            $html .= '<option value="' . $field['emarsys_field_id'] . '" ' . $selected . '>' . $field['name'] . '</option>';
        }
        $html .= '</select>';

        return $html;
    }
}
