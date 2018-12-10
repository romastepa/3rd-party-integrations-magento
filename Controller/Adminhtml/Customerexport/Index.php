<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Customerexport;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Emarsys\Emarsys\Helper\Data;

/**
 * Class Index
 * @package Emarsys\Emarsys\Controller\Adminhtml\Customerexport
 */
class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $adminSession;

    /**
     * @var Data
     */
    protected $emarsysHelper;

    /**
     * Index constructor.
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
        $this->adminSession = $context->getSession();
        $this->emarsysHelper = $emarsysHelper;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Index action
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $websiteId = $this->getRequest()->getParam('website');
        if (!$websiteId) {
            return $this->resultRedirectFactory->create()->setUrl(
                $this->getUrl(
                    '*/*',
                    ['website' => $this->emarsysHelper->getFirstWebsiteId()]
            ));
        }
        $page = $this->resultPageFactory->create();
        $page->getLayout()->getBlock("head");
        $this->_setActiveMenu('Emarsys_Emarsys::emarsys_emarsysadminindex9');
        $page->addBreadcrumb(__('Log'), __('Bulk Customer Export'));
        $page->getConfig()->getTitle()->prepend(__('Bulk Customer Export'));

        return $page;
    }
}
