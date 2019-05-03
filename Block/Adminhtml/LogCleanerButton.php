<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class LogCleanerButton
 *
 * @package Emarsys\Emarsys\Block\Adminhtml\Logs
 */
class LogCleanerButton extends Field
{

    /**
     * Retrieve onclick handler
     *
     * @return null|string
     */
    public function getLink()
    {
        return $this->getUrl('emarsys/logs/clearLogs');
    }

    /**
     * Retrieve HTML markup for given form element
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = '<td class="label"><label for="' .
            $element->getHtmlId() . '"><span' .
            $this->_renderScopeLabel($element) . '>' .
            '<a href="' . $this->getLink() . '">' . $element->getLabel() . '</a>' .
            '</span></label></td>';

        $html .= $this->_renderHint($element);

        return $this->_decorateRowHtml($element, $html);
    }
}
