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
use Emarsys\Emarsys\Model\PlaceholdersFactory;

/**
 * Class Saveplaceholdermapping
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event
 */
class Saveplaceholdermapping extends Action
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
     * @var EventFactory
     */
    protected $eventFactory;

    /**
     * Saveplaceholdermapping constructor.
     * @param Context $context
     * @param EventFactory $eventFactory
     * @param Event $eventResourceModel
     * @param PlaceholdersFactory $emarsysEventPlaceholderMappingFactory
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        EventFactory $eventFactory,
        Event $eventResourceModel,
        PlaceholdersFactory $emarsysEventPlaceholderMappingFactory,
        PageFactory $resultPageFactory
    ) {
    
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->eventResourceModel = $eventResourceModel;
        $this->resultPageFactory = $resultPageFactory;
        $this->eventFactory = $eventFactory;
        $this->emarsysEventPlaceholderMappingFactory = $emarsysEventPlaceholderMappingFactory;
    }

    /**
     * SavePlaceHolderMapping Action
     * @return $this
     */
    public function execute()
    {
        try {
            $resultRedirect = $this->resultRedirectFactory->create();
            $placeholderData = $this->getRequest()->getPost('placeholderData');
            $placeholders = html_entity_decode($placeholderData);
            $placeholderDataDecode = json_decode($placeholders, true);

            foreach ($placeholderDataDecode as $placeholderItem) {
                foreach ($placeholderItem as $key => $value) {
                    $emarsysEventPlaceholderMapping = $this->emarsysEventPlaceholderMappingFactory->create()->load($key);
                    $emarsysEventPlaceholderMapping->setEmarsysPlaceholderName($value);
                    $emarsysEventPlaceholderMapping->save();
                }
            }
            $this->messageManager->addSuccessMessage("Placeholders mapped successfully");
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage("Error occurred while mapping Event " . $e->getMessage());
        }

        return $resultRedirect->setRefererOrBaseUrl();
    }
}
