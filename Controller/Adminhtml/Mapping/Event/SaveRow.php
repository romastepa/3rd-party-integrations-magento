<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

/**
 * Class SaveRow
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event
 */
class SaveRow extends Action
{
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * SaveRow constructor.
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
        $this->session = $context->getSession();
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $requestParams = $this->getRequest()->getParams();
        $attributeCode = $requestParams['attribute'];
        $attributeValue = $requestParams['attributeValue'];
        $coulmnAttr = $requestParams['columnAttr'];
        $session = $this->session->getData();
        $gridSession = $session['gridData'];
        $gridSession[$attributeCode][$coulmnAttr] = $attributeValue;
        $this->session->setData('gridData', $gridSession);
    }
}
