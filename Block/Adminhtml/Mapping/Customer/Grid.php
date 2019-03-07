<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */


namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Customer;

/**
 * Class Grid
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping\Customer
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
     * @var \Emarsys\Emarsys\Model\CustomerMagentoAttsFactory
     */
    protected $customerMagentoAttsFactory;

    /**
     * Grid constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Eav\Model\Entity\Type $entityType
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @param \Magento\Framework\Data\Collection $dataCollection
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Emarsys\Emarsys\Model\ResourceModel\Customer $resourceModelCustomer
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Emarsys\Emarsys\Model\CustomerMagentoAttsFactory $customerMagentoAttsFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Eav\Model\Entity\Type $entityType,
        \Magento\Eav\Model\Entity\Attribute $attribute,
        \Magento\Framework\Data\Collection $dataCollection,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $resourceModelCustomer,
        \Magento\Framework\Module\Manager $moduleManager,
        \Emarsys\Emarsys\Model\CustomerMagentoAttsFactory $customerMagentoAttsFactory, 
        $data = []
    ) {
        $this->session               = $context->getBackendSession();
        $this->entityType            = $entityType;
        $this->attribute             = $attribute;
        $this->moduleManager         = $moduleManager;
        $this->backendHelper         = $backendHelper;
        $this->dataCollection        = $dataCollection;
        $this->dataObjectFactory     = $dataObjectFactory;
        $this->resourceModelCustomer = $resourceModelCustomer;
        $this->_storeManager         = $context->getStoreManager();
        $this->customerMagentoAttsFactory = $customerMagentoAttsFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $this->session->setData('gridData', '');
        $storeId = $this->getRequest()->getParam('store');
        if (!isset($storeId)) {
            $storeId = 1;
        }
        $collection = $this->customerMagentoAttsFactory->create()->getCollection()
            ->addFieldToFilter('store_id', ['eq' => $storeId]);
        $collection->setOrder('main_table.frontend_label', 'ASC');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {

        $this->addColumn(
            'frontend_label',
            [
                'header' => __('Magento Customer Attribute'),
                'type' => 'varchar',
                'index' => 'frontend_label',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'renderer' => 'Emarsys\Emarsys\Block\Adminhtml\Mapping\Customer\Renderer\MagentoAttribute',
            ]
        );
        $this->addColumn(
            'emarsys_contact_header',
            [
                'header' => __('Emarsys Customer Attribute'),
                'renderer' => 'Emarsys\Emarsys\Block\Adminhtml\Mapping\Customer\Renderer\EmarsysCustomer',
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
        $storeId = $this->getRequest()->getParam('store');
        if (!isset($storeId)) {
            $storeId = 1;
        }
        $this->session->setData('store', $storeId);
        $mappingExists = $this->resourceModelCustomer->customerMappingExists($storeId);
        if (!$mappingExists) {
            $customerAttData = $this->attribute->getCollection()
                ->addFieldToSelect('frontend_label')
                ->addFieldToSelect('attribute_code')
                ->addFieldToSelect('entity_type_id')
                ->addFieldToFilter('entity_type_id', ['in' => '1, 2'])
                ->getData();
            $this->resourceModelCustomer->insertCustomerMageAtts($customerAttData, $storeId);
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
