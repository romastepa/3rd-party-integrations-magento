<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\SubscriberExport;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Index
 * @package Emarsys\Emarsys\Controller\Adminhtml\SubscriberExport
 */
class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var
     */
    protected $session;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->adminSession = $context->getSession();
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Index action
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $data = $this->adminSession->getFormData(true);
        $page = $this->resultPageFactory->create();
        $page->getLayout()->getBlock("head");
        $page->addBreadcrumb(__('Log'), __('Bulk Subscriber Export'));
        $this->_setActiveMenu('Emarsys_Emarsys::emarsys_emarsysadminindex9');
        $page->getConfig()->getTitle()->prepend(__('Bulk Subscriber Export'));

        return $page;
    }
}
