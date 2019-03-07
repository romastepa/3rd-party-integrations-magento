<?php
/**
 *
 * Copyright © 2015 Yagendracommerce. All rights reserved.
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Installation;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Emarsys\Emarsys\Helper\Data;

/**
 * Class Checklist
 * @package Emarsys\Emarsys\Controller\Adminhtml\Installation
 */
class Checklist extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Checklist constructor.
     * @param Context $context
     * @param Data $emarsysHelper
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        Data $emarsysHelper,
        PageFactory $resultPageFactory
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
            return $this->resultRedirectFactory->create()->setUrl($this->getUrl('*/*/checklist', ['store' => $storeId]));
        }
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Emarsys Installation Checklist'));

        return $resultPage;
    }
}
