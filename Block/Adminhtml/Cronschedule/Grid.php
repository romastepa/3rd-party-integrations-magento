<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml\Cronschedule;

use Magento\Cron\Model\ScheduleFactory;
use Magento\Cron\Model\Schedule;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Backend\Block\Widget\Grid\Extended;

/**
 * Class Grid
 * @package Emarsys\Emarsys\Block\Adminhtml\Cronschedule
 */
class Grid extends Extended
{
    /**
     * @var ScheduleFactory
     */
    protected $scheduleFactory;

    /**
     * Grid constructor.
     * @param Context $context
     * @param Data $backendHelper
     * @param ScheduleFactory $scheduleFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        ScheduleFactory $scheduleFactory,
        array $data = []
    ) {
        $this->scheduleFactory = $scheduleFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $cronJobs = $this->scheduleFactory->create()->getCollection();
        $cronJobs->getSelect()->joinLeft(
            ['ecd' => 'emarsys_cron_details'],
            'ecd.schedule_id = main_table.schedule_id',
            ['ecd.params']
        )->order('main_table.schedule_id DESC');

        $this->setCollection($cronJobs);

        return parent::_prepareCollection();
    }

    /**
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'schedule_id',
            [
                'header' => __('Id'),
                'align' => 'left',
                'width' => '50',
                'index' => 'schedule_id'
            ]
        );

        $this->addColumn(
            'job_code',
            [
                'header' => __('Job Code'),
                'align' => 'center',
                'index' => 'job_code',
                'width' => '300'
            ]
        );

        $this->addColumn(
            'created_at',
            [
                'header' => __('Created At'),
                'align' => 'left',
                'index' => 'created_at',
                'type' => 'datetime',
                'width' => '50'
            ]
        );

        $this->addColumn(
            'scheduled_at',
            [
                'header' => __('Scheduled At'),
                'align' => 'left',
                'index' => 'scheduled_at',
                'type' => 'datetime',
                'width' => '50'
            ]
        );

        $this->addColumn(
            'executed_at',
            [
                'header' => __('Executed At'),
                'align' => 'left',
                'index' => 'executed_at',
                'type' => 'datetime',
                'width' => '50'
            ]
        );

        $this->addColumn(
            'finished_at',
            [
                'header' => __('Finished At'),
                'align' => 'left',
                'index' => 'finished_at',
                'type' => 'datetime',
                'width' => '50'
            ]
        );

        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'align' => 'center',
                'index' => 'status',
                'type' => 'options',
                'width' => '200',
                'renderer' => 'Emarsys\Emarsys\Block\Adminhtml\Cronschedule\Renderer\StatusColor',
                'options' => [
                    Schedule::STATUS_PENDING => Schedule::STATUS_PENDING,
                    Schedule::STATUS_RUNNING => Schedule::STATUS_RUNNING,
                    Schedule::STATUS_SUCCESS => Schedule::STATUS_SUCCESS,
                    Schedule::STATUS_MISSED => Schedule::STATUS_MISSED,
                    Schedule::STATUS_ERROR => Schedule::STATUS_ERROR,
                ]
            ]
        );

        $this->addColumn(
            'params',
            [
                'header' => __('Params'),
                'align' => 'center',
                'width' => '50',
                'renderer' => 'Emarsys\Emarsys\Block\Adminhtml\Cronschedule\Renderer\Params',
                'filter' => false
            ]
        );

        $this->addColumn(
            'message',
            [
                'header' => __('Message'),
                "align" => "center",
                'width' => '50',
                'renderer' => 'Emarsys\Emarsys\Block\Adminhtml\Cronschedule\Renderer\Message',
                'filter' => false
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @param \Magento\Catalog\Model\Product|\Magento\Framework\DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return '';
    }
}
