<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml\Support\Edit;

/**
 * Class Form
 * @package Emarsys\Emarsys\Block\Adminhtml\System\Account\Edit
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_authSession;

    /**
     * @var \Magento\User\Model\UserFactory
     */
    protected $_userFactory;

    /**
     * @var \Magento\Framework\Locale\ListsInterface
     */
    protected $_localeLists;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\User\Model\UserFactory $userFactory
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\Locale\ListsInterface $localeLists
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\User\Model\UserFactory $userFactory,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\Locale\ListsInterface $localeLists,
        array $data = []
    ) {
        $this->_userFactory = $userFactory;
        $this->_authSession = $authSession;
        $this->_localeLists = $localeLists;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareForm()
    {
        $userId = $this->_authSession->getUser()->getId();
        $user = $this->_userFactory->create()->load($userId);
        $user->unsetData('password');
        $url = $this->getUrl("*/*/supportEmails");
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $fieldset = $form->addFieldset("support_form", ["legend" => '<h4 class="" style="background-color: #41362f;color:#fff;line-height: 20px;padding:10px">Support Information</h4>']);
        $fieldset->addField("type", "select", [
            'label'     => 'Support Type',
            'name'      => 'type',
            'required'  => true,
            'values'    => [
                ['value' => '', 'label' => 'Please Select'],
                ['value' => 'Sales Support||connect@emarsys.com', 'label' => 'Sales Support'],
                ['value' => 'Customization Service||support@emarsys.com', 'label' => 'Customization Service'],
                ['value' => 'Feedback and Complaint||connect@emarsys.com,support@emarsys.com', 'label' => 'Feedback and Complaint'],
                ['value' => 'Technical Support||support@emarsys.com', 'label' => 'Technical Support'],
                ['value' => 'Urgent Issue||support@emarsys.com', 'label' => 'Urgent Issue'],
                ['value' => 'Installation Service||support@emarsys.com', 'label' => 'Installation Service'],
                ['value' => 'Request Upgrade||support@emarsys.com', 'label' => 'Request Upgrade'],
                ['value' => 'Other||connect@emarsys.com,support@emarsys.com', 'label' => 'Other']
            ],
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
                    'value'     => 'Medium',
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

        $data = $user->getData();
        $form->setValues($data);
        $form->setAction($this->getUrl('*/*/save'));
        $form->setMethod('post');
        $form->setUseContainer(true);
        $form->setId('edit_form');

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
