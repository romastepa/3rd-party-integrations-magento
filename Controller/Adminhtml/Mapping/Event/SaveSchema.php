<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event;

use Magento\{
    Backend\App\Action,
    Backend\App\Action\Context,
    Framework\View\Result\PageFactory,
    Framework\Stdlib\DateTime\DateTime,
    Store\Model\StoreManagerInterface
};
use Emarsys\Emarsys\{
    Helper\Data,
    Helper\Logs
};

/**
 * Class SaveSchema
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event
 */
class SaveSchema extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var
     */
    protected $customerHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logs
     */
    protected $logsHelper;

    /**
     * @var Data
     */
    protected $emarsysHelper;

    /**
     * @var DateTime 
     */
    protected $date;

    /**
     * SaveSchema constructor.
     * @param Context $context
     * @param Data $emarsysHelper
     * @param PageFactory $resultPageFactory
     * @param Logs $logsHelper
     * @param DateTime $date
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        Data $emarsysHelper,
        PageFactory $resultPageFactory,
        Logs $logsHelper,
        DateTime $date,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->resultPageFactory = $resultPageFactory;
        $this->logsHelper = $logsHelper;
        $this->date = $date;
        $this->storeManager = $storeManager;
        $this->emarsysHelper = $emarsysHelper;
    }

    /**
     * SaveSchema Action
     * @return $this
     * @throws \Exception
     */
    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store');
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        $resultRedirect = $this->resultRedirectFactory->create();
        $errorStatus = true;
        try {
            $logsArray['job_code'] = 'Event Mapping';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'Running Update Schema';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['store_id'] = $storeId;
            $logsArray['website_id'] = $websiteId;
            $logId = $this->logsHelper->manualLogs($logsArray);
            $logsArray['id'] = $logId;

            if ($this->emarsysHelper->isEmarsysEnabled($websiteId)) {
                $errorStatus = false;
                $this->emarsysHelper->importEvents($storeId, $logId);
                $this->messageManager->addSuccessMessage('Event schema added/updated successfully');
            } else {
                $logsArray['messages'] = 'Emarsys is Disabled for this Store';
                $logsArray['emarsys_info'] = 'Update Schema';
                $logsArray['description'] ='Update Schema was not Successful';
                $logsArray['action'] = 'Schame Updated';
                $logsArray['message_type'] = 'Error';
                $logsArray['log_action'] = 'True';
                $this->logsHelper->logs($logsArray);
                $this->messageManager->addErrorMessage('Emarsys is not Enabled for this store');
            }
        } catch (\Exception $e) {
            $logsArray['emarsys_info'] = 'Update Schema';
            $logsArray['description'] = $e->getMessage();
            $logsArray['action'] = 'Update Schema not successful';
            $logsArray['message_type'] = 'Error';
            $logsArray['log_action'] = 'True';
            $this->logsHelper->logs($logsArray);
            $this->messageManager->addErrorMessage('Error occurred while Updating Schema' . $e->getMessage());
        }

        if ($errorStatus) {
            $logsArray['messages'] = 'Error occurred while Events Updating Schema';
            $logsArray['status'] = 'error';
        } else {
            $logsArray['messages'] = 'Events Update Schema Successful';
            $logsArray['status'] = 'success';
        }
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        $this->logsHelper->manualLogsUpdate($logsArray);

        return $resultRedirect->setRefererOrBaseUrl();
    }
}
