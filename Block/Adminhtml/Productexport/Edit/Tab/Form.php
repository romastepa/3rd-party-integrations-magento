<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Productexport\Edit\Tab;

use Magento\Backend\Block\Widget\Grid\Extended;
use \Magento\Backend\Block\Widget\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Data\FormFactory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Category;
use Magento\Framework\Registry;
use Magento\ImportExport\Model\Source\Export\FormatFactory;
use Magento\Framework\App\Request\Http;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Form
 * @package Emarsys\Emarsys\Block\Adminhtml\Productexport\Edit\Tab
 */
class Form extends Generic
{
    /**
     * @var \Magento\ImportExport\Model\Source\Export\FormatFactory
     */
    protected $_formatFactory;

    /**
     * Form constructor.
     * @param Context $context
     * @param Session $session
     * @param FormFactory $formFactory
     * @param CategoryFactory $categoryFactory
     * @param Category $categoryModel
     * @param Registry $registry
     * @param FormatFactory $formatFactory
     * @param Http $request
     * @param Extended $extended
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $session,
        FormFactory $formFactory,
        CategoryFactory $categoryFactory,
        Category $categoryModel,
        Registry $registry,
        FormatFactory $formatFactory,
        Http $request,
        Extended $extended,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->session = $session;
        $this->admin = $context->getBackendSession();
        $this->storeManager = $context->getStoreManager();
        $this->_formFactory = $formFactory;
        $this->categoryFactory = $categoryFactory;
        $this->categoryModel = $categoryModel;
        $this->_registry = $registry;
        $this->extended = $extended;
        $this->getRequest = $request;
        $this->scopeConfigInterface = $context->getScopeConfig();
        $this->_formatFactory = $formatFactory;
    }

    /**
     * Init form
     */
    protected function _prepareForm()
    {
        $params = $this->getRequest->getParams();
        $scope = ScopeInterface::SCOPE_WEBSITES;
        $storeId = $params['store'];
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();

        $form = $this->_formFactory->create();
        $this->setForm($form);
        $fieldset = $form->addFieldset("support_form", ["legend" => '']);
        $values = [];
        $values['customer'] = 'Customer';
        $values['subscriber'] = 'Subscriber';
        $smartInsightEnable = $this->scopeConfigInterface->getValue('smart_insight/smart_insight/smartinsight_enabled', $scope, $websiteId);
        if ($smartInsightEnable == 1) {
            $values['order'] = 'Order';
        }
        $productExportStatus = $this->scopeConfigInterface->getValue('emarsys_predict/feed_export/enable_nightly_product_feed', $scope, $websiteId);
        if ($productExportStatus == 1 || $smartInsightEnable == 1) {
            $values['product'] = 'Product';
        }
        $includeBundleProductValue = $this->scopeConfigInterface->getValue('emarsys_predict/feed_export/include_bundle_product', $scope, $websiteId);

        $fieldset->addField("entitytype", "select", [
            'label' => 'Export Entity Type',
            'name' => 'entity_type',
            'values' => $values,
            'style' => 'width:350px',
            'value' => 'product',
            'onchange' => "bulkExport(this.value)",
        ]);

        $fieldset->addField("include_bundle", "select", [
            'label' => 'Include Bundle Product',
            'name' => 'include_bundle',
            'values' => [
                '0' => 'No',
                '1' => 'Yes',
            ],
            'value' => $includeBundleProductValue,
            'style' => 'width:350px'
        ]);

        $fieldset->addType(
            'text',
            '\Emarsys\Emarsys\Block\Adminhtml\Customformfield\Edit\Renderer\CustomRenderer'
        );

        $fieldset->addField(
            'text',
            'text',
            [
                'name' => 'text',
                'label' => __('Exclude Categories'),
                'title' => __('Exclude Categories'),
            ]
        );

        return parent::_prepareForm();
    }
}
