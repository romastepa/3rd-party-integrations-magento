<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Emrattribute;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Edit
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Emrattribute
 */
class Edit extends \Magento\Framework\App\Action\Action
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
     * @var \Emarsys\Emarsys\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Customer
     */
    protected $resourceModelCustomer;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    protected $_resultJsonFactory;

    /**
     *
     * @param Context $context
     * @param \Emarsys\Emarsys\Model\CustomerFactory $customerFactory
     * @param \Emarsys\Emarsys\Model\ResourceModel\Customer $resourceModelCustomer
     * @param PageFactory $resultPageFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        \Emarsys\Emarsys\Model\CustomerFactory $customerFactory,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $resourceModelCustomer,
        \Emarsys\Emarsys\Helper\Data $emsrsysHelper,
        \Emarsys\Emarsys\Helper\Logs $logHelper,
        \Emarsys\Emarsys\Model\Emrattribute $Emrattribute,
        \Emarsys\Emarsys\Model\Logs $emarsysLogs,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        PageFactory $resultPageFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->emarsysLogs = $emarsysLogs;
        $this->Emrattribute = $Emrattribute;
        $this->emsrsysHelper = $emsrsysHelper;
        $this->resultPageFactory = $resultPageFactory;
        $this->resourceModelCustomer = $resourceModelCustomer;
        $this->customerFactory = $customerFactory;
        $this->logHelper = $logHelper;
        $this->date = $date;
        $this->_storeManager = $storeManager;
        $this->_resultJsonFactory = $resultJsonFactory;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $requestParams = $this->getRequest()->getParams();
        $attributeId = $requestParams['Id'];
        $code = $requestParams['code'];
        $label = $requestParams['label'];
        $field_type = $requestParams['field_type'];
        if ($attributeId) {
            $productAttribute = $this->Emrattribute->load($attributeId);
            $productId = $productAttribute->getId();
            if ($productId) {
                if (strpos($code, 'c_') === false) {
                    $code = 'c_' . $code;
                }
                if (strpos($label, 'c_') === false) {
                    $label = 'c_' . $label;
                }
                $productAttribute->setCode($code);
                $productAttribute->setLabel($label);
                $field_type = ucfirst($field_type);
                $productAttribute->setFieldType($field_type);
                $productAttribute->save();
                $resultJson = $this->_resultJsonFactory->create();
                $data['status'] = 'SUCCESS';
                return $resultJson->setData($data);
            }
        }
    }
}
