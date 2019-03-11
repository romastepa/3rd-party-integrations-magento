<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Orderexport\Edit\Tab;

use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Form
 * @package Emarsys\Emarsys\Block\Adminhtml\Orderexport\Edit\Tab
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\ImportExport\Model\Source\Export\FormatFactory
     */
    protected $_formatFactory;

    /**
     * Form constructor.
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Backend\Model\Auth\Session $session
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\ImportExport\Model\Source\Export\FormatFactory $formatFactory
     * @param \Magento\Framework\App\Request\Http $request
     * @param Extended $extended
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Backend\Model\Auth\Session $session,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Registry $registry,
        \Magento\ImportExport\Model\Source\Export\FormatFactory $formatFactory,
        \Magento\Framework\App\Request\Http $request,
        Extended $extended,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->session = $session;
        $this->admin = $context->getBackendSession();
        $this->storeManager = $context->getStoreManager();
        $this->_formFactory = $formFactory;
        $this->_registry = $registry;
        $this->extended = $extended;
        $this->getRequest = $request;
        $this->scopeConfigInterface = $context->getScopeConfig();
        $this->_formatFactory = $formatFactory;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
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
        $smartInsightEnable = $this->scopeConfigInterface->getValue(
            'smart_insight/smart_insight/smartinsight_enabled',
            $scope,
            $websiteId
        );
        if ($smartInsightEnable == 1) {
            $values['order'] = 'Order';
        }
        $productExportStatus = $this->scopeConfigInterface->getValue(
            'emarsys_predict/feed_export/enable_nightly_product_feed',
            $scope,
            $websiteId
        );
        if ($productExportStatus == 1 || $smartInsightEnable == 1) {
            $values['product'] = 'Product';
        }
        $fieldset->addField("entitytype", "select", [
            'label' => 'Export Entity Type',
            'name' => 'entity_type',
            'values' => $values,
            'value' => 'order',
            'onchange' => "bulkExport(this.value)",
            'style' => 'width:200px',
        ]);

        $fieldset->addField("fromdate", "date", [
            'label' => 'From',
            'name' => 'from_date',
            'style' => 'width:200px',
            'date_format' => $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT),
            'time_format' => $this->_localeDate->getTimeFormat(\IntlDateFormatter::SHORT),
        ]);

        $fieldset->addField("todate", "date", [
            'label' => 'To',
            'name' => 'to_date',
            'style' => 'width:200px',
            'date_format' => $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT),
            'time_format' => $this->_localeDate->getTimeFormat(\IntlDateFormatter::SHORT),
        ]);

        return parent::_prepareForm();
    }
}
