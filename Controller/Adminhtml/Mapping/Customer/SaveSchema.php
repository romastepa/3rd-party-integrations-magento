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
use Emarsys\Emarsys\Helper\Customer;
use Emarsys\Emarsys\Model\ResourceModel\Customer as EmarsysResourceModelCustomer;
use Emarsys\Emarsys\Model\Logs;
use Emarsys\Emarsys\Helper\Logs as EmarsysHelperLogs;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Eav\Model\Entity\Attribute;

/**
 * Class SaveSchema
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Customer
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
     * @var EmarsysResourceModelCustomer
     */
    protected $customerResourceModel;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Attribute
     */
    protected  $attribute;

    /**
     * SaveSchema constructor.
     * @param Context $context
     * @param Customer $emarsysCustomerHelper
     * @param EmarsysResourceModelCustomer $customerResourceModel
     * @param PageFactory $resultPageFactory
     * @param Logs $emarsysLogs
     * @param EmarsysHelperLogs $logHelper
     * @param DateTime $date
     * @param StoreManagerInterface $storeManager
     * @param Attribute $attribute
     */
    public function __construct(
        Context $context,
        Customer $emarsysCustomerHelper,
        EmarsysResourceModelCustomer $customerResourceModel,
        PageFactory $resultPageFactory,
        Logs $emarsysLogs,
        EmarsysHelperLogs $logHelper,
        DateTime $date,
        StoreManagerInterface $storeManager,
        Attribute $attribute
    ) {
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->resultPageFactory = $resultPageFactory;
        $this->emarsysCustomerHelper = $emarsysCustomerHelper;
        $this->customerResourceModel = $customerResourceModel;
        $this->date = $date;
        $this->emarsysLogs = $emarsysLogs;
        $this->logHelper = $logHelper;
        $this->_storeManager = $storeManager;
        $this->attribute = $attribute;
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
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
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

            $customerAttData = $this->attribute->getCollection()
                ->addFieldToSelect('frontend_label')
                ->addFieldToSelect('attribute_code')
                ->addFieldToSelect('entity_type_id')
                ->addFieldToFilter('entity_type_id', ['in' => '1, 2'])
                ->getData();
            $this->customerResourceModel->insertCustomerMageAtts($customerAttData, $storeId);

            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Update Schema';
            $logsArray['description'] = 'Updated Schema as ' . print_r($customerAttData, true);
            $logsArray['action'] = 'Update Schema Successful';
            $logsArray['message_type'] = 'Success';
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['log_action'] = 'True';
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Update Schema Completed Successfully';
            $this->logHelper->logs($logsArray);
            $this->logHelper->manualLogs($logsArray);
            $schemaData = $this->emarsysCustomerHelper->getEmarsysCustomerSchema($storeId);

            if ($schemaData['data'] != '') {
                $this->customerResourceModel->updateCustomerSchema($schemaData, $storeId);
                $this->messageManager->addSuccessMessage('Customer schema added/updated successfully');
            } else {
                $this->messageManager->addErrorMessage($schemaData['replyText']);
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'SaveSchema(Customer)');
        }

        return $resultRedirect->setRefererOrBaseUrl();
    }
}
