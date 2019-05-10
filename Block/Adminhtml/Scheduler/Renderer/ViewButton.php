<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Schedular
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Scheduler\Renderer;

use Magento\Framework\DataObject;

/**
 * Class ViewButton
 * @package Emarsys\Emarsys\Block\Adminhtml\Scheduler\Renderer
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
     * @param DataObject $row
     * @return string|void
     */
    public function render(DataObject $row)
    {
        $url = $this->backendHelper->getUrl(
            'logs/grid/index',
            [
                'job_code' => $row['job_code'],
                'schedule_id' => $row['id'],
                'store' => $row['store_id'],
            ]
        );
        printf("<a href='%s'><div style='color:#EB5202 ;text-decoration: underline;text-decoration-color:#EB5202;'>View</div></a>", $url);
    }
}
