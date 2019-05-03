<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Schedular
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml\Logs;

use Emarsys\Emarsys\Model\LogScheduleFactory;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\Timezone;

/**
 * Class Logs
 * @package Emarsys\Log\Block\Adminhtml
 */
class View extends Template
{
    /**
     * @var string
     */
    protected $_template = 'logs/grid.phtml';

    /**
     * @var LogScheduleFactory
     */
    protected $logScheduleFactory;

    /**
     * @var TimeZone
     */
    protected $timezone;

    /**
     * @param Context $context
     * @param LogScheduleFactory $logScheduleFactory
     * @param Timezone $timezone
     * @param array $data
     */
    public function __construct(
        Context $context,
        LogScheduleFactory $logScheduleFactory,
        TimeZone $timezone,
        $data = []
    ) {
        $this->logScheduleFactory = $logScheduleFactory;
        $this->timezone = $timezone;
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    protected function _prepareLayout()
    {
        $this->setChild(
            'grid',
            $this->getLayout()->createBlock('Emarsys\Emarsys\Block\Adminhtml\Logs\View\Grid', 'log_view_grid')
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

    /**
     * @return mixed
     */
    public function getLogDetails()
    {
        $collection = $this->logScheduleFactory->create()->load($this->_request->getParam('schedule_id'));
        return $collection->getData();
    }

    public function getStoreId()
    {
        $storeId = $this->getRequest()->getParam('store');
        return $storeId;
    }

    /**
     * @return Timezone
     */
    public function getDateObject()
    {
        return $this->timezone;
    }
}
