<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\CronSchedule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Cron\Model\ScheduleFactory;

/**
 * Class Message
 * @package Emarsys\Emarsys\Controller\Adminhtml\CronSchedule
 */
class Message extends Action
{
    /**
     * @var ScheduleFactory
     */
    protected $scheduleFactory;

    /**
     * Message constructor.
     * @param Context $context
     * @param ScheduleFactory $scheduleFactory
     */
    public function __construct(
        Context $context,
        ScheduleFactory $scheduleFactory
    ) {
        $this->scheduleFactory = $scheduleFactory;
        parent::__construct($context);
    }

    /**
     * @return void|$this
     * @throws \RuntimeException
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            $cronDettails = $this->scheduleFactory->create()->load($id);
            if ($cronDettails) {
                printf("<pre>" . $cronDettails->getMessages() . "</pre>");
            } else {
                printf("No Data Available");
            }
        } else {
            printf("No Data Available");
        }
    }
}
