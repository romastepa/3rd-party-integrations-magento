<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Mapping;

use Magento\Backend\Block\Widget\Context;
use Magento\Email\Model\Template\Config;
use Emarsys\Emarsys\Model\ResourceModel\Order as ResourceModelOrder;
use Emarsys\Emarsys\Model\ResourceModel\Event as ResourceModelEvent;
use Emarsys\Emarsys\Model\ResourceModel\Product as ResourceModelProduct;
use Emarsys\Emarsys\Model\ResourceModel\Customer as ResourceModelCustomer;
use Emarsys\Emarsys\Model\ResourceModel\Field as ResourceModelField;
use Emarsys\Emarsys\Model\ResourceModel\Emarsyseventmapping\Collection as EmarsyseventmappingCollection;
use Emarsys\Emarsys\Model\ResourceModel\Emarsysevents\Collection as EmarsyseventsCollection;
use Emarsys\Emarsys\Model\ResourceModel\Emarsysmagentoevents\Collection as EmarsysmagentoeventsCollection;
use Magento\Email\Model\Template as EmailModelTemplate;

/**
 * Class MappingButtons
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping
 */
class MappingButtons extends \Magento\Backend\Block\Widget\Container
{
    /**
     * @var ResourceModelEvent
     */
    protected $resourceModelEvent;

    /**
     * @var ResourceModelOrder
     */
    protected $resourceModelOrder;

    /**
     * @var ResourceModelProduct
     */
    protected $resourceModelProduct;

    /**
     * @var ResourceModelCustomer
     */
    protected $resourceModelCustomer;

    /**
     * @var ResourceModelField
     */
    protected $resourceModelField;

    /**
     * @var EmarsyseventmappingCollection
     */
    protected $emarsyseventmappingCollection;

    /**
     * @var EmarsyseventsCollection
     */
    protected $emarsysEventCollection;

    /**
     * @var EmarsysmagentoeventsCollection
     */
    protected $emarsysmagentoeventsCollection;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @var EmailModelTemplate
     */
    protected $emailModelTemplate;

    /**
     * @var string
     */
    protected $_template = 'mapping/customer/view.phtml';

    /**
     * MappingButtons constructor.
     * @param Context $context
     * @param Config $edit
     * @param ResourceModelOrder $resourceModelOrder
     * @param ResourceModelEvent $resourceModelEvent
     * @param ResourceModelProduct $resourceModelProduct
     * @param ResourceModelCustomer $resourceModelCustomer
     * @param ResourceModelField $resourceModelField
     * @param EmarsyseventsCollection $emarsysEventCollection
     * @param EmarsyseventmappingCollection $emarsyseventmappingCollection
     * @param EmarsysmagentoeventsCollection $emarsysmagentoeventsCollection
     * @param EmailModelTemplate $emailModelTemplate
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $edit,
        ResourceModelOrder $resourceModelOrder,
        ResourceModelEvent $resourceModelEvent,
        ResourceModelProduct $resourceModelProduct,
        ResourceModelCustomer $resourceModelCustomer,
        ResourceModelField $resourceModelField,
        EmarsyseventsCollection $emarsysEventCollection,
        EmarsyseventmappingCollection $emarsyseventmappingCollection,
        EmarsysmagentoeventsCollection $emarsysmagentoeventsCollection,
        EmailModelTemplate $emailModelTemplate,
        $data = []
    )
    {
        $this->emailModelTemplate = $emailModelTemplate;
        $this->scopeConfigInterface = $context->getScopeConfig();
        $this->emarsysmagentoeventsCollection = $emarsysmagentoeventsCollection;
        $this->emarsysEventCollection = $emarsysEventCollection;
        $this->resourceModelOrder = $resourceModelOrder;
        $this->resourceModelEvent = $resourceModelEvent;
        $this->resourceModelProduct = $resourceModelProduct;
        $this->resourceModelCustomer = $resourceModelCustomer;
        $this->resourceModelField = $resourceModelField;
        $this->emarsyseventmappingCollection = $emarsyseventmappingCollection;
        $this->edit = $edit;
        parent::__construct($context, $data);
    }

    /**
     * @return int|mixed
     */
    public function getStoreId()
    {
        $storeId = $this->getRequest()->getParam('store');
        if (!isset($storeId)) {
            $storeId = 1;
        }
        return $storeId;
    }

    /**
     * @param $configPath
     * @return string
     */
    public function getMagentoDefaultTemplate($configPath)
    {
        $templateName = '';
        $templateValue = $this->_scopeConfig->getValue($configPath);
        $availableTemplates = $this->edit->getAvailableTemplates();
        if ($availableTemplates) {
            foreach ($availableTemplates as $template) {
                if ($template['value'] == $templateValue) {
                    $templateName = $template['label'];
                }
            }
        }
        return $templateName . " (Default Template from Locale)";
    }

    /**
     * @param $mappingItem
     * @return array
     */
    public function getMappingOption($mappingItem)
    {
        $params = ["store" => $this->getStoreId()];
        $values = [
            [
                'label' => 'Customer',
                'value' => $this->getUrl('emarsys_emarsys/mapping_customer/index', $params),
                'selected' => ($mappingItem == 'mapping_customer') ? true : false,
            ],
            [
                'label' => 'Customer-Field',
                'value' => $this->getUrl('emarsys_emarsys/mapping_field/index', $params),
                'selected' => ($mappingItem == 'mapping_field') ? true : false,
            ],
            [
                'label' => 'Product',
                'value' => $this->getUrl('emarsys_emarsys/mapping_product/index', $params),
                'selected' => ($mappingItem == 'mapping_product') ? true : false,
            ],
            [
                'label' => 'Order',
                'value' => $this->getUrl('emarsys_emarsys/mapping_order/index', $params),
                'selected' => ($mappingItem == 'mapping_order') ? true : false,
            ],
            [
                'label' => 'Event',
                'value' => $this->getUrl('emarsys_emarsys/mapping_event/index', $params),
                'selected' => ($mappingItem == 'mapping_event') ? true : false,
            ],
        ];
        return $values;
    }

    /**
     * @return string
     */
    public function getSaveButtonUrl()
    {
        return $this->getUrl("*/*/save", ["store" => $this->getStoreId()]);
    }

    /**
     * @return string
     */
    public function getCustomerRecommendedUrl()
    {
        $customerMappingCheck = $this->resourceModelCustomer->checkCustomerMapping($this->getStoreId());
        $recommdedBtnUrl = "'" . $this->getUrl("*/*/saveRecommended", ["store" => $this->getStoreId()]) . "','" . $customerMappingCheck . "'";
        return $recommdedBtnUrl;
    }

    /**
     * @return string
     */
    public function getProductRecommendedUrl()
    {
        $storeId = $this->getStoreId();
        $productMappingCheck = $this->resourceModelProduct->checkProductMapping($storeId);
        $recommdedBtn = "'" . $this->getUrl("*/*/saveRecommended", ["store" => $storeId]) . "','" . $productMappingCheck . "'";
        return $recommdedBtn;
    }

    /**
     * @return string
     */
    public function getCustomerMappingCount()
    {
        return $this->resourceModelCustomer->getEmarsysAttrCount($this->getStoreId());
    }

    /**
     * @return array
     */
    public function getProductMappingCount()
    {
        return $this->resourceModelProduct->getEmarsysAttrCount($this->getStoreId());
    }

    /**
     * @return string
     */
    public function getEmarsysOptionCount()
    {
        return $this->resourceModelField->getEmarsysOptionCount($this->getStoreId());
    }

    /**
     * @return string
     */
    public function getEventMappingCount()
    {
        return $this->resourceModelEvent->checkEventMappingCount($this->getStoreId());
    }

    /**
     * @return string
     */
    public function getMagentoEventTemplateCount()
    {
        return $this->resourceModelEvent->getMagentoEventTemplateCount($this->getStoreId());
    }

    /**
     * @return bool|\Magento\Framework\DataObject
     */
    public function getEmarsysEventMappingCollection()
    {
        $mappingId = $this->getRequest()->getParam("mapping_id");
        if ($mappingId) {
            return $this->emarsyseventmappingCollection->addFieldToFilter("id", $mappingId)->getFirstItem();
        }
        return false;
    }

    /**
     * @param $path
     * @return mixed
     */
    public function getTemplateId($path)
    {
        return $this->scopeConfigInterface->getValue($path, 'store', $this->getStoreId());
    }

    /**
     * @param $emarsysEventId
     * @return string
     */
    public function getEmarsysEvent($emarsysEventId)
    {
        $result = '';
        $emarsysEventCollection = $this->emarsysEventCollection->addFieldToFilter("id", $emarsysEventId);
        if ($emarsysEventCollection) {
            return $emarsysEventCollection->getFirstItem()->getEmarsysEvent();
        }
        return $result;
    }

    /**
     * @param $templateId
     * @return string
     */
    public function getTemplateCodeById($templateId)
    {
        $result = '';
        $collection = $this->emailModelTemplate->getCollection()->addFieldToFilter('template_id', $templateId);
        if ($collection) {
            $result = $collection->getFirstItem()->getTemplateCode();
        }
        return $result;
    }

    /**
     * @param $magentoEventId
     * @return bool
     */
    public function getEventsCollectionById($magentoEventId)
    {
        $result = false;
        $collection = $this->emarsysmagentoeventsCollection->addFieldToFilter("id", $magentoEventId);
        if ($collection) {
            $result = $collection->getFirstItem();
        }
        return $result;
    }
}