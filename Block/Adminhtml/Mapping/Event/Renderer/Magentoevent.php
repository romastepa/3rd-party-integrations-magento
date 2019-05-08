<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Event\Renderer;

use Magento\Framework\DataObject;

/**
 * Class Magentoevent
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping\Event\Renderer
 */
class Magentoevent extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
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
     * Magentoevent constructor.
     * @param \Magento\Backend\Model\Session $session
     * @param \Emarsys\Emarsys\Model\ResourceModel\Customer\CollectionFactory $collectionFactory
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Emarsys\Emarsys\Model\Logs $emarsysLogs
     * @param \Emarsys\Emarsys\Model\ResourceModel\Emarsysmagentoevents $Emarsysmagentoevents
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Emarsys\Emarsys\Model\ResourceModel\Emarsysmagentoevents\CollectionFactory $CollectionFactory
     */
    public function __construct(
        \Magento\Backend\Model\Session $session,
        \Emarsys\Emarsys\Model\ResourceModel\Customer\CollectionFactory $collectionFactory,
        \Magento\Backend\Helper\Data $backendHelper,
        \Emarsys\Emarsys\Model\Logs $emarsysLogs,
        \Emarsys\Emarsys\Model\ResourceModel\Emarsysmagentoevents $Emarsysmagentoevents,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Emarsys\Emarsys\Model\ResourceModel\Emarsysmagentoevents\CollectionFactory $CollectionFactory
    ) {
        $this->session = $session;
        $this->collectionFactory = $collectionFactory;
        $this->backendHelper = $backendHelper;
        $this->emarsysLogs = $emarsysLogs;
        $this->Emarsysmagentoevents = $Emarsysmagentoevents;
        $this->_storeManager = $storeManager;
        $this->CollectionFactory = $CollectionFactory;
    }

    /**
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        try {
            $collection = $this->CollectionFactory->create()->addFilter("id", $row->getData("magento_event_id"))->getFirstItem();
            return $collection->getData("magento_event");
        } catch (\Exception $e) {
            $storeId = $this->_storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'render(Magento Events)');
        }
    }
}
