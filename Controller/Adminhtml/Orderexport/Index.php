<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Orderexport;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Index
 * @package Emarsys\Emarsys\Controller\Adminhtml\Orderexport
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
        $page->addBreadcrumb(__('Log'), __('Bulk Order Export'));
        $this->_setActiveMenu('Emarsys_Emarsys::emarsys_emarsysadminindex10');
        $page->getConfig()->getTitle()->prepend(__('Bulk Order Export'));

        return $page;
    }
}
