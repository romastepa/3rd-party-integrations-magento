<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Emarsys\Emarsys\Helper\Event;
use Emarsys\Emarsys\Helper\Data;
use Emarsys\Emarsys\Model\ResourceModel\Event as EmarsysResourceModelEvent;
use Emarsys\Emarsys\Helper\Logs;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var
     */
    protected $customerResourceModel;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * SaveSchema constructor.
     * @param Context $context
     * @param Event $eventHelper
     * @param Data $emarsysHelper
     * @param EmarsysResourceModelEvent $eventResourceModel
     * @param PageFactory $resultPageFactory
     * @param Logs $logHelper
     * @param DateTime $date
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        Event $eventHelper,
        Data $emarsysHelper,
        EmarsysResourceModelEvent $eventResourceModel,
        PageFactory $resultPageFactory,
        Logs $logHelper,
        DateTime $date,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->resultPageFactory = $resultPageFactory;
        $this->eventResourceModel = $eventResourceModel;
        $this->eventHelper = $eventHelper;
        $this->logHelper = $logHelper;
        $this->date = $date;
        $this->_storeManager = $storeManager;
        $this->emarsysHelper = $emarsysHelper;
    }

    /**
     * SaveSchema Action
     * @return $this
     */
    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store');
        $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
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
            $logId = $this->logHelper->manualLogs($logsArray);
            $logsArray['id'] = $logId;

            if ($this->emarsysHelper->isEmarsysEnabled($websiteId) == 'true') {
                $errorStatus = false;
                $this->emarsysHelper->importEvents($logId);
                $this->messageManager->addSuccessMessage('Event schema added/updated successfully');
            } else {
                $logsArray['messages'] = 'Emarsys is Disabled for this Store';
                $logsArray['emarsys_info'] = 'Update Schema';
                $logsArray['description'] ='Update Schema was not Successful';
                $logsArray['action'] = 'Schame Updated';
                $logsArray['message_type'] = 'Error';
                $logsArray['log_action'] = 'True';
                $this->logHelper->logs($logsArray);
                $this->messageManager->addErrorMessage('Emarsys is not Enabled for this store');
            }
        } catch (\Exception $e) {
            $logsArray['emarsys_info'] = 'Update Schema';
            $logsArray['description'] = $e->getMessage();
            $logsArray['action'] = 'Update Schema not successful';
            $logsArray['message_type'] = 'Error';
            $logsArray['log_action'] = 'True';
            $this->logHelper->logs($logsArray);
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
        $this->logHelper->manualLogsUpdate($logsArray);

        return $resultRedirect->setRefererOrBaseUrl();
    }
}
