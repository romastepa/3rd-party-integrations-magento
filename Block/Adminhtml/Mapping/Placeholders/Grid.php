<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Placeholders;

use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Eav\Model\Entity\Type;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\Data\Collection;
use Magento\Framework\DataObjectFactory;
use Emarsys\Emarsys\Model\ResourceModel\Event;
use Emarsys\Emarsys\Model\EmarsyseventmappingFactory;
use Emarsys\Emarsys\Model\PlaceholdersFactory;
use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\App\ResponseFactory;

/**
 * Class Grid
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping\Placeholders
 */
class Grid extends Extended
{
    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * @var
     */
    protected $_collection;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var Data
     */
    protected $backendHelper;

    /**
     * @var Event
     */
    protected $resourceModelEvent;

    /**
     * @var Collection
     */
    protected $dataCollection;

    /**
     * @var DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var Type
     */
    protected $entityType;

    /**
     * @var Attribute
     */
    protected $attribute;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;

    /**
     * Grid constructor.
     * @param Context $context
     * @param Data $backendHelper
     * @param Type $entityType
     * @param Attribute $attribute
     * @param Collection $dataCollection
     * @param DataObjectFactory $dataObjectFactory
     * @param Event $resourceModelEvent
     * @param EmarsyseventmappingFactory $EmarsyseventmappingFactory
     * @param PlaceholdersFactory $emarsysEventPlaceholderMappingFactory
     * @param EmarsysHelper $EmarsysHelper
     * @param ModuleManager $moduleManager
     * @param MessageManagerInterface $messageManager
     * @param ResponseFactory $responseFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        Type $entityType,
        Attribute $attribute,
        Collection $dataCollection,
        DataObjectFactory $dataObjectFactory,
        Event $resourceModelEvent,
        EmarsyseventmappingFactory $EmarsyseventmappingFactory,
        PlaceholdersFactory $emarsysEventPlaceholderMappingFactory,
        EmarsysHelper $EmarsysHelper,
        ModuleManager $moduleManager,
        MessageManagerInterface $messageManager,
        ResponseFactory $responseFactory,
        $data = []
    ) {
        $this->session = $context->getBackendSession();
        $this->entityType = $entityType;
        $this->attribute = $attribute;
        $this->moduleManager = $moduleManager;
        $this->backendHelper = $backendHelper;
        $this->EmarsyseventmappingFactory = $EmarsyseventmappingFactory;
        $this->emarsysEventPlaceholderMappingFactory = $emarsysEventPlaceholderMappingFactory;
        $this->dataCollection = $dataCollection;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->resourceModelEvent = $resourceModelEvent;
        $this->_storeManager = $context->getStoreManager();
        $this->EmarsysHelper = $EmarsysHelper;
        $this->_url = $context->getUrlBuilder();
        $this->_responseFactory = $responseFactory;
        $this->_messageManager = $messageManager;
        $this->formKey = $context->getFormKey();
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $storeId = $this->getRequest()->getParam('store');
        $mappingId = $this->getRequest()->getParam('mapping_id');
        if (!isset($storeId)) {
            $storeId = 1;
        }
        $EventMappingCollection = $this->emarsysEventPlaceholderMappingFactory->create()->getCollection()
                                    ->addFieldToFilter("store_id", $storeId)
                                    ->addFieldToFilter("event_mapping_id", $mappingId);

        $this->setCollection($EventMappingCollection);

        return parent::_prepareCollection();
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->session->setData('gridData', '');
        $mapping_id = $this->getRequest()->getParam('mapping_id');
        $storeId = $this->getRequest()->getParam('store_id');

        if (!isset($storeId)) {
            $storeId = 1;
        }

        $emarsysEventPlaceholderMappingColl = $this->emarsysEventPlaceholderMappingFactory->create()->getCollection();
        $emarsysEventPlaceholderMappingColl->addFieldToFilter('event_mapping_id', $mapping_id);
        $emarsysEventPlaceholderMappingColl->addFieldToFilter('store_id', $storeId);

        if (!$emarsysEventPlaceholderMappingColl->getSize()) {
            $val = $this->EmarsysHelper->insertFirstimeMappingPlaceholders($mapping_id, $storeId);
            if ($val == "") {
                $this->_messageManager->addErrorMessage(__("Please Assign Email Template to event"));
                $RedirectUrl = $this->_url->getUrl('emarsys_emarsys/mapping_event/index', ["store_id" => $storeId]);
                $this->_responseFactory->create()->setRedirect($RedirectUrl)->sendResponse();
            }
        }
    }

    /**
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'magento_placeholder_name',
            ['header' => __('Magento Variable'),
                'index' => 'magento_placeholder_name']
        );

        $this->addColumn(
            'emarsys_placeholder_name',
            [
                'header' => __('Emarsys Placeholder'),
                'index' => 'emarsys_placeholder_name',
                'renderer' => 'Emarsys\Emarsys\Block\Adminhtml\Mapping\Placeholders\Renderer\EmarsysPlaceholders',
                'filter' => false
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @param \Magento\Catalog\Model\Product|\Magento\Framework\DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return '';
    }

    /**
     * @return string
     */
    public function getMainButtonsHtml()
    {
        $url = $this->backendHelper->getUrl('emarsys_emarsys/mapping_event/saveplaceholdermapping');
        $form = '<form id="eventsPlaceholderForm"  method="post" action="' . $url . '">';
        $form .= '<input name="form_key" type="hidden" value="' . $this->formKey->getFormKey() . '" />';
        $form .= '<input type="hidden" id="placeholderData" name="placeholderData" value="">';
        $form .= '</form>';

        return $form;
    }
}
