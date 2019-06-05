<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Support\Edit\Tab;

/**
 * Class Form
 * @package Emarsys\Emarsys\Block\Adminhtml\Support\Edit\Tab
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * Form constructor.
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Backend\Model\Auth\Session $session
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Backend\Model\Auth\Session $session,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->session = $session;
        $this->admin = $context->getBackendSession();
        $this->_formFactory = $formFactory;
        $this->_registry = $registry;
        $this->storeManagerInterface = $context->getStoreManager();
    }

    /**
     * Init form
     */
    protected function _prepareForm()
    {
        $userId = $this->session->getUser()->getId();
        $user = $this->admin;
        $url = $this->getUrl("*/*/supportEmails");
        $form = $this->_formFactory->create();

        $this->setForm($form);
        $fieldset = $form->addFieldset("support_form", ["legend" => '<h4 class="" style="background-color: #41362f;color:#fff;line-height: 20px;padding:10px">Support Information</h4>']);

        $fieldset->addField("type", "select", [
            'label'     => 'Support Type',
            'name'      => 'type',
            'required'  => true,
            'onchange'  => 'support(\'' . $url. '\', this.value)',
            'values'    => [
                ['value' => '' , 'label' => 'Please Select'],
                ['value' => 'Sales Support', 'label' => 'Sales Support'],
                ['value' => 'Customization Service', 'label' => 'Customization Service'],
                ['value' => 'Feedback and Complaint', 'label' => 'Feedback and Complaint'],
                ['value' => 'Technical Support', 'label' => 'Technical Support'],
                ['value' => 'Urgent Issue', 'label' => 'Urgent Issue'],
                ['value' => 'Installation Service', 'label' => 'Installation Service'],
                ['value' => 'Request Upgrade', 'label' => 'Request Upgrade'],
                ['value' => 'Other', 'label' => 'Other']
            ],
        ]);

        $fieldset->addField("show_email", "label", [
            "label" => "Email",
            "id"    => "email",
            "class" => "showemail",
            'after_element_html' => '<span id="show-email"></span>',
        ]);

        $fieldset->addField("firstname", "text", [
            "label" => "Name",
            "class" => "required-entry",
            "required" => true,
            "name" => "firstname",
        ]);

        $fieldset->addField("subject", "text", [
            "label" => "Subject",
            "class" => "required-entry",
            "required" => true,
            "name" => "subject",
        ]);

        $fieldset->addField("priority", "select", [
            'label'     => 'Priority',
            'name'      => 'priority',
            'values'    => [
                [
                    'value'     => 'Normal',
                    'label'     => 'Normal',
                ],
                [
                    'value'     => 'Normal',
                    'label'     => 'Medium',
                ],

                [
                    'value'     => 'High',
                    'label'     => 'High',
                ],
                [
                    'value'     => 'Urgent',
                    'label'     => 'Urgent',
                ],
            ],
        ]);

        $fieldset->addField('message', 'textarea', [
                'name' => 'message',
                'label' => 'Message',
                'title' => 'Message',
            'required' => true
        ]);

        $fieldset->addField("email", "text", [
            "label" => "Email",
            "name"  => "email",
            "id"    => "email",
            "index" => "email",
            "class" => "hiddensupportemail"
        ]);

        if ($this->session->getSupportData()) {
            $support_data = $this->session->getSupportData();
            $this->session->setSupportData(null);
        } elseif ($this->_registry->registry('support_data')) {
            $support_data = $this->_registry->registry('support_data')->getData();
        }

        if (isset($support_data['file']) && $support_data['file']) {
            $baseUrl = $this->storeManagerInterface->getStore()->getBaseUrl();
            $support_data['file'] = $baseUrl . 'support/' . $support_data['file'];
        }

        $form->setValues($user->getData());

        return parent::_prepareForm();
    }
}
