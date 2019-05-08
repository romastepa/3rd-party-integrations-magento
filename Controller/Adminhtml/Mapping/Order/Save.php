<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Emarsys\Emarsys\Helper\Logs;
use Emarsys\Emarsys\Model\ResourceModel\Order;

/**
 * Class Save
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Order
 */
class Save extends Action
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
     * @var Order
     */
    protected $orderResourceModel;

    /**
     * Save constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param StoreManagerInterface $storeManager
     * @param DateTime $date
     * @param Logs $logsHelper
     * @param Order $orderResourceModel
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        StoreManagerInterface $storeManager,
        DateTime $date,
        Logs $logsHelper,
        Order $orderResourceModel
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->logsHelper = $logsHelper;
        $this->date = $date;
        $this->storeManager = $storeManager;
        $this->orderResourceModel = $orderResourceModel;
        $this->session = $context->getSession();
    }

    /**
     * Save action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $session = $this->session->getData();
        if (isset($session['store'])) {
            $storeId = $session['store'];
        } else {
            $storeId = 1;
        }
        try {
            $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
            $logsArray['job_code'] = 'Order Mapping';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'Save Mapping';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['store_id'] = $storeId;
            $logsArray['website_id'] = $websiteId;
            $logId = $this->logsHelper->manualLogs($logsArray);

            $stringJSONData = json_decode(stripslashes($this->getRequest()->getParam('jsonstringdata')));
            $stringArrayData = (array)$stringJSONData;

            $this->orderResourceModel->insertIntoMappingTableCustomValue($stringArrayData, $storeId);

            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Saved Order Mapping Successfully';
            $logsArray['description'] = 'Save Entries as ' .print_r($stringArrayData,true);
            $logsArray['action'] = 'Save Order Schema';
            $logsArray['message_type'] = 'Success';
            $logsArray['status'] = 'Success';
            $logsArray['log_action'] = 'Save Order Schema';
            $logsArray['messages'] = 'Save Order Schema Successful';
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $this->logsHelper->logs($logsArray);
            $this->logsHelper->manualLogsUpdate($logsArray);
            $this->messageManager->addSuccessMessage(__('Order attributes mapped successfully'));
        } catch (\Exception $e) {
            if ($logId) {
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Save Mapping not Successful';
                $logsArray['description'] = $e->getMessage();
                $logsArray['action'] = 'Save Order Mapping';
                $logsArray['message_type'] = 'Error';
                $logsArray['log_action'] = 'Save Order Mapping';
                $logsArray['messages'] = 'Save Order Mapping not Successful';
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $this->logsHelper->logs($logsArray);
                $this->logsHelper->manualLogsUpdate($logsArray);
            }
            $this->messageManager->addErrorMessage(
                __('There was a problem while saving the order mapping. Please refer emarsys logs for more information.')
            );
        }
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setRefererOrBaseUrl();
    }
}
