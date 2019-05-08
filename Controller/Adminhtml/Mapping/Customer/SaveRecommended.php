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
use Emarsys\Emarsys\Model\Logs;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Emarsys\Emarsys\Helper\Logs as EmarsysHelperLogs;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class SaveRecommended
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Customer
 */
class SaveRecommended extends \Magento\Backend\App\Action
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
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * SaveRecommended constructor.
     * @param Context $context
     * @param CustomerFactory $customerFactory
     * @param Customer $resourceModelCustomer
     * @param PageFactory $resultPageFactory
     * @param Logs $emarsysLogs
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param DateTime $date
     * @param EmarsysHelperLogs $logHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        CustomerFactory $customerFactory,
        Customer $resourceModelCustomer,
        PageFactory $resultPageFactory,
        Logs $emarsysLogs,
        ScopeConfigInterface $scopeConfigInterface,
        DateTime $date,
        EmarsysHelperLogs $logHelper,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->resultPageFactory = $resultPageFactory;
        $this->customerFactory = $customerFactory;
        $this->resourceModelCustomer = $resourceModelCustomer;
        $this->date = $date;
        $this->emarsysLogs = $emarsysLogs;
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->emarsysLogs = $emarsysLogs;
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

            //Collect custom customer attribute mapping
            $customerCustomMappedAttrs = $this->resourceModelCustomer->getCustomMappedCustomerAttribute($storeId);

            // Truncating the Mapping Table first
            $this->resourceModelCustomer->truncateMappingTable($storeId);

            //Add custom customer attribute mapping
            if (!empty($customerCustomMappedAttrs)) {
                foreach ($customerCustomMappedAttrs as $key => $value) {
                    $model = $this->customerFactory->create();
                    $model->setEmarsysContactField($value['emarsys_contact_field']);
                    $model->setMagentoAttributeId($value['magento_attribute_id']);
                    $model->setMagentoCustomAttributeId($value['magento_custom_attribute_id']);
                    $model->setStoreId($storeId);
                    $model->save();
                }
            }

            // Here We need set the recommended attribute values
            $recommendedData = $this->resourceModelCustomer->getRecommendedCustomerAttribute($storeId);
            $emarsysCodes = [
                'first_name' => 'firstname',
                'middle_name' => 'middlename',
                'last_name' => 'lastname',
                'email' => 'email',
                'gender' => 'gender',
                'birth_date' => 'dob'
            ];
            if (isset($recommendedData['magento'])) {
                foreach ($recommendedData['magento'] as $key => $code) {
                    if (isset($recommendedData['emarsys'][$key]) && !empty($recommendedData['emarsys'][$key])) {
                        $model = $this->customerFactory->create();
                        $custMageId = $this->resourceModelCustomer->getCustAttIdByCode($emarsysCodes[$key],$storeId);
                        $model->setEmarsysContactField($recommendedData['emarsys'][$key]);
                        $model->setMagentoAttributeId($code);
                        $model->setMagentoCustomAttributeId($custMageId['id']);
                        $model->setStoreId($storeId);
                        $model->save();
                    }
                }
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Recommended Mapping';
                $logsArray['description'] = 'Saved Recommended Mapping as ' . print_r($emarsysCodes,true);
                $logsArray['action'] = 'Update Schema Successful';
                $logsArray['message_type'] = 'Success';
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['log_action'] = 'True';
                $logsArray['status'] = 'success';
                $logsArray['messages'] = 'Update Schema Completed Successfully';
                $this->logHelper->logs($logsArray);
                $this->logHelper->manualLogs($logsArray);
                $this->messageManager->addSuccessMessage("Recommended Customer attributes mapped successfully");
            } else {
                $this->messageManager->addErrorMessage("No Recommendations are added");
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'SaveSchema(Customer)');
            $this->messageManager->addErrorMessage("Error occurred while mapping Customer attribute");
        }
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setRefererOrBaseUrl();
    }
}
