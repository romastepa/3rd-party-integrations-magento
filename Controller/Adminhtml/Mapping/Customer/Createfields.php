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
use Magento\Framework\App\Request\Http;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Model\Api\Api;

/**
 * Class Createfields
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Customer
 */
class Createfields extends Action
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
     * Createfields constructor.
     * @param Context $context
     * @param CustomerFactory $customerFactory
     * @param Customer $resourceModelCustomer
     * @param PageFactory $resultPageFactory
     * @param Http $request
     * @param StoreManagerInterface $storeManager
     * @param Api $api
     */
    public function __construct(
        Context $context,
        CustomerFactory $customerFactory,
        Customer $resourceModelCustomer,
        PageFactory $resultPageFactory,
        Http $request,
        StoreManagerInterface $storeManager,
        Api $api
    ) {
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->resultPageFactory = $resultPageFactory;
        $this->resourceModelCustomer = $resourceModelCustomer;
        $this->customerFactory = $customerFactory;
        $this->getRequest = $request;
        $this->_storeManager = $storeManager;
        $this->api = $api;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            // Note: Change websiteId to dynamic after adding store switcher in Install checklist
            $websiteId = $this->getRequest->getParam('website');
            $this->api->setWebsiteId($websiteId);
            $mandatoryFields = $this->getRequest->getParam('customerfields');
            if ($mandatoryFields) {
                $mandatoryFields = explode(',', $mandatoryFields);

                foreach ($mandatoryFields as $mandatoryField) {
                    $data['name'] = $mandatoryField;
                    $data['application_type'] = "shorttext";
                    $this->api->sendRequest('POST', 'field', $data);
                }
                $this->messageManager->addSuccessMessage('Fileds created');
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setRefererOrBaseUrl();
    }
}
