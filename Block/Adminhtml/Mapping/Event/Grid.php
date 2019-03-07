<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */


namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Event;

/**
 * Class Grid
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping\Event
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected $_collection;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $backendHelper;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Event
     */
    protected $resourceModelEvent;

    /**
     * @var \Magento\Framework\Data\Collection
     */
    protected $dataCollection;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var \Magento\Eav\Model\Entity\Type
     */
    protected $entityType;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute
     */
    protected $attribute;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Grid constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Eav\Model\Entity\Type $entityType
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @param \Magento\Framework\Data\Collection $dataCollection
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Emarsys\Emarsys\Model\ResourceModel\Event $resourceModelEvent
     * @param \Emarsys\Emarsys\Model\EmarsyseventmappingFactory $EmarsyseventmappingFactory
     * @param \Emarsys\Emarsys\Helper\Data $EmarsysHelper
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Eav\Model\Entity\Type $entityType,
        \Magento\Eav\Model\Entity\Attribute $attribute,
        \Magento\Framework\Data\Collection $dataCollection,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Emarsys\Emarsys\Model\ResourceModel\Event $resourceModelEvent,
        \Emarsys\Emarsys\Model\EmarsyseventmappingFactory $EmarsyseventmappingFactory,
        \Emarsys\Emarsys\Helper\Data $EmarsysHelper,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        $data = []
    ) {
        $this->session = $context->getBackendSession();
        $this->entityType = $entityType;
        $this->attribute = $attribute;
        $this->moduleManager = $moduleManager;
        $this->backendHelper = $backendHelper;
        $this->EmarsyseventmappingFactory = $EmarsyseventmappingFactory;
        $this->dataCollection = $dataCollection;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->resourceModelEvent = $resourceModelEvent;
        $this->_storeManager = $context->getStoreManager();
        $this->EmarsysHelper = $EmarsysHelper;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $storeId = $this->getRequest()->getParam('store');
        if (!isset($storeId)) {
            $storeId = $this->EmarsysHelper->getFirstStoreId();
        }
        $EventMappingCollection = $this->EmarsyseventmappingFactory->create()->getCollection()->addFieldToFilter("store_id", $storeId);
        if (!$EventMappingCollection->getSize()) {
            $this->EmarsysHelper->insertFirstime($storeId);
        }
        $EventMappingCollection = $this->EmarsyseventmappingFactory->create()->getCollection()->addFieldToFilter("store_id", $storeId);
        $tableName = $this->resourceConnection->getTableName('emarsys_magento_events');
        $EventMappingCollection->getSelect()->joinLeft(['magento_events' => $tableName], 'main_table.magento_event_id = magento_events.id', ['magento_event']);
        $this->setCollection($EventMappingCollection);
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
            $storeId = $this->EmarsysHelper->getFirstStoreId();
        }
        $this->session->setData('store', $storeId);
        $this->session->setMappingGridData('');
        $collection = $this->EmarsyseventmappingFactory->create()->getCollection()->addFieldToFilter("store_id", $storeId);
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
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {

        $recommended = '';

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
     * @param \Magento\Catalog\Model\Product|\Magento\Framework\DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return '';
    }
}
