<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Support\Edit;

/**
 * Class Tabs
 * @package Emarsys\Emarsys\Block\Adminhtml\Support\Edit
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @var \Magento\Cms\Model\Wysiwyg\ConfigFactory
     */
    protected $wysiwygConfigFactory;

    /**
     * Tabs constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Cms\Model\Wysiwyg\ConfigFactory $wysiwygConfigFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Cms\Model\Wysiwyg\ConfigFactory $wysiwygConfigFactory,
        array $data = []
    ) {
        $this->wysiwygConfigFactory = $wysiwygConfigFactory;
        parent::__construct($context, $jsonEncoder, $authSession, $data);
    }

    /**
     * Constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId("support_tabs");
        $this->setDestElementId("edit_form");
    }

    /**
     * @return Mage_Core_Block_Abstract
     * @throws Exception
     */
    protected function _beforeToHtml()
    {
        $this->addTab(
            'form_section',
            [
                'content' => $this->getLayout()->createBlock('Emarsys\Emarsys\Adminhtml\Support\Edit\Tab\Form')->toHtml(),
                'active' => true
            ]
        );

        return parent::_beforeToHtml();
    }

    /**
     * @return Mage_Core_Block_Abstract|void
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $wysiwyg = $this->wysiwygConfigFactory->create();
        if ($wysiwyg->isEnabled()) {
            $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        }
    }
}
