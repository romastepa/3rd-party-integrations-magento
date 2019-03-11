<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\System\Config\Form;

/**
 * Class LogCleanerButton
 * @package Emarsys\Emarsys\Block\System\Config\Form
 */
class LogCleanerButton extends Button
{
    /**
     * Path to block template
     */
    const TEST_CONNECTION_TEMPLATE = 'system/config/button/log_clean.phtml';

    /**
     * Test Connection Button Label
     *
     * @var string
     */
    protected $_testConnectionButtonLabel = 'Clear Logs';

    /**
     * Get the button and scripts contents
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $websiteId = $this->getRequest()->getParam('website');
        if ($websiteId == '') {
            $websiteId = 1;
        }
        $originalData = $element->getOriginalData();
        $ajaxUrl = $this->_urlBuilder->getUrl("emarsys_emarsys/log/clearlogs", ["website" => $websiteId]);
        $buttonLabel = !empty($originalData['button_label']) ? $originalData['button_label'] : $this->_testConnectionButtonLabel;
        $this->addData(
            [
                'button_label' => __($buttonLabel),
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $ajaxUrl,
            ]
        );

        return $this->_toHtml();
    }
}
