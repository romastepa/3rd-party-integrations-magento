<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Schedular
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Cron\Renderer;

use Magento\Framework\DataObject;

/**
 * Class Messagetype
 * @package Emarsys\Emarsys\Block\Adminhtml\Cron\Renderer
 */
class Messagetype extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $backendHelper;

    /**
     * Messagetype constructor.
     * @param \Magento\Backend\Model\Session $session
     * @param \Magento\Backend\Helper\Data $backendHelper
     */
    public function __construct(
        \Magento\Backend\Model\Session $session,
        \Magento\Backend\Helper\Data $backendHelper
    ) {
        $this->session = $session;
        $this->backendHelper = $backendHelper;
    }

    /**
     * @param DataObject $row
     */
    public function render(DataObject $row)
    {
        $logconfigurl =$this->backendHelper->getUrl('adminhtml/system_config/edit/section/emarsyslog');
         printf("<a href='" . $logconfigurl. "' style='text-decoration:none'><div style='color:red'>Edit</div></a>");
    }
}
