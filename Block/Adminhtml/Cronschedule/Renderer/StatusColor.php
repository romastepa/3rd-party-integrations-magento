<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Schedular
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Cronschedule\Renderer;

use Magento\Cron\Model\Schedule;
use Magento\Framework\DataObject;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;

/**
 * Class StatusColor
 * @package Emarsys\Emarsys\Block\Adminhtml\Cronschedule\Renderer
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
            case Schedule::STATUS_PENDING:
                $color = 'black';
                break;
            case Schedule::STATUS_RUNNING:
                $color = 'orange';
                break;
            case Schedule::STATUS_SUCCESS:
                $color = 'green';
                break;
            case Schedule::STATUS_MISSED:
                $color = 'red';
                break;
            case Schedule::STATUS_ERROR:
                $color = 'red';
                break;
            default:
                $color = 'black';
        }

        return '<span style="color:' . $color . ';">' . ucwords($value) . '</span>';
    }
}
