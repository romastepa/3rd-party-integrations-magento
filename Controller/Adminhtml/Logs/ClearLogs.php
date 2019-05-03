<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Logs;

use Magento\Backend\App\Action\Context;
use Emarsys\Emarsys\Model\Logs;
use Emarsys\Emarsys\Model\LogSchedule;
use Magento\Backend\App\Action;

/**
 * Class ClearLogs
 * @package Emarsys\Emarsys\Controller\Adminhtml\Log
 */
class ClearLogs extends Action
{
    /**
     * @var Logs
     */
    protected $logs;

    /**
     * @var LogSchedule
     */
    protected $logSchedule;

    /**
     * ClearLogs constructor.
     * @param Context $context
     * @param Logs $logs
     * @param LogSchedule $logSchedule
     */
    public function __construct(
        Context $context,
        Logs $logs,
        LogSchedule $logSchedule
    ) {
        parent::__construct($context);
        $this->logs = $logs;
        $this->logSchedule = $logSchedule;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setRefererOrBaseUrl();

        try {
            $connection = $this->logSchedule->getResource()->getConnection();
            $tableName = $this->logSchedule->getResource()->getMainTable();
            $connection->truncateTable($tableName);

            $logsConnection = $this->logs->getResource()->getConnection();
            $logsTableName = $this->logs->getResource()->getMainTable();
            $logsConnection->truncateTable($logsTableName);

            $this->messageManager->addSuccessMessage(__('Log tables have been truncated successfully.'));
        } catch (\Exception $e) {
            $this->logs->addErrorLog($e->getMessage(), 0, 'Clear Logs');
            $this->messageManager->addErrorMessage('Something went wrong while deleting logs.');
        }

        return $resultRedirect;
    }
}
