<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Schedular
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml;

/**
 * Class Cron
 * @package Emarsys\Emarsys\Block\Adminhtml
 */
class Cron extends \Magento\Backend\Block\Widget\Grid\Extended
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
     * @var CronData
     */
    protected $cronData;

    /**
     * Cron constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Data\Collection $dataCollection
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param CronData $cronData
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
        $this->cronData  = $cronData;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('LogsGrid');
        $this->setDefaultSort('frontend_label');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('frontend_label');
        $storeId = $this->getRequest()->getParam('store');
        $this->session->setData('storeId', $storeId);
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function _prepareCollection()
    {
        $count = 1;
        $rows = $this->cronData->getCronData();
        $collection = $this->dataCollection;
        foreach ($rows as $row) {
            foreach ($row as $key => $datahere) {
                $datahere['rowid'] = $count;
                /*condition to filter only emarsys starts here, to get all the crons in this mageto remove the below if condition*/
                if (isset($datahere['name'])) {
                    $emarsysexp = explode("_", $datahere['name']);
                }
                if ($emarsysexp[0]!='emarsys') {
                    continue;
                }

                /* filter code ends here */

                $rowObj = $this->dataObjectFactory->create();// use this create method to prevent getting only the last data in the grid.
                $rowObj->setData($datahere)->toJson();
                $collection->addItem($rowObj);
                $collection->loadData();
                $count++;
            }
        }
        $storeId = $this->getRequest()->getParam('store');
        if ($storeId == 0) {
            $storeId = 1;
        }
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn("name", [
            "header" => __("Code"),
            "align" => "left",
            'width' => '25',
            "index" => "name",
        ]);

        $this->addColumn("method", [
            "header" => __("Method"),
            "align" => "center",
            "index" => "method",
            'width' => '150'

        ]);

        $this->addColumn("schedule", [
            "header" =>__("Schedule"),
            "align"  => "left",
            "index"  => "schedule",
            'width'  => '150'

        ]);

        $this->addColumn("rowid", [
            "header" =>__("Action"),
            "align"  => "left",
            "index"  => "rowid",
            'renderer' => 'Emarsys\Emarsys\Block\Adminhtml\Cron\Renderer\Messagetype',
            'width'  => '150'
        ]);
        return parent::_prepareColumns();
    }
}
