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
use Emarsys\Emarsys\Model\ResourceModel\Emarsyseventmapping\CollectionFactory as EmarsysEventMappingCollectionFactory;
use Emarsys\Emarsys\Model\ResourceModel\Emarsysmagentoevents\CollectionFactory as EmarsysMagentoEventsCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Emarsys\Emarsys\Model\ResourceModel\Emarsysevents\CollectionFactory as EmarsysEventsCollectionFactory;
use Magento\Email\Model\TemplateFactory;
use Magento\Email\Block\Adminhtml\Template\Edit;

/**
 * Class Placeholders
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event
 */
class Placeholders extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Placeholders constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param EmarsysEventMappingCollectionFactory $emarsysEventmappingCollection
     * @param EmarsysMagentoEventsCollectionFactory $emarsysMagentoeventsCollection
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param EmarsysEventsCollectionFactory $emarsysEventCollection
     * @param TemplateFactory $emailTemplateModelColl
     * @param Edit $edit
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        EmarsysEventMappingCollectionFactory $emarsysEventmappingCollection,
        EmarsysMagentoEventsCollectionFactory $emarsysMagentoeventsCollection,
        ScopeConfigInterface $scopeConfigInterface,
        EmarsysEventsCollectionFactory $emarsysEventCollection,
        TemplateFactory $emailTemplateModelColl,
        Edit $edit
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->emarsysEventmappingCollection = $emarsysEventmappingCollection;
        $this->emarsysMagentoeventsCollection = $emarsysMagentoeventsCollection;
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->emarsysEventCollection = $emarsysEventCollection;
        $this->emailTemplateModel = $emailTemplateModelColl;
        $this->edit = $edit;
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $meventId = '';
        $emarsysEventId = '';
        $emarsysEvent = '';
        $magentoEvent = '';
        $TemplateName = '';
        $this->_setActiveMenu('Emarsys_Emarsys::emarsys_emarsysadminindex7');
        $mappingId = $this->getRequest()->getParam("mapping_id");
        $storeId = $this->getRequest()->getParam("store");
        $eventMapping = $this->emarsysEventmappingCollection->create()->addFieldToFilter("id", $mappingId)->getFirstItem();

        if ($eventMapping->getId()) {
            $meventId = $eventMapping->getMagentoEventId();
            $emarsysEventId = $eventMapping->getEmarsysEventId();
            $emarsysEvent = $this->emarsysEventCollection->create()
                ->addFieldToFilter("id", $emarsysEventId)
                ->getFirstItem()->getEmarsysEvent();
        }
        $result = $this->emarsysMagentoeventsCollection->create()->addFieldToFilter("id", $meventId)
            ->getFirstItem();
        if ($result && $result->getId()) {
            $magentoEvent = $result->getMagentoEvent();
            $templateId = $this->scopeConfigInterface->getValue($result->getConfigPath(), 'store', $storeId);
        }
        if (is_numeric($templateId)) {
            $result = $this->emailTemplateModel->create()->getCollection()->addFieldToFilter('template_id', $templateId)->getFirstItem();
            $TemplateName = $result->getTemplateCode();
        }

        $placeholderHints = $magentoEvent . ' - Placeholders Mapping';
        $resultPage->addBreadcrumb(__('Emarsys - Event Mapping'), __('Emarsys - Event Mapping'));
        $resultPage->getConfig()->getTitle()->prepend(__($placeholderHints));

        return $resultPage;
    }
}
