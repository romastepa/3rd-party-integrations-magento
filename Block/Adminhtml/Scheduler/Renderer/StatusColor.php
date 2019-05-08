<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Schedular
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Scheduler\Renderer;

use Magento\Framework\DataObject;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;

/**
 * Class StatusColor
 * @package Emarsys\Emarsys\Block\Adminhtml\Scheduler\Renderer
 */
class StatusColor extends AbstractRenderer
{
    /**
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        $value =  $row->getData($this->getColumn()->getIndex());
        switch ($value) {
            case 'success':
                $color = 'green';
                break;
            case 'error':
                $color = 'red';
                break;
            case 'notice':
                $color = '#EB5202';
                break;
            default:
                $color = 'green';
        }

        return '<span style="color:' . $color . ';">' . ucwords($value) . '</span>';
    }
}
