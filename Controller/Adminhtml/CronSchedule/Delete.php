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
 * Class Index
 * @package Emarsys\Emarsys\Controller\Adminhtml\CronSchedule
 */
class Delete extends Action
{

    /**
     * @var ScheduleFactory
     */
    private $scheduleFactory;

    /**
     * @var Data
     */
    private $jsonHelper;

    /**
     * Params constructor.
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
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        $cron = $this->scheduleFactory->create()->load($id);
        if ($cron->getId() && stristr($cron->getJobCode(), 'emarsys')) {
            $cron->delete();
            $this->messageManager->addSuccessMessage(__('Cron Job (%1: %2), removed successfully', $cron->getId(), $cron->getJobCode()));
        } else {
            $this->messageManager->addErrorMessage(__('Something goes wrong'));
        }

        return $this->_redirect('*/*');
    }
}
