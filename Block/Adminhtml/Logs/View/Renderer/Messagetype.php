<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml\Logs\View\Renderer;

use Magento\Framework\DataObject;

/**
 * Class Messagetype
 * @package Emarsys\Emarsys\Block\Adminhtml\Logs\Renderer
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
     * @return string|void
     */
    public function render(DataObject $row)
    {
        $rowData = $row->getData();
        if (strtolower($rowData['message_type']) == 'success') {
            $usermsg = "<span style='color:green'>Success</span>";
        } elseif (strtolower($rowData['message_type']) == 'error') {
            $usermsg = "<span style='color:red'>Failed</span>";
        } elseif(strtolower($rowData['message_type']) == 'notice') {
            $usermsg = "<span style='color:#EB5202'>Notice</span>";
        } else {
            $usermsg = "<span style='color:green'>Success</span>";
        }
        printf($usermsg);
    }
}
