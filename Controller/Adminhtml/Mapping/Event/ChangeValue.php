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
use Emarsys\Emarsys\Model\EventFactory;
use Emarsys\Emarsys\Model\ResourceModel\Event;
use Emarsys\Emarsys\Model\EmarsyseventmappingFactory;

/**
 * Class ChangeValue
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event
 */
class ChangeValue extends Action
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
     * @var \Emarsys\Emarsys\Model\EventFactory
     */
    protected $eventFactory;

    /**
     * ChangeValue constructor.
     * @param Context $context
     * @param EventFactory $eventFactory
     * @param Event $eventResourceModel
     * @param EmarsyseventmappingFactory $EmarsyseventmappingFactory
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        EventFactory $eventFactory,
        Event $eventResourceModel,
        EmarsyseventmappingFactory $EmarsyseventmappingFactory,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->eventResourceModel = $eventResourceModel;
        $this->EmarsyseventmappingFactory = $EmarsyseventmappingFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->eventFactory = $eventFactory;
        $this->_urlInterface = $context->getUrl();
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $magentoEventId = $this->getRequest()->getParam('magentoeventId');
        $emarsysEventId = $this->getRequest()->getParam('emarsyseventId');
        $id = $this->getRequest()->getParam('Id');
        $gridSession = $this->session->getMappingGridData();
        $gridSession[$id]['magento_event_id'] = $magentoEventId;
        $gridSession[$id]['emarsys_event_id'] = $emarsysEventId;
        $this->session->setMappingGridData($gridSession);
    }
}
