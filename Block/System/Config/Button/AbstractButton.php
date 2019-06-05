<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\System\Config\Button;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Store\Model\Website;

/**
 * Class ApiTestConnection
 * @package Emarsys\Emarsys\Block\System\Config\Button
 */
abstract class AbstractButton extends Field
{
    /**
     * Test Connection Button Label
     * @var string
     */
    private $_testConnectionButtonLabel = 'Test Connections';

    /**
     * @var Website
     */
    protected $websiteCollection;

    /**
     * AbstractButton constructor.
     * @param Context $context
     * @param Website $website
     * @param array $data
     */
    public function __construct(
        Context $context,
        Website $website,
        array $data = []
    ) {
        $this->websiteCollection = $website;
        parent::__construct($context, $data);
    }

    /**
     * Render button
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $websiteId = $this->getRequest()->getParam('website');
        if ($websiteId == '') {
            $websites =  $this->websiteCollection->getCollection()->addFieldToFilter('is_default', 1);
            $website = $websites->getFirstItem();
            $websiteId = $website->getId() ? $website->getWebsiteId() : '';
        }

        $originalData = $element->getOriginalData();
        $buttonLabel = !empty($originalData['button_label']) ? $originalData['button_label'] : $this->_testConnectionButtonLabel;
        $this->addData(
            [
                'button_label' => __($buttonLabel),
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $this->getAjaxActionUrl($websiteId)
            ]
        );
        return $this->_toHtml();
    }

    /**
     * @param $websiteId
     * @return mixed
     */
    abstract protected function getAjaxActionUrl($websiteId);
}
