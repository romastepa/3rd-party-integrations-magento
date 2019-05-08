<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Event\Renderer;

use Magento\Framework\DataObject;
use Magento\Backend\Model\Session;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
use Emarsys\Emarsys\Model\ResourceModel\Emarsysevents\CollectionFactory as EmarsysEventsCollectionFactory;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;

/**
 * Class EmarsysEvent
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping\Event\Renderer
 */
class EmarsysEvent extends AbstractRenderer
{
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var
     */
    protected $collectionFactory;

    /**
     * @var BackendHelper
     */
    protected $backendHelper;


    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * @var EmarsysEventsCollectionFactory
     */
    protected $emarsysEventCollection;

    /**
     * EmarsysEvent constructor.
     * @param Session $session
     * @param BackendHelper $backendHelper
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlInterface
     * @param EmarsysHelper $EmarsysHelper
     * @param EmarsysEventsCollectionFactory $EmarsyseventCollection
     */
    public function __construct(
        Session $session,
        BackendHelper $backendHelper,
        StoreManagerInterface $storeManager,
        UrlInterface $urlInterface,
        EmarsysHelper $EmarsysHelper,
        EmarsysEventsCollectionFactory $EmarsyseventCollection
    ) {
        $this->session = $session;
        $this->backendHelper = $backendHelper;
        $this->_storeManager = $storeManager;
        $this->emarsysEventCollection = $EmarsyseventCollection;
        $this->emarsysHelper = $EmarsysHelper;
        $this->_urlInterface = $urlInterface;
    }

    /**
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        $storeId = $this->session->getData('store');
        $url = $this->_urlInterface->getUrl('*/*/changeValue');
        $row->getData('id');
        $params = ['mapping_id' => $row->getData('id'), 'store' => $storeId];
        $placeHolderUrl = $this->backendHelper->getUrl("*/*/placeholders", $params);
        $placeholderJsonRequestUrl = $this->backendHelper->getUrl(
            "*/*/placeholderjson",
            [
                "mapping_id" => $row->getData('id'),
                "store_id" => $storeId
            ]
        );
        $emarsysEvents = $this->emarsysEventCollection->create()->addFieldToFilter('store_id', ['eq' => $storeId]);
        $ronly = '';
        $buttonClass = '';

        if ($this->emarsysHelper->isReadonlyMagentoEventId($row->getData('magento_event_id'))) {
            $ronly .= ' disabled = disabled';
            $buttonClass = ' disabled';
        }

        $html = '<select class="admin__control-select mapping-select" ' . $ronly .
            ' name="directions"  style="width:200px;" onchange="changeEmarsysValue(\'' . $url .
            '\',this.value, \'' . $row->getData('magento_event_id') . '\', \'' . $row->getData('id') . '\')";>
			<option value="0">Please Select</option>';

        foreach ($emarsysEvents as $emarsysEvent) {
            $sel = '';
            if ($row->getData("emarsys_event_id") == $emarsysEvent->getId()) {
                $sel .= 'selected = selected';
            }
            $html .= '<option ' . $sel . ' value="' . $emarsysEvent->getId() . '">' . $emarsysEvent->getEmarsysEvent() . '</option>';
        }

        $html .= '</select>';
        $html .= '&nbsp;&nbsp;&nbsp;<button ' . $buttonClass . ' type="button" class="scalable task form-button ' .
            $buttonClass . '" name="json" id="json"  onclick="openMyPopup(\'' . $placeholderJsonRequestUrl .
            '\');" >JSON Request</button>';
        $html .= '&nbsp;&nbsp;<button class="scalable task form-button" name="placeholders" id="placeholders"  onclick="placeholderRedirect(\'' .
            $placeHolderUrl . '\');" >Placeholders</button>';

        return $html;
    }
}
