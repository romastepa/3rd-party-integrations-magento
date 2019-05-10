<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Field;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Emarsys\Emarsys\Helper\Field;
use Emarsys\Emarsys\Model\ResourceModel\Field as EmarsysResourceModelField;
use Emarsys\Emarsys\Helper\Logs;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Emarsys\Emarsys\Model\Logs as EmarsysModelLogs;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class SaveSchema
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Field
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
     * @var Field
     */
    protected $fieldHelper;

    /**
     * @var EmarsysResourceModelField
     */
    protected $fieldResourceModel;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * SaveSchema constructor.
     * @param Context $context
     * @param Field $fieldHelper
     * @param EmarsysResourceModelField $fieldResourceModel
     * @param PageFactory $resultPageFactory
     * @param Logs $logHelper
     * @param DateTime $date
     * @param EmarsysModelLogs $emarsysLogs
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        Field $fieldHelper,
        EmarsysResourceModelField $fieldResourceModel,
        PageFactory $resultPageFactory,
        Logs $logHelper,
        DateTime $date,
        EmarsysModelLogs $emarsysLogs,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->resultPageFactory = $resultPageFactory;
        $this->fieldHelper = $fieldHelper;
        $this->emarsysLogs = $emarsysLogs;
        $this->fieldResourceModel = $fieldResourceModel;
        $this->_storeManager = $storeManager;
        $this->date = $date;
        $this->emarsysLogs = $emarsysLogs;
        $this->logHelper = $logHelper;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /**
         * To Get the schema from Emarsys and add/update in magento mapping table
         */
        $storeId = $this->getRequest()->getParam('store');
        $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
        $logsArray['job_code'] = 'Customer Filed Mapping';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = 'Running Update Schema';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Automatic';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['website_id'] = $websiteId;
        $logsArray['store_id'] = $storeId;
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $schemaData = $this->fieldHelper->getEmarsysOptionSchema($storeId);
            if (count($schemaData) > 0) {
                $this->fieldResourceModel->updateOptionSchema($schemaData, $storeId);
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logId = $this->logHelper->manualLogs($logsArray);
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Update Schema';
                $logsArray['description'] = 'Updated Schema as ' .print_r($schemaData,true);
                $logsArray['action'] = 'Update Schema Successful';
                $logsArray['message_type'] = 'Success';
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['log_action'] = 'True';
                $logsArray['status'] = 'success';
                $logsArray['messages'] = 'Update Schema Completed Successfully';
                $this->logHelper->logs($logsArray);
                $this->logHelper->manualLogs($logsArray);
                $this->messageManager->addSuccessMessage('Customer-Field schema added/updated successfully');
            } else {
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logId = $this->logHelper->manualLogs($logsArray);
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Update Schema';
                $logsArray['description'] = 'Failed to update Customer-Field Schema';
                $logsArray['action'] = 'Failed Update Schema';
                $logsArray['message_type'] = 'Success';
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['log_action'] = 'True';
                $logsArray['status'] = 'success';
                $logsArray['messages'] = 'Failed Update Schema';
                $this->logHelper->logs($logsArray);
                $this->logHelper->manualLogs($logsArray);
                $this->messageManager->addErrorMessage('Failed to update Customer-Field Schema');
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage('Failed to update Customer-Field Schema');
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'SaveSchema(Field)');
        }

        return $resultRedirect->setRefererOrBaseUrl();
    }
}
