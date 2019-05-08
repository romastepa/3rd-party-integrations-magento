<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Template;

use Magento\Framework\App\TemplateTypesInterface;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class TransportBuilder
 * @package Emarsys\Emarsys\Model\Template
 */
class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    /**
     * @var string
     */
    public $productCollObj = '';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * TransportBuilder constructor.
     * @param StoreManagerInterface $storeManager
     * @param FactoryInterface $templateFactory
     * @param MessageInterface $message
     * @param SenderResolverInterface $senderResolver
     * @param ObjectManagerInterface $objectManager
     * @param TransportInterfaceFactory $mailTransportFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        FactoryInterface $templateFactory,
        MessageInterface $message,
        SenderResolverInterface $senderResolver,
        ObjectManagerInterface $objectManager,
        TransportInterfaceFactory $mailTransportFactory
    ) {
        $this->storeManager = $storeManager;
        parent::__construct(
            $templateFactory,
            $message,
            $senderResolver,
            $objectManager,
            $mailTransportFactory
        );
    }

    /**
     * Get mail transport
     *
     * @return $this
     */
    public function prepareMessage()
    {
        $handle = '';
        $template = $this->getTemplate();
        $types = [
            TemplateTypesInterface::TYPE_TEXT => MessageInterface::TYPE_TEXT,
            TemplateTypesInterface::TYPE_HTML => MessageInterface::TYPE_HTML,
        ];

        $body = $template->processTemplate();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->productCollObj = $objectManager->create('\Magento\Catalog\Model\Product');
        $storeId = $template->getEmailStoreId();
        /** @var \Emarsys\Emarsys\Helper\Data $dataHelper */
        $dataHelper = $objectManager->create('\Emarsys\Emarsys\Helper\Data');
        $scopeConfig = $objectManager->create('\Magento\Framework\App\Config\ScopeConfigInterface');
        $request = $objectManager->get('\Magento\Framework\App\Request\Http');

        list($magentoEventID, $configPath) = $dataHelper->getMagentoEventIdAndPath(
            $this->templateIdentifier,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (!$magentoEventID) {
            $this->message->setMessageType($types[$template->getType()])
                ->setBody($body)
                ->setSubject($template->getSubject())
                ->setEmarsysData([
                    "emarsysPlaceholders" => '',
                    "emarsysEventId" => '',
                    "store_id" => $storeId
                ]);
            return $this;
        }
        $enableOptin = $scopeConfig->getValue('opt_in/optin_enable/enable_optin', 'websites', $this->storeManager->getStore($storeId)->getWebsiteId());
        if ($enableOptin) {
            $handle = $request->getFullActionName();
        }
        $emarsysEventMappingID = $dataHelper->getEmarsysEventMappingId($magentoEventID, $storeId);
        if (!$emarsysEventMappingID) {
            $this->message->setMessageType($types[$template->getType()])
                ->setBody($body)
                ->setSubject($template->getSubject())
                ->setEmarsysData([
                    "emarsysPlaceholders" => '',
                    "emarsysEventId" => '',
                    "store_id" => $storeId
                ]);
            return $this;
        }

        $emarsysEventApiID = $dataHelper->getEmarsysEventApiId($magentoEventID, $storeId);
        if (!$emarsysEventApiID) {
            $this->message->setMessageType($types[$template->getType()])
                ->setBody($body)
                ->setSubject($template->getSubject())
                ->setEmarsysData([
                    "emarsysPlaceholders" => '',
                    "emarsysEventId" => '',
                    "store_id" => $storeId
                ]);
            return $this;
        }

        $emarsysPlaceholders = $dataHelper->getPlaceHolders($emarsysEventMappingID);
        if (!$emarsysPlaceholders) {
            $emarsysPlaceholders = $dataHelper->insertFirstimeMappingPlaceholders($emarsysEventMappingID, $storeId);
            $emarsysPlaceholders = $dataHelper->getPlaceHolders($emarsysEventMappingID);
        }

        $emarsysHeaderPlaceholders = $dataHelper->emarsysHeaderPlaceholders($emarsysEventMappingID, $storeId);
        if (!$emarsysHeaderPlaceholders) {
            $emarsysInsertFirstHeaderPlaceholders = $dataHelper->insertFirstimeHeaderMappingPlaceholders(
                $emarsysEventMappingID,
                $storeId
            );
            $emarsysHeaderPlaceholders = $dataHelper->emarsysHeaderPlaceholders($emarsysEventMappingID, $storeId);
        }

        $emarsysFooterPlaceholders = $dataHelper->emarsysFooterPlaceholders($emarsysEventMappingID, $storeId);
        if (!$emarsysFooterPlaceholders) {
            $emarsysInsertFirstFooterPlaceholders = $dataHelper->insertFirstimeFooterMappingPlaceholders(
                $emarsysEventMappingID,
                $storeId
            );
            $emarsysFooterPlaceholders = $dataHelper->emarsysFooterPlaceholders($emarsysEventMappingID, $storeId);
        }

        $processedVariables = [];
        if ($order = $template->checkOrder()) {
            foreach ($order->getAllVisibleItems() as $item) {
                $orderData[] = $this->getOrderData($item);
            }
            $processedVariables['product_purchases'] = $orderData;
        }

        foreach ($emarsysHeaderPlaceholders as $key => $value) {
            if ($key == 'css_file_css_email_css') {
                continue;
            } else {
                $processedVariables['global'][$key] = $template->getProcessedVariable($value);
            }
        }
        foreach ($emarsysPlaceholders as $key => $value) {
            $processedVariables['global'][$key] = $template->getProcessedVariable($value);
        }
        foreach ($emarsysFooterPlaceholders as $key => $value) {
            $processedVariables['global'][$key] = $template->getProcessedVariable($value);
        }
        $this->message->setMessageType($types[$template->getType()])
            ->setBody($body)
            ->setSubject($template->getSubject())
            ->setEmarsysData([
                "emarsysPlaceholders" => $processedVariables,
                "emarsysEventId" => $emarsysEventApiID,
                "store_id" => $storeId
            ]);

        return $this;
    }

    /**
     * @param $value
     * @return string
     */
    protected function _formatPrice($value = 0)
    {
        return sprintf('%01.2f', $value);
    }

    /**
     * @param $value
     * @return string
     */
    protected function _formatQty($value = 0)
    {
        return sprintf('%01.0f', $value);
    }

    /**
     * @param $item
     * @return array
     */
    public function getOrderData($item)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        try {
            $optionGlue = " - ";
            $optionSeparator = " : ";

            $unitTaxAmount = $item->getTaxAmount() / $item->getQtyOrdered();
            $order = [
                'unitary_price_exc_tax' => $this->_formatPrice($item->getPriceInclTax() - $unitTaxAmount),
                'unitary_price_inc_tax' => $this->_formatPrice($item->getPriceInclTax()),
                'unitary_tax_amount' => $this->_formatPrice($unitTaxAmount),
                'line_total_price_exc_tax' => $this->_formatPrice($item->getRowTotalInclTax() - $item->getTaxAmount()),
                'line_total_price_inc_tax' => $this->_formatPrice($item->getRowTotalInclTax()),
                'line_total_tax_amount' => $this->_formatPrice($item->getTaxAmount())
            ];
            $order['product_id'] = $item->getData('product_id');
            $order['product_type'] = $item->getData('product_type');
            $order['base_original_price'] = $this->_formatPrice($item->getData('base_original_price'));
            $order['sku'] = $item->getData('sku');
            $order['product_name'] = $item->getData('name');
            $order['product_weight'] = $item->getData('weight');
            $order['qty_ordered'] = $this->_formatQty($item->getData('qty_ordered'));
            $order['original_price'] = $this->_formatPrice($item->getData('original_price'));
            $order['price'] = $this->_formatPrice($item->getData('price'));
            $order['base_price'] = $this->_formatPrice($item->getData('base_price'));
            $order['tax_percent'] = $this->_formatPrice($item->getData('tax_percent'));
            $order['tax_amount'] = $this->_formatPrice($item->getData('tax_amount'));
            $order['discount_amount'] = $this->_formatPrice($item->getData('discount_amount'));
            $order['price_line_total'] = $this->_formatPrice($order['qty_ordered'] * $order['price']);

            $_product = $this->productCollObj->load($order['product_id']);

            $base_url = $objectManager->get('Magento\Store\Model\StoreManagerInterface')
                ->getStore($item->getData('store_id'))
                ->getBaseUrl();
            $base_url = trim($base_url, '/');
            $order['_external_image_url'] = $base_url . '/media/catalog/product' . $_product->getData('thumbnail');
            $order['_url'] = $base_url . "/" . $_product->getUrlPath();
            $order['_url_name'] = $order['product_name'];
            $order['product_description'] = $_product->getData('description');
            $order['short_description'] = $_product->getData('short_description');
            $attributes = $_product->getAttributes();
            $prodData = $_product->getData();
            foreach ($attributes as $attribute) {
                if ($attribute->getFrontendInput() != "gallery") {
                    if (!isset($prodData[$attribute->getAttributeCode()])) {
                        //do nothing
                    } else {
                        $order['attribute_' . $attribute->getAttributeCode()] = $prodData[$attribute->getAttributeCode()];
                    }
                }
            }
            $order['full_options'] = [];
            $prodOptions = $item->getProductOptions();

            if (isset($prodOptions['attributes_info'])) {
                foreach ($prodOptions['attributes_info'] as $option) {
                    $order['full_options'][] = $option['label'] . $optionSeparator . $option['value'];
                }
                $order['full_options'] = implode($optionGlue, $order['full_options']);
            }

            $order = array_filter($order);
            $order['additional_data'] = ($item->getData('additional_data') ? $item->getData('additional_data') : "");

            return $order;
        } catch (\Exception $e) {
            $emarsysLogs = $objectManager->create('\Emarsys\Emarsys\Model\Logs');
            $emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'TransportBuilder::getOrderData()'
            );
        }
    }
}
