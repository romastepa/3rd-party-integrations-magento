<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Event;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Model\Session;
use Magento\Backend\Helper\Data;
use Emarsys\Emarsys\Model\EmarsyseventmappingFactory as EmarsysEventsFactory;
use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Helper\Logs as EmarsysHelperLogs;

/**
 * Class Grid
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping\Event
 */
class Grid extends Extended
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * @var EmarsysEventsFactory
     */
    protected $emarsysEventsFactory;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var EmarsysHelperLogs
     */
    protected $logsHelper;

    /**
     * Grid constructor.
     *
     * @param EmarsysEventsFactory $emarsysEventsFactory
     * @param EmarsysHelper $emarsysHelper
     * @param ResourceConnection $resourceConnection
     * @param EmarsysHelperLogs $logsHelper
     * @param Context $context
     * @param Data $backendHelper
     * @param array $data
     */
    public function __construct(
        EmarsyseventsFactory $emarsysEventsFactory,
        EmarsysHelper $emarsysHelper,
        ResourceConnection $resourceConnection,
        EmarsysHelperLogs $logsHelper,
        Context $context,
        Data $backendHelper,
        $data = []
    ) {
        $this->session = $context->getBackendSession();
        $this->emarsysEventsFactory = $emarsysEventsFactory;
        $this->_storeManager = $context->getStoreManager();
        $this->emarsysHelper = $emarsysHelper;
        $this->resourceConnection = $resourceConnection;
        $this->logsHelper = $logsHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return Extended
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _prepareCollection()
    {
        $storeId = $this->getRequest()->getParam('store');

        $eventMappingCollection = $this->emarsysEventsFactory->create()->getCollection()->addFieldToFilter('store_id', $storeId);

        $eventMappingCollection->getSelect()->joinLeft(
            ['magento_events' => $this->resourceConnection->getTableName('emarsys_magento_events')],
            'main_table.magento_event_id = magento_events.id',
            ['magento_event']
        );
        $this->setCollection($eventMappingCollection);
        return parent::_prepareCollection();
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $storeId = $this->getRequest()->getParam('store');
        if (!isset($storeId)) {
            $storeId = $this->emarsysHelper->getFirstStoreId();
        }
        $this->session->setData('store', $storeId);
        $this->session->setMappingGridData('');
        $collection = $this->emarsysEventsFactory->create()->getCollection()->addFieldToFilter('store_id', $storeId);
        $mappingGridArray = [];
        foreach ($collection as $col) {
            $mappingGridArray[$col->getData("id")]['magento_event_id'] = $col->getData('magento_event_id');
            $mappingGridArray[$col->getData("id")]['emarsys_event_id'] = $col->getData('emarsys_event_id');
        }
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->session->setMappingGridData($mappingGridArray);
        $this->session->setStoreId($storeId);
    }

    /**
     * @return Extended
     * @throws \Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'emarsys_event',
            ['header' => __('Magento Event'), 'index' => 'magento_event']
        );

        $recommended = $this->getRequest()->getParam('recommended');

        if (isset($recommended) && $recommended != "") {
            $this->addColumn('emarsys_event_id', [
                'header' => __('Emarsys Event'),
                'renderer' => 'Emarsys\Emarsys\Block\Adminhtml\Mapping\Event\Renderer\Emarsyseventmapping',
                'filter' => false
            ]);
        } else {
            $this->addColumn('emarsys_event_id', [
                'header' => __('Emarsys Event'),
                'renderer' => 'Emarsys\Emarsys\Block\Adminhtml\Mapping\Event\Renderer\EmarsysEvent',
                'filter' => false

            ]);
        }

        return parent::_prepareColumns();
    }

    /**
     * @return string
     */
    public function getMainButtonsHtml()
    {
        return parent::getMainButtonsHtml();
    }

    /**
     * @param $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return '';
    }
}
