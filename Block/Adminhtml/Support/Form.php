<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml\Support;

use Emarsys\Emarsys\Controller\Adminhtml\Support;
use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form as WidgetForm;

/**
 * Class Form
 * @package Emarsys\Emarsys\Block\Adminhtml\Support
 */
class Form extends WidgetForm
{
    /**
     * @var string
     */
    protected $_template = 'support.phtml';

    /**
     * Form constructor.
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setId('supportGeneralForm');
        $this->setTemplate('support.phtml');
    }

    /**
     * This method is called before rendering HTML
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->isFromError = $this->getRequest()->getParam('error') === 'true';

        $systemRequirementsBlock = $this->getLayout()->createBlock(
            'Emarsys\Emarsys\Block\Adminhtml\Support\Requirements',
            '',
            ['is_support_mode' => true]
        );
        $this->setChild('system_requirements', $systemRequirementsBlock);

        $emarsysVersionDetailsBlock = $this->getLayout()->createBlock(
            'Emarsys\Emarsys\Block\Adminhtml\Support\About',
            '',
            ['is_support_mode' => true]
        );
        $this->setChild('about_emarsys', $emarsysVersionDetailsBlock);

        return parent::_beforeToHtml();
    }
}

