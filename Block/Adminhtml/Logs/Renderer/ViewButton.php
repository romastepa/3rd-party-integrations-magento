<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Log\Renderer;

use Magento\Framework\DataObject;

/**
 * Class ViewButton
 * @package Emarsys\Emarsys\Block\Adminhtml\Log\Renderer
 */
class ViewButton extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
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
     * ViewButton constructor.
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
     * @param Varien_Object $row
     * @return string
     */
    public function render(DataObject $row)
    {
        $rowData = $row->getData();
        $url = $this->backendHelper->getUrl('log/logdetails/logdetails', ['id' => $rowData['id']]);
        if (strtolower($rowData['message_type']) == 'success') {
            $usermsg = "<span style='color:green'>Success</span>";
        } elseif (strtolower($rowData['message_type']) == 'error') {
            $usermsg = "<span style='color:red'>Failed</span>";
        } elseif(strtolower($rowData['message_type']) == 'notice') {
            $usermsg = "<span style='color:orange'>Notice</span>";
        } else {
            $usermsg = "<span style='color:green'>Success</span>";
        }
        printf("<a href='" . $url . "' style='text-decoration:none'>" . $usermsg. "</a>");
    }
}
