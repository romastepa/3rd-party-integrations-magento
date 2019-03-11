<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\System\Config\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\ScopeInterface;
use Magento\Config\Block\System\Config\Form\Field;

/**
 * Class InitialLoadDb
 * @package Emarsys\Emarsys\Block\System\Config\Form
 */
class InitialLoadDb extends Field
{
    /**
     * @var
     */
    protected $customerResourceModel;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Request\Http $request,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel
    ) {
    
        $this->getRequest = $request;
        $this->_storeManager = $storeManager;
        $this->customerResourceModel = $customerResourceModel;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $checkTrue = '';
        $checkEmpty = '';
        $checkAttribute = '';
        $scope = ScopeInterface::SCOPE_WEBSITES;
        $websiteId = $this->getRequest->getParam('website');

        $fieldValueInDb = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/initial_db_load/initial_db_load', $scope, $websiteId);
        if ($fieldValueInDb == '' && $websiteId == 1) {
            $fieldValueInDb = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/initial_db_load/initial_db_load');
        }

        $attribute = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/initial_db_load/attribute', $scope, $websiteId);
        if ($attribute == '' && $websiteId == 1) {
            $attribute = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/initial_db_load/attribute');
        }

        $attributeValue = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/initial_db_load/attribute_value', $scope, $websiteId);
        if ($attributeValue == '' && $websiteId == 1) {
            $attributeValue = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/initial_db_load/attribute_value');
        }

        if ($fieldValueInDb == 'true') {
            $checkTrue = 'checked';
        } elseif ($fieldValueInDb == 'empty') {
            $checkEmpty = 'checked';
        } elseif ($fieldValueInDb == 'attribute') {
            $checkAttribute = 'checked';
        }

        $customerAttributes = [
            [
                'frontend_label' => 'Subscribed Status',
                'attribute_code' => 'subscribed_status',
                'entity_type_id' => 1
            ]
        ];

        $html = "
                <input type='radio' id='contacts_synchronization_initial_db_load_initial_db_load1' name='groups[initial_db_load][fields][initial_db_load][value]' value='true' $checkTrue>Set Opt-In Status for all users to true<br><br>
                <input type='radio' id='contacts_synchronization_initial_db_load_initial_db_load2' name='groups[initial_db_load][fields][initial_db_load][value]' value='empty' $checkEmpty>Set Opt-In Status for all users to empty<br><br>
                <input type='radio' id='contacts_synchronization_initial_db_load_initial_db_load3' name='groups[initial_db_load][fields][initial_db_load][value]' value='attribute' $checkAttribute>Set Opt-In Status true for all users depending on attribute<br><br>
                <select id='contacts_synchronization_initial_db_load_initial_db_load_attribute' name='groups[initial_db_load][fields][attribute][value]' style='width:163px' value='$attribute'>
            ";

        foreach ($customerAttributes as $field) {
            if ($field['entity_type_id'] == 2) {
                $fieldLabel = "Default Billing Address: " . $field['frontend_label'];
            } else {
                $fieldLabel = $field['frontend_label'];
            }
            $chkSelected = $field['attribute_code'] . "||" . $field['entity_type_id'];

            $selected = "";
            if ($chkSelected == $attribute) {
                $selected = 'selected = selected';
            }
            $html .= '<option value="' . $field['attribute_code'] . "||" . $field['entity_type_id'] . '" ' . $selected . '>' . $fieldLabel . '</option>';
        }
        $subscribed = '';
        $notActivated = '';
        $unscubscribed = '';
        $unconfirmed = '';
        if ($attributeValue == 1) {
            $subscribed = 'selected';
        } elseif ($attributeValue == 2) {
            $notActivated = 'selected';
        } elseif ($attributeValue == 3) {
            $unscubscribed = 'selected';
        } elseif ($attributeValue == 4) {
            $unconfirmed = 'selected';
        }

        $html .= "
                </select><br/><br/>
                <select id='contacts_synchronization_initial_db_load_initial_db_load_attribute_value' name='groups[initial_db_load][fields][attribute_value][value]' style='width:189px'>
                    <option value='1' $subscribed>Subscribed</option>
                    <option value='2' $notActivated>Not Activated</option>
                    <option value='3' $unscubscribed>Unsubscribed</option>
                    <option value='4' $unconfirmed>Unconfirmed</option>
                </select>
            ";
        return $html;
    }
}
