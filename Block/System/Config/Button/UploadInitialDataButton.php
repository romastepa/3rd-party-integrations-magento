<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\System\Config\Button;

/**
 * Class UploadInitialDataButton
 * @package Emarsys\Emarsys\Block\System\Config\Button
 */
class UploadInitialDataButton extends AbstractButton
{
    /**
     * Path to block template
     */
    const TEST_CONNECTION_TEMPLATE = 'system/config/button/uploadinitialdb.phtml';

    /**
     * Test Connection Button Label
     *
     * @var string
     */
    protected $_testConnectionButtonLabel = 'Export';

    /**
     * Set template to itself
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::TEST_CONNECTION_TEMPLATE);
        }
        return $this;
    }

    /**
     * Get the button and scripts contents
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $websiteId = $this->getRequest()->getParam('website');
        $websiteId = $websiteId ? $websiteId : 0;

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
     * @return string
     */
    protected function getAjaxActionUrl($websiteId)
    {
        return $this->getUrl("emarsys_emarsys/uploadinitialdb/index", ["website" => $websiteId]);
    }
}
