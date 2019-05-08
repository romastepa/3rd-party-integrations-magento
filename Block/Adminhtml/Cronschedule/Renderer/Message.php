<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml\Cronschedule\Renderer;

use Magento\Framework\DataObject;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;

/**
 * Class Message
 * @package Emarsys\Emarsys\Block\Adminhtml\Cronschedule\Renderer
 */
class Message extends AbstractRenderer
{
    /**
     * @var BackendHelper
     */
    protected $backendHelper;

    /**
     * Params constructor.
     * @param BackendHelper $backendHelper
     */
    public function __construct(
        BackendHelper $backendHelper
    ) {
        $this->backendHelper = $backendHelper;
    }

    /**
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        $requestUrl = $this->backendHelper->getUrl(
            "emarsys_emarsys/cronschedule/message",
            ['id' => $row->getData('schedule_id')]
        );
        $html = '';

        if ($row->getData('messages')) {
            $html .= '<a href="#" onclick="openMyPopup(\'' . $requestUrl .
                '\')" >' . 'View' . '</a>';
        }

        return $html;
    }
}
