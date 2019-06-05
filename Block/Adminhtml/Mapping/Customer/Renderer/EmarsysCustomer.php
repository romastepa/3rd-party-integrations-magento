<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Customer\Renderer;

use Emarsys\Emarsys\Model\ResourceModel\Customer;
use Emarsys\Emarsys\Model\ResourceModel\Customer\Collection;
use Emarsys\Emarsys\Model\ResourceModel\Customer\CollectionFactory;
use Emarsys\Emarsys\Model\ResourceModel\Sync;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Backend\Helper\Data;
use Magento\Backend\Model\Session;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class EmarsysCustomer
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping\Customer\Renderer
 */
class EmarsysCustomer extends AbstractRenderer
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Collection
     */
    protected $collectionFactory;

    /**
     * @var Data
     */
    protected $backendHelper;

    /**
     * @var Sync
     */
    protected $syncResourceModel;

    /**
     * @var Customer
     */
    protected $resourceModelCustomer;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * EmarsysCustomer constructor.
     * @param Session $session
     * @param CollectionFactory $collectionFactory
     * @param Data $backendHelper
     * @param Customer $resourceModelCustomer
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Session $session,
        CollectionFactory $collectionFactory,
        Data $backendHelper,
        Customer $resourceModelCustomer,
        StoreManagerInterface $storeManager
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function render(DataObject $row)
    {
        $storeId = $this->session->getStore();

        $emarsysContactFields = $this->resourceModelCustomer->getEmarsysContactFields($storeId);
        $chkSelected = $this->resourceModelCustomer->checkAttributeUsed($row->getId(), $storeId);

        $html = '<select name="' . $row->getData('attribute_code_custom') . '" class="admin__control-select emaryscustomervalues" style="width:200px;">
                 <option value=" ">Please Select</option>';
        foreach ($emarsysContactFields as $field) {
            $selected = ($chkSelected && in_array($field['emarsys_field_id'], $chkSelected)) ? 'selected = selected' : "";
            $html .= '<option value="' . $field['emarsys_field_id'] . '" ' . $selected . '>' . $field['name'] . '</option>';
        }
        $html .= '</select>';

        return $html;
    }
}
