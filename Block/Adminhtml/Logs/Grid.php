<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml\Logs;

/**
 * Class Grid
 * @package Emarsys\Emarsys\Block\Adminhtml\Logs
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
     * @var \Emarsys\Emarsys\Model\ResourceModel\Customer
     */
    protected $resourceModelCustomer;

    /**
     * @var \Magento\Framework\Data\Collection
     */
    protected $dataCollection;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var \Magento\Cron\Model\ConfigInterface
     */
    protected $cronConfig;

    /**
     * @var \Magento\Framework\Dataobject
     */
    protected $dataObject;

    /**
     * @var \Emarsys\Emarsys\Block\Adminhtml\CronData
     */
    protected $cronData;

    /**
     * @var \Emarsys\Emarsys\Model\LogsFactory
     */
    protected $logsFactory;

    /**
     * Grid constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Data\Collection $dataCollection
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Emarsys\Emarsys\Model\LogsFactory $logsFactory
     * @param \Emarsys\Emarsys\Block\Adminhtml\CronData $cronData
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Cron\Model\ConfigInterface $cronConfig
     * @param \Magento\Framework\Dataobject $dataObject
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Data\Collection $dataCollection,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Emarsys\Emarsys\Model\LogsFactory $logsFactory,
        \Emarsys\Emarsys\Block\Adminhtml\CronData $cronData,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Cron\Model\ConfigInterface $cronConfig,
        \Magento\Framework\Dataobject $dataObject,
        $data = []
    ) {
        $this->session = $context->getSession();
        $this->moduleManager = $moduleManager;
        $this->backendHelper = $backendHelper;
        $this->dataCollection = $dataCollection;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->cronConfig = $cronConfig;
        $this->dataObject = $dataObject;
        $this->cronData = $cronData;
        $this->logsFactory = $logsFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('logsGrid');
        $this->setDefaultSort('frontend_label');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setVarNameFilter('frontend_label');
        $storeId = $this->getRequest()->getParam('store');
        $this->session->setData('storeId', $storeId);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $scheduleId = $this->_request->getParam('schedule_id');
        $collection = $this->logsFactory->create()->getCollection()->addFieldToFilter('log_exec_id', $scheduleId);
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn("created_at", [
            "header" => __("Creation Date"),
            "align" => "left",
            'width' => '25',
            "index" => "created_at",
            'type' => 'datetime'
        ]);

        $this->addColumn("emarsys_info", [
            "header" => __("Emarsys Info"),
            "align" => "left",
            'width' => '25',
            "index" => "emarsys_info"
        ]);

        $this->addColumn("description", [
            "header" => __("Description"),
            "align" => "left",
            'width' => '25'
        ]);

        $this->addColumn("description", [
            "header" => __("Description"),
            "align" => "left",
            'width' => '25',
            "index" => "description",
        ]);

        $this->addColumn("message_type", [
            "header" => __("Type"),
            "align" => "left",
            "index" => "message_type",
            'width' => '150',
            'renderer' => 'Emarsys\Emarsys\Block\Adminhtml\Logs\Renderer\Messagetype',
        ]);
        return parent::_prepareColumns();
    }

    /**
     * @param \Magento\Catalog\Model\Product|\Magento\Framework\DataObject $item
     * @return string|void
     */
    public function getRowUrl($item)
    {
        parent::getRowUrl($item);
    }
}
