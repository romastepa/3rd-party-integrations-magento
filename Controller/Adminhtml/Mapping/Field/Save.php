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
use Emarsys\Emarsys\Model\Logs as EmarsysModelLogs;
use Emarsys\Emarsys\Helper\Data;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Save
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Field
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
     * @var FieldFactory
     */
    protected $fieldFactory;

    /**
     * @var Field
     */
    protected $resourceModelField;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Save constructor.
     * @param Context $context
     * @param FieldFactory $fieldFactory
     * @param Field $resourceModelField
     * @param PageFactory $resultPageFactory
     * @param Logs $logHelper
     * @param EmarsysModelLogs $emarsysLogs
     * @param Data $emarsysHelper
     * @param DateTime $date
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        FieldFactory $fieldFactory,
        Field $resourceModelField,
        PageFactory $resultPageFactory,
        Logs $logHelper,
        EmarsysModelLogs $emarsysLogs,
        Data $emarsysHelper,
        DateTime $date,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->date = $date;
        $this->emarsysHelper = $emarsysHelper;
        $this->resultPageFactory = $resultPageFactory;
        $this->emarsysLogs = $emarsysLogs;
        $this->resourceModelField = $resourceModelField;
        $this->fieldFactory = $fieldFactory;
        $this->logHelper = $logHelper;
        $this->_storeManager = $storeManager;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (isset($session['store'])) {
            $storeId = $session['store'];
        } else {
            $storeId = $this->emarsysHelper->getFirstStoreId();
        }
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
            $model = $this->fieldFactory->create();
            $session = $this->session->getData();

            $gridSessionStoreId = '';
            $gridSessionData = [];
            if (isset($session['gridData'])) {
                $gridSessionData = $session['gridData'];
            }
            if (isset($gridSessionData) && $gridSessionData != '') {
                foreach ($gridSessionData as $magentoOptionId => $emarsysFieldOption) {
                    if ($emarsysFieldOption == '') {
                        continue;
                    }

                    $emarsysField = explode('-', $emarsysFieldOption);
                    $emarsysOptionId = $emarsysField[1];
                    $emarsysFieldId = $emarsysField[0];
                    $modelColl = $model->getCollection()
                        ->addFieldToFilter('magento_option_id', $magentoOptionId)
                        ->addFieldToFilter('store_id', $storeId);
                    $modelCollData = $modelColl->getData();

                    foreach ($modelCollData as $cols) {
                        $colsValue = $cols['id'];
                    }

                    if (isset($colsValue)) {
                        $model = $model->load($colsValue);
                    }

                    if (!empty($modelCollData)) {
                        foreach ($modelColl as $model) {
                            if (isset($emarsysOptionId)) {
                                $model->setEmarsysOptionId($emarsysOptionId);
                                $model->setEmarsysFieldId($emarsysFieldId);
                            }
                            $model->save();
                        }
                    } else {
                        $model = $this->fieldFactory->create();
                        $model->setEmarsysOptionId($emarsysOptionId);
                        $model->setEmarsysFieldId($emarsysFieldId);
                        $model->setMagentoOptionId($magentoOptionId);
                        $model->setStoreId($storeId);
                        $model->save();
                    }
                }
            }
            $logId = $this->logHelper->manualLogs($logsArray);
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Save Customer Filed Mapping';
            $logsArray['description'] = 'Save Customer Filed Mapping Successful';
            $logsArray['action'] = 'Save Customer Filed Mapping Successful';
            $logsArray['message_type'] = 'Success';
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['log_action'] = 'True';
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Save Customer Filed Mapping Successful';
            $this->logHelper->logs($logsArray);
            $this->logHelper->manualLogs($logsArray);
            $this->messageManager->addSuccessMessage('Customer-Field attributes mapped successfully');
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'Save (Customer Filed)');
            $this->messageManager->addErrorMessage('Error occurred while mapping Customer-Field');
        }

        return $resultRedirect->setRefererOrBaseUrl();
    }
}
