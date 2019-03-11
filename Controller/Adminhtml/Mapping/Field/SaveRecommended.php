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
use Emarsys\Emarsys\Model\FieldFactory;
use Emarsys\Emarsys\Model\ResourceModel\Field;
use Emarsys\Emarsys\Helper\Logs;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Emarsys\Emarsys\Model\Logs as EmarsysModelLogs;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class SaveRecommended
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Field
 */
class SaveRecommended extends Action
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
     * @var DateTime
     */
    protected $date;

    /**
     * @var FieldFactory
     */
    protected $fieldFactory;

    /**
     * @var Field
     */
    protected $resourceModelField;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var EmarsysModelLogs
     */
    protected $emarsysLogs;

    /**
     * @var Logs
     */
    protected $logHelper;

    /**
     * SaveRecommended constructor.
     * @param Context $context
     * @param FieldFactory $fieldFactory
     * @param Field $resourceModelField
     * @param PageFactory $resultPageFactory
     * @param Logs $logHelper
     * @param DateTime $date
     * @param EmarsysModelLogs $emarsysLogs
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        FieldFactory $fieldFactory,
        Field $resourceModelField,
        PageFactory $resultPageFactory,
        Logs $logHelper,
        DateTime $date,
        EmarsysModelLogs $emarsysLogs,
        ScopeConfigInterface $scopeConfigInterface,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->resultPageFactory = $resultPageFactory;
        $this->date = $date;
        $this->emarsysLogs = $emarsysLogs;
        $this->fieldFactory = $fieldFactory;
        $this->resourceModelField = $resourceModelField;
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->_storeManager = $storeManager;
        $this->logHelper = $logHelper;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store');
        $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $logsArray['job_code'] = 'Customer Filed Mapping';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'Running Recommended Mapping';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['website_id'] = $websiteId;
            $logsArray['store_id'] = $storeId;
            $this->resourceModelField->truncateMappingTable($storeId);
            $recommendedData = $this->resourceModelField->getRecommendedFieldAttribute($storeId);
            if (count($recommendedData)) {
                foreach ($recommendedData as $key => $code) {
                    if (isset($recommendedData)) {
                        $value = explode('-', $code);
                        $model = $this->fieldFactory->create();
                        $model->setEmarsysOptionId($value[0]);
                        $model->setEmarsysFieldId($value[1]);
                        $model->setMagentoOptionId($key);
                        $model->setStoreId($storeId);
                        $model->save();
                    }
                }
                $logId = $this->logHelper->manualLogs($logsArray);
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Recommended Mapping';
                $logsArray['description'] = 'Saved Recommended Mapping as ' .print_r($recommendedData,true);
                $logsArray['action'] = 'Saved Recommended Mapping Successful';
                $logsArray['message_type'] = 'Success';
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['log_action'] = 'True';
                $logsArray['status'] = 'success';
                $logsArray['messages'] = 'Saved Recommended Mapping Successful';
                $this->logHelper->logs($logsArray);
                $this->logHelper->manualLogs($logsArray);
                $this->messageManager->addSuccessMessage("Recommended Customer-Field attributes mapped successfully");
            } else {
                $logId = $this->logHelper->manualLogs($logsArray);
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Recommended Mapping';
                $logsArray['description'] = 'No Recommended Mappings';
                $logsArray['action'] = 'Recommended Mapping Completed';
                $logsArray['message_type'] = 'Error';
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['log_action'] = 'True';
                $logsArray['status'] = 'error';
                $logsArray['messages'] = 'Saved Recommended Mapping Completed';
                $this->logHelper->logs($logsArray);
                $this->logHelper->manualLogs($logsArray);
                $this->messageManager->addErrorMessage("No Recommendations are added");
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'SaveRecommended (Customer Filed)');
            $this->messageManager->addErrorMessage("Error occurred while mapping Customer-Field");
        }

        return $resultRedirect->setRefererOrBaseUrl();
    }
}
