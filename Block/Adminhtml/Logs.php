<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml;

use Magento\Framework\Stdlib\DateTime\Timezone;

/**
 * Class Logs
 * @package Emarsys\Log\Block\Adminhtml
 */
class Logs extends \Magento\Backend\Block\Template
{
    /**
     * @var string
     */
    protected $_template = '';

    /**
     * @var \Emarsys\Emarsys\Model\LogScheduleFactory
     */
    protected $logScheduleFactory;

    /**
     * @var TimeZone
     */
    protected $timezone;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Emarsys\Emarsys\Model\LogScheduleFactory $logScheduleFactory
     * @param Timezone $timezone
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Emarsys\Emarsys\Model\LogScheduleFactory $logScheduleFactory,
        TimeZone $timezone,
        $data = []
    ) {
        $this->logScheduleFactory = $logScheduleFactory;
        $this->timezone = $timezone;
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
            $this->getLayout()->createBlock('Emarsys\Emarsys\Block\Adminhtml\Logs\Grid', 'loggrid')
        );
        $this->_template = 'logs/grid.phtml';

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
