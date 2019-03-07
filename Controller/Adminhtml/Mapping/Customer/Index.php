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
use Emarsys\Emarsys\Helper\Data;

/**
 * Class Index
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Customer
 */
class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Data $emarsysHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Data $emarsysHelper
    ) {
        parent::__construct($context);
        $this->emarsysHelper = $emarsysHelper;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $store = $this->getRequest()->getParam('store');
        if (!$store) {
            $storeId = $this->emarsysHelper->getFirstStoreId();
            return $this->resultRedirectFactory->create()->setUrl($this->getUrl('*/*', ['store' => $storeId]));
        }
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $this->_setActiveMenu('Emarsys_Emarsys::emarsys_customer_mapping');
        $resultPage->addBreadcrumb(__('Emarsys - Customer Mapping'), __('Emarsys - Customer Mapping'));
        $resultPage->getConfig()->getTitle()->prepend(__('Emarsys - Customer Mapping'));

        return $resultPage;
    }
}
