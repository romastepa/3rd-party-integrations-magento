<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Schedular
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml\Logs;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Widget\Context;

/**
 * Class Scheduler
 * @package Emarsys\Emarsys\Block\Adminhtml
 */
class Index extends Template
{
    protected $_template = 'cron/grid.phtml';

    /**
     * Scheduler constructor.
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        $this->setChild(
            'grid',
            $this->getLayout()->createBlock('Emarsys\Emarsys\Block\Adminhtml\Logs\Index\Grid', 'emarsys.logs.grid')
        );
        return parent::_prepareLayout();
    }

    /**
     * Render grid
     * @return string
     */
    public function getGridHtml()
    {
        return $this->getChildHtml('grid');
    }
}
