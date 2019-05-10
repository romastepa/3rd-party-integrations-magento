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
use Emarsys\Emarsys\Model\EmarsysmagentoeventsFactory;
use Emarsys\Emarsys\Model\EmarsyseventmappingFactory;
use Emarsys\Emarsys\Helper\Data;

/**
 * Class Placeholderjson
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event
 */
class Placeholderjson extends Action
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
     * Placeholderjson constructor.
     * @param Context $context
     * @param EventFactory $eventFactory
     * @param Event $eventResourceModel
     * @param PlaceholdersFactory $emarsysEventPlaceholderMappingFactory
     * @param EmarsysmagentoeventsFactory $emarsysMagentoEventsFactory
     * @param EmarsyseventmappingFactory $emarsysEventMappingFactory
     * @param Data $EmarsysHelper
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        EventFactory $eventFactory,
        Event $eventResourceModel,
        PlaceholdersFactory $emarsysEventPlaceholderMappingFactory,
        EmarsysmagentoeventsFactory $emarsysMagentoEventsFactory,
        EmarsyseventmappingFactory $emarsysEventMappingFactory,
        Data $EmarsysHelper,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->eventResourceModel = $eventResourceModel;
        $this->resultPageFactory = $resultPageFactory;
        $this->eventFactory = $eventFactory;
        $this->emarsysEventPlaceholderMappingFactory = $emarsysEventPlaceholderMappingFactory;
        $this->emarsysMagentoEventsFactory = $emarsysMagentoEventsFactory;
        $this->emarsysEventMappingFactory = $emarsysEventMappingFactory;
        $this->EmarsysHelper = $EmarsysHelper;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $mappingID = $this->getRequest()->getParam('mapping_id');
        $storeId = $this->getRequest()->getParam('store_id');

        $emarsysEventPlaceholderMappingColl = $this->emarsysEventPlaceholderMappingFactory->create()->getCollection();
        $emarsysEventPlaceholderMappingColl->addFieldToFilter('event_mapping_id', $mappingID);
        $emarsysEventPlaceholderMappingColl->addFieldToFilter('store_id', $storeId);

        $emarsysEventMappingColl = $this->emarsysEventMappingFactory->create()->getCollection()->addFieldToFilter('id', $mappingID)->getData();
        $magentoEventId = $emarsysEventMappingColl[0]['magento_event_id'];

        $emarsysMagentoEventsColl = $this->emarsysMagentoEventsFactory->create()->getCollection()->addFieldToFilter('id', $magentoEventId)->getData();
        $magentoEventName = $emarsysMagentoEventsColl[0]['magento_event'];

        if (!$emarsysEventPlaceholderMappingColl->getSize()) {
            $this->EmarsysHelper->insertFirstimeMappingPlaceholders($mappingID, $storeId);
        }
        $emarsysEventPlaceholderMappingColl = $this->emarsysEventPlaceholderMappingFactory->create()->getCollection();
        $emarsysEventPlaceholderMappingColl->addFieldToFilter('event_mapping_id', $mappingID);
        $emarsysEventPlaceholderMappingColl->addFieldToFilter('store_id', $storeId);

        if ($emarsysEventPlaceholderMappingColl->getSize()) {
            $dataArray = [];
            $emarsysHeaderPlaceholders = $this->EmarsysHelper->emarsysHeaderPlaceholders($mappingID, $storeId);
            $emarsysFooterPlaceholders = $this->EmarsysHelper->emarsysFooterPlaceholders($mappingID, $storeId);
            foreach ($emarsysEventPlaceholderMappingColl->getData() as $_data) {
                $dataGlobalArray[$_data['emarsys_placeholder_name']] = $_data['magento_placeholder_name'];
            }

            $dataArray['global'] = array_merge($emarsysHeaderPlaceholders, $dataGlobalArray, $emarsysFooterPlaceholders);
            if (strstr($magentoEventName, "Order") || strstr($magentoEventName, "Shipment") || strstr($magentoEventName, "Invoice") || strstr($magentoEventName, "Credit Memo") || strstr($magentoEventName, "RMA")) {
                $emarsysDefaultPlaceholders = $this->EmarsysHelper->emarsysDefaultPlaceholders();
                foreach ($emarsysDefaultPlaceholders['product_purchases'] as $key => $value) {
                    $dataProductPurchasesArray[$key] = $value;
                }
                $dataArray['product_purchases'] = $dataProductPurchasesArray;
            }
            $finalArray1 = ["external_id" => "RECIPIENT_EMAIL", "key_id" => "KEY_ID", "data" => $dataArray];
            printf("<pre>" . json_encode($finalArray1, JSON_PRETTY_PRINT) . "</pre>");
        } else {
            printf("No Placeholders Available");
        }
    }
}
