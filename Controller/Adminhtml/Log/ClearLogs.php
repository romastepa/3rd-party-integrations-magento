<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Log;

use Magento\Backend\App\Action\Context;
use Emarsys\Emarsys\Model\Logs;
use Emarsys\Emarsys\Model\LogSchedule;
use Magento\Backend\App\Action;
use Emarsys\Emarsys\Helper\Data;

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
     * @var Data
     */
    protected $emarsysHelper;

    /**
     * ClearLogs constructor.
     * @param Context $context
     * @param Logs $logs
     * @param LogSchedule $logSchedule
     * @param Data $emarsysHelper
     */
    public function __construct(
        Context $context,
        Logs $logs,
        LogSchedule $logSchedule,
        Data $emarsysHelper
    ) {
        parent::__construct($context);
        $this->logs = $logs;
        $this->logSchedule = $logSchedule;
        $this->emarsysHelper = $emarsysHelper;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setRefererOrBaseUrl();
        $storeId = $this->emarsysHelper->getFirstStoreId();

        try {
            $connection = $this->logSchedule->getResource()->getConnection();
            $tableName = $this->logSchedule->getResource()->getMainTable();
            $connection->truncateTable($tableName);

            $logsConnection = $this->logs->getResource()->getConnection();
            $logsTableName = $this->logs->getResource()->getMainTable();
            $logsConnection->truncateTable($logsTableName);

            $this->messageManager->addSuccessMessage(__('Log tables have been truncated successfully.'));
        } catch (\Exception $e) {
            $this->logs->addErrorLog($e->getMessage(), $storeId, 'Save (Customer Filed)');
            $this->messageManager->addErrorMessage('Something went wrong while deleting logs.');
        }

        return $resultRedirect;
    }
}
