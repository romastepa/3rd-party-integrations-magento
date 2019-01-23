<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Customer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Emarsys\Emarsys\Model\CustomerFactory;
use Emarsys\Emarsys\Model\ResourceModel\Customer;
use Emarsys\Emarsys\Helper\Data;
use Emarsys\Emarsys\Helper\Logs as EmarsysHelperLogs;
use Emarsys\Emarsys\Model\Logs;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Save
 *
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Customer
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
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var Customer
     */
    protected $resourceModelCustomer;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param CustomerFactory $customerFactory
     * @param Customer $resourceModelCustomer
     * @param Data $emsrsysHelper
     * @param EmarsysHelperLogs $logHelper
     * @param Logs $emarsysLogs
     * @param DateTime $date
     * @param PageFactory $resultPageFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        CustomerFactory $customerFactory,
        Customer $resourceModelCustomer,
        Data $emsrsysHelper,
        EmarsysHelperLogs $logHelper,
        Logs $emarsysLogs,
        DateTime $date,
        PageFactory $resultPageFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->emarsysLogs = $emarsysLogs;
        $this->emsrsysHelper = $emsrsysHelper;
        $this->resultPageFactory = $resultPageFactory;
        $this->resourceModelCustomer = $resourceModelCustomer;
        $this->customerFactory = $customerFactory;
        $this->logHelper = $logHelper;
        $this->date = $date;
        $this->_storeManager = $storeManager;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $session = $this->session->getData();
        if (isset($session['store'])) {
            $storeId = $session['store'];
        } else {
            $storeId = $this->emsrsysHelper->getFirstStoreId();
        }
        try {
            $savedFields = [];
            $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
            $logsArray['job_code'] = 'Customer Mapping';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'Running Update Schema';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['website_id'] = $websiteId;
            $logsArray['store_id'] = $storeId;
            $logId = $this->logHelper->manualLogs($logsArray);
            $stringJSONData = json_decode(stripslashes($this->getRequest()->getParam('jsonstringdata')));
            $stringArrayData = (array)$stringJSONData;

            foreach ($stringArrayData as $key => $value) {
                $custMageId = $this->resourceModelCustomer->getCustAttIdByCode($key, $storeId);
                $magentoAttributeId = $this->resourceModelCustomer->getCustomerAttributeId($key, $storeId);
                if (empty($magentoAttributeId)) {
                    continue;
                }
                if (empty(trim($value))) {
                    $this->resourceModelCustomer->deleteMapping($custMageId, $magentoAttributeId, $storeId);
                    continue;
                }
                $attData = $this->customerFactory->create()->getCollection()
                    ->addFieldToFilter('store_id', ['eq' => $storeId])
                    ->addFieldToFilter('magento_custom_attribute_id', ['eq' => $custMageId]);

                if (is_array($attData->getData()) && !empty($attData->getData())) {
                    $attDataAll = $attData->getData();
                    $attModel = $this->customerFactory->create()->load($attDataAll[0]['id']);
                } else {
                    $attModel = $this->customerFactory->create();
                }
                $savedFields[] = $value;
                $attModel->setData('emarsys_contact_field', $value);
                $attModel->setData('magento_custom_attribute_id', $custMageId);
                $attModel->setData('magento_attribute_id', $magentoAttributeId);
                $attModel->setData('store_id', $storeId);
                $attModel->save();
            }
            if ($savedFields) {
                $logsArray['description'] = 'Saved Fields Id(s) ' . print_r(implode(",", $savedFields), true);
            } else {
                $logsArray['description'] = 'Customer Mapping Saved';
            }
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Save Customer Mapping';
            $logsArray['action'] = 'Save Customer Mapping Successful';
            $logsArray['message_type'] = 'Success';
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['log_action'] = 'True';
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Save Customer Mapping Saved Successfully';
            $this->logHelper->logs($logsArray);
            $this->logHelper->manualLogs($logsArray);
            $this->messageManager->addSuccessMessage('Customer attributes mapped successfully');
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'SaveSchema(Customer)');
            $this->messageManager->addErrorMessage('Error occurred while mapping Customer attribute');
        }
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setRefererOrBaseUrl();
    }
}
