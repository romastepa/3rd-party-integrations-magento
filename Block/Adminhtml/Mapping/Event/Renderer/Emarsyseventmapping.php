<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Event\Renderer;

use Magento\Framework\DataObject;

/**
 * Class Emarsyseventmapping
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping\Event\Renderer
 */
class Emarsyseventmapping extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Customer\Collection
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $backendHelper;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Sync
     */
    protected $syncResourceModel;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Event
     */
    protected $resourceModelEvent;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Emarsyseventmapping constructor.
     * @param \Magento\Backend\Model\Session $session
     * @param \Emarsys\Emarsys\Model\ResourceModel\Customer\CollectionFactory $collectionFactory
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Emarsys\Emarsys\Model\ResourceModel\Event $resourceModelEvent
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param \Emarsys\Emarsys\Helper\Data $EmarsysHelper
     * @param \Emarsys\Emarsys\Model\ResourceModel\Emarsysmagentoevents\CollectionFactory $magentoEventCollection
     * @param \Emarsys\Emarsys\Model\ResourceModel\Emarsysevents\CollectionFactory $EmarsyseventCollection
     */
    public function __construct(
        \Magento\Backend\Model\Session $session,
        \Emarsys\Emarsys\Model\ResourceModel\Customer\CollectionFactory $collectionFactory,
        \Magento\Backend\Helper\Data $backendHelper,
        \Emarsys\Emarsys\Model\ResourceModel\Event $resourceModelEvent,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlInterface,
        \Emarsys\Emarsys\Helper\Data $EmarsysHelper,
        \Emarsys\Emarsys\Model\ResourceModel\Emarsysmagentoevents\CollectionFactory $magentoEventCollection,
        \Emarsys\Emarsys\Model\ResourceModel\Emarsysevents\CollectionFactory $EmarsyseventCollection
    ) {
        $this->session = $session;
        $this->collectionFactory = $collectionFactory;
        $this->backendHelper = $backendHelper;
        $this->resourceModelEvent = $resourceModelEvent;
        $this->_storeManager = $storeManager;
        $this->EmarsyseventCollection = $EmarsyseventCollection;
        $this->EmarsysHelper = $EmarsysHelper;
        $this->_urlInterface = $urlInterface;
        $this->magentoEventCollection = $magentoEventCollection;
    }

    /**
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        $magentoEventName = $this->magentoEventCollection->create()->addFieldToFilter("id", $row->getData("magento_event_id"))->getFirstItem()->getData('magento_event');
        $emarsysEventname = trim(str_replace(" ", "_", strtolower($magentoEventName)));
        $session = $this->session;
        $storeId = $session->getStoreId();
        $gridSessionData = $session->getMappingGridData();
        $params = ['mapping_id' => $row->getId(), 'store_id' => $storeId];
        $url = $this->_urlInterface->getUrl('*/*/changeValue');
        $params = ['mapping_id' => $row->getData('id'), 'store_id' => $storeId];
        $placeHolderUrl = $this->backendHelper->getUrl("adminhtml/suite2email_placeholders/index", $params);
        $jsonRequestUrl = $this->backendHelper->getUrl("adminhtml/suite2email_placeholders/jsonrequest", $params);
        $emarsysEvents = $this->EmarsyseventCollection->create();
        $ronly = '';
        $dbEvents = [];
        foreach ($emarsysEvents as $emarsysEvent) {
            $dbEvents[] = $emarsysEvent->getId();
        }
        $buttonClass = '';
        if ($this->EmarsysHelper->isReadonlyMagentoEventId($row->getData('magento_event_id'))) {
            $ronly .= ' disabled = disabled';
            $buttonClass = ' disabled';
        }
        $html = '<select ' . $ronly . ' name="directions"  style="width:200px;" onchange="changeEmarsysValue(\'' . $url . '\',this.value, \'' . $row->getData('magento_event_id') . '\', \'' . $row->getData('id') . '\')";>
			<option value="0">Please Select</option>';
        foreach ($emarsysEvents as $emarsysEvent) {
            $sel = '';
            $id = $row->getId();
            $magento_event_id = $row->getMagentoEventId();
            $gridSessionData[$id]['magento_event_id'] = $magento_event_id;

            if ($row->getEmasysEventId() == $emarsysEvent->getId()) {
                $sel .= 'selected = selected';
                $gridSessionData[$id]['emarsys_event_id'] = $emarsysEvent->getId();
            } elseif (($emarsysEventname == $emarsysEvent->getEmarsysEvent()) && ($row->getEmarsysEventId() == 0)) {
                $sel .= 'selected = selected';
                $gridSessionData[$id]['emarsys_event_id'] = $emarsysEvent->getId();
            } elseif (($emarsysEventname == $emarsysEvent->getEmarsysEvent()) && ($row->getEmarsysEventId() != 0) && !in_array($row->getEmarsysEventId(), $dbEvents)) {
                $sel .= 'selected = selected';
                $gridSessionData[$id]['emarsys_event_id'] = $emarsysEvent->getId();
            }
            if ($row->getEmarsysEventId() == $emarsysEvent->getId()) {
                $sel .= 'selected = selected';
            }
            $html .= '<option ' . $sel . ' value="' . $emarsysEvent->getId() . '">' . $emarsysEvent->getEmarsysEvent() . '</option>';
        }
        $html .= '</select>';
        $session->setStoreId($storeId);
        $session->setMappingGridData($gridSessionData);
        return $html;
    }
}
