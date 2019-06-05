<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Order;

/**
 * Class Grid
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping\Order
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
     * @var \Emarsys\Emarsys\Model\ResourceModel\Order
     */
    protected $resourceModelOrder;

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
     * @var \Emarsys\Emarsys\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Emarsys\Emarsys\Helper\Data
     */
    protected $emarsysDataHelper;

    /**
     * Grid constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Eav\Model\Entity\Type $entityType
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @param \Magento\Framework\Data\Collection $dataCollection
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Emarsys\Emarsys\Model\ResourceModel\Order $resourceModelOrder
     * @param \Emarsys\Emarsys\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Emarsys\Emarsys\Helper\Data $emarsysDataHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Eav\Model\Entity\Type $entityType,
        \Magento\Eav\Model\Entity\Attribute $attribute,
        \Magento\Framework\Data\Collection $dataCollection,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Emarsys\Emarsys\Model\ResourceModel\Order $resourceModelOrder,
        \Emarsys\Emarsys\Model\OrderFactory $orderFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Emarsys\Emarsys\Helper\Data $emarsysDataHelper,
        $data = []
    ) {
        $this->session = $context->getBackendSession();
        $this->entityType = $entityType;
        $this->attribute = $attribute;
        $this->moduleManager = $moduleManager;
        $this->backendHelper = $backendHelper;
        $this->dataCollection = $dataCollection;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->resourceModelOrder = $resourceModelOrder;
        $this->_storeManager = $context->getStoreManager();
        $this->orderFactory = $orderFactory;
        $this->emarsysDataHelper = $emarsysDataHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $storeId = $this->getRequest()->getParam('store', 1);
        $customCollection = $this->orderFactory->create()->getCollection()
            ->addFilter('store_id', $storeId)
            ->setOrder('magento_column_name', 'ASC');
        $this->setCollection($customCollection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'magento_column_name',
            [
                'header' => __('Magento Column Name'),
                'index' => 'magento_column_name'
            ]
        );
        $this->addColumn('entity_type_id', [
            'header' => __('Magento Entity Type Id'),
            'align' => 'left',
            'index' => 'entity_type_id',
            'column_css_class' => 'no-display',
            'header_css_class' => 'no-display',
        ]);
        $this->addColumn(
            'emarsys_order_field',
            [
                'header' => __('Emarsys Order Attribute'),
                'renderer' => 'Emarsys\Emarsys\Block\Adminhtml\Mapping\Order\Renderer\EmarsysOrderField',
                'filter' => false
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->session->setData('gridData', '');
        $storeId = $this->getRequest()->getParam('store', 1);
        $this->session->setData('store', $storeId);
        $mappingExists = $this->resourceModelOrder->orderMappingExists($storeId);
        if (empty($mappingExists)) {
            $header = $this->emarsysDataHelper->getSalesOrderCsvDefaultHeader($storeId);
            foreach ($header as $column) {
                $manData[$column] = $column;
            }

            $this->resourceModelOrder->insertIntoMappingTableStaticData($manData, $storeId);
            $data = $this->resourceModelOrder->getSalesOrderColumnNames();
            $this->resourceModelOrder->insertIntoMappingTable($data, $storeId);
        }
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
