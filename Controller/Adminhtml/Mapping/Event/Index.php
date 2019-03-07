<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Emarsys\Emarsys\Helper\Data;
use Emarsys\Emarsys\Model\ResourceModel\Emarsysmagentoevents\CollectionFactory;

/**
 * Class Index
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event
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
     * @param CollectionFactory $CollectionFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Data $emarsysHelper,
        CollectionFactory $CollectionFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->emarsysHelper = $emarsysHelper;
        $this->CollectionFactory = $CollectionFactory;
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
        $this->_setActiveMenu('Emarsys_Emarsys::emarsys_emarsysadminindex7');
        $resultPage->addBreadcrumb(__('Emarsys - Event Mapping'), __('Emarsys - Event Mapping'));
        $resultPage->getConfig()->getTitle()->prepend(__('Emarsys - Event Mapping'));

        return $resultPage;
    }
}
