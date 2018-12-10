<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Productexport\Edit\Tab;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\App\Request\Http;
use Magento\Backend\Block\Widget\Form\Generic;
use Emarsys\Emarsys\Helper\Data as EmarsysDataHelper;

/**
 * Class Form
 * @package Emarsys\Emarsys\Block\Adminhtml\Productexport\Edit\Tab
 */
class Form extends Generic
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Http
     */
    protected $request;


    /**
     * Form constructor.
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param array $data
     * @param Http $request
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        array $data = [],
        Http $request
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->storeManager = $context->getStoreManager();
        $this->request = $request;
    }

    /**
     * Init form
     */
    protected function _prepareForm()
    {
        $websiteId = $this->getRequest()->getParam('website');
        $website = $this->storeManager->getWebsite($websiteId);

        $form = $this->_formFactory->create();
        $this->setForm($form);
        $fieldset = $form->addFieldset("support_form", ["legend" => '']);
        $values = [];
        $values['customer'] = 'Customer';
        $values['subscriber'] = 'Subscriber';

        $smartInsightEnable = $website->getConfig(EmarsysDataHelper::XPATH_SMARTINSIGHT_ENABLED);
        if ($smartInsightEnable == 1) {
            $values['order'] = 'Order';
        }

        $productExportStatus = $website->getConfig(EmarsysDataHelper::XPATH_PREDICT_ENABLE_NIGHTLY_PRODUCT_FEED);
        if ($productExportStatus == 1 || $smartInsightEnable == 1) {
            $values['product'] = 'Product';
        }

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
            'value' => $website->getConfig(EmarsysDataHelper::XPATH_PREDICT_INCLUDE_BUNDLE_PRODUCT),
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
