<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\CronSchedule;

use Magento\Backend\App\Action;
use Emarsys\Emarsys\Model\Logs;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Model\EmarsysCronDetails;

/**
 * Class Clear
 * @package Emarsys\Emarsys\Controller\Adminhtml\CronSchedule
 */
class Clear extends Action
{
    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var EmarsysCronDetails
     */
    protected $emarsysCronDetails;

    /**
     * Clear constructor.
     * @param Action\Context $context
     * @param Logs $emarsysLogs
     * @param StoreManagerInterface $storeManager
     * @param EmarsysCronDetails $emarsysCronDetails
     */
    public function __construct(
        Action\Context $context,
        Logs $emarsysLogs,
        StoreManagerInterface $storeManager,
        EmarsysCronDetails $emarsysCronDetails
    ) {
        $this->emarsysLogs = $emarsysLogs;
        $this->storeManager = $storeManager;
        $this->emarsysCronDetails = $emarsysCronDetails;
        parent::__construct($context);
    }

    /**
     * @return void|$this
     * @throws \RuntimeException
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setRefererOrBaseUrl();

        try {
            //clear cron details table
            $clearCronDetails = $this->emarsysCronDetails->clearEmarsysCronDetails();

            if ($clearCronDetails) {
                $this->messageManager->addSuccessMessage(__('Cron Details tables have been cleared successfully.'));
            } else {
                $this->messageManager->addErrorMessage('Something went wrong while clearing cron details table.');
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'Clear::execute()'
            );
            $this->messageManager->addErrorMessage('Something went wrong while clearing cron details table.');
        }

        return $resultRedirect;
    }
}
