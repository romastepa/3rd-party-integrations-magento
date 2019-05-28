<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\Template;

use Emarsys\Emarsys\Helper\Data\Proxy as EmarsysHelper;
use Magento\{
    Catalog\Helper\Image,
    Framework\App\TemplateTypesInterface,
    Framework\Mail\MessageInterface,
    Framework\Mail\TransportInterfaceFactory,
    Framework\ObjectManagerInterface,
    Framework\Mail\Template\FactoryInterface,
    Framework\Mail\Template\SenderResolverInterface,
    Store\Model\StoreManagerInterface
};

/**
 * Class TransportBuilder
 * @package Emarsys\Emarsys\Model\Template
 */
class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * TransportBuilder constructor.
     *
     * @param FactoryInterface $templateFactory
     * @param MessageInterface $message
     * @param SenderResolverInterface $senderResolver
     * @param ObjectManagerInterface $objectManager
     * @param TransportInterfaceFactory $mailTransportFactory
     * @param StoreManagerInterface $storeManager
     * @param EmarsysHelper $emarsysHelper
     * @param Image $imageHelper
     */
    public function __construct(
        FactoryInterface $templateFactory,
        MessageInterface $message,
        SenderResolverInterface $senderResolver,
        ObjectManagerInterface $objectManager,
        TransportInterfaceFactory $mailTransportFactory,
        EmarsysHelper $emarsysHelper,
        StoreManagerInterface $storeManager,
        Image $imageHelper
    ) {
        $this->emarsysHelper = $emarsysHelper;
        $this->storeManager = $storeManager;
        $this->imageHelper = $imageHelper;
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
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Json_Exception
     * @throws \Zend_Mail_Exception
     */
    public function prepareMessage()
    {
        $template = $this->getTemplate();
        $types = [
            TemplateTypesInterface::TYPE_TEXT => MessageInterface::TYPE_TEXT,
            TemplateTypesInterface::TYPE_HTML => MessageInterface::TYPE_HTML,
        ];

        $body = $template->processTemplate();

        $storeId = $template->getEmailStoreId();
        $templateIdentifier = $this->templateIdentifier;
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        list($magentoEventID, $configPath) = $this->emarsysHelper->getMagentoEventIdAndPath(
            $templateIdentifier,
            $storeScope,
            $storeId
        );

        if (!$magentoEventID) {
            $this->message->setMessageType($types[$template->getType()])
                ->setBody($body)
                ->setSubject(html_entity_decode($template->getSubject(), ENT_QUOTES))
                ->setEmarsysData([
                    "emarsysPlaceholders" => '',
                    "emarsysEventId" => '',
                    "store_id" => $storeId,
                ]);
            return $this;
        }

        $emarsysEventApiID = $this->emarsysHelper->getEmarsysEventApiId($magentoEventID, $storeId);
        if (!$emarsysEventApiID) {
            $this->message->setMessageType($types[$template->getType()])
                ->setBody($body)
                ->setSubject(html_entity_decode($template->getSubject(), ENT_QUOTES))
                ->setEmarsysData([
                    "emarsysPlaceholders" => '',
                    "emarsysEventId" => '',
                    "store_id" => $storeId,
                ]);
            return $this;
        }

        $emarsysPlaceholders = $this->emarsysHelper->getPlaceHolders($magentoEventID, $storeId);
        if (!$emarsysPlaceholders) {
            $this->emarsysHelper->insertFirstTimeMappingPlaceholders($magentoEventID, $storeId);
            $emarsysPlaceholders = $this->emarsysHelper->getPlaceHolders($magentoEventID, $storeId);
        }

        $emarsysHeaderPlaceholders = $this->emarsysHelper->emarsysHeaderPlaceholders($magentoEventID, $storeId);
        if (!$emarsysHeaderPlaceholders) {
            $this->emarsysHelper->insertFirstTimeHeaderMappingPlaceholders(
                $magentoEventID,
                $storeId
            );
            $emarsysHeaderPlaceholders = $this->emarsysHelper->emarsysHeaderPlaceholders($magentoEventID, $storeId);
        }

        $emarsysFooterPlaceholders = $this->emarsysHelper->emarsysFooterPlaceholders($magentoEventID, $storeId);
        if (!$emarsysFooterPlaceholders) {
            $this->emarsysHelper->insertFirstTimeFooterMappingPlaceholders(
                $magentoEventID,
                $storeId
            );
            $emarsysFooterPlaceholders = $this->emarsysHelper->emarsysFooterPlaceholders($magentoEventID, $storeId);
        }

        $processedVariables = [];

        /** @var \Magento\Sales\Model\Order $order */
        if (method_exists($template, 'checkOrder') && ($order = $template->checkOrder())) {
            $orderData = [];
            foreach ($order->getAllVisibleItems() as $item) {
                $orderData[] = $this->getOrderData($item);
            }
            $processedVariables['product_purchases'] = $orderData;
        }

        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        if (method_exists($template, 'checkShipment') && ($shipment = $template->checkShipment())) {
            $shipmentData = [];
            /** @var \Magento\Sales\Model\Order $rmaOrder */
            $shipmentOrder = $shipment->getOrder();
            /** @var \Magento\Sales\Model\Order\Shipment\Item $item */
            foreach ($shipment->getItems() as $item) {
                $shipmentOrderItem = $this->getOrderData($shipmentOrder->getItemById($item->getOrderItemId()));
                $shipmentItem = $this->getShipmentData($item);
                $shipmentItem['order_item'] = $shipmentOrderItem;
                $shipmentData[] = $shipmentItem;
            }
            $processedVariables['shipment_items'] = $shipmentData;

            $processedVariables['shipment_tracks'] = $this->getShipmentTracks($shipment->getTracks());
        }

        /** @var \Magento\Sales\Model\Order\Invoice $invoce */
        if (method_exists($template, 'checkInvoice') && ($invoice = $template->checkInvoice())) {
            $invoiceData = [];
            /** @var \Magento\Sales\Model\Order $rmaOrder */
            $invoiceOrder = $invoice->getOrder();
            /** @var \Magento\Sales\Model\Order\Invoice\Item $item */
            foreach ($invoice->getItems() as $item) {
                $invoiceOrderItem = $this->getOrderData($invoiceOrder->getItemById($item->getOrderItemId()));
                $invoiceItem = $this->getInvoiceData($item);
                $invoiceItem['order_item'] = $invoiceOrderItem;
                $invoiceData[] = $invoiceItem;
            }
            $processedVariables['invoice_items'] = $invoiceData;
        }

        /** @var \Magento\Rma\Model\Rma $rma */
        if (method_exists($template, 'checkRma') && ($rma = $template->checkRma())) {
            $returnItems = [];
            /** @var \Magento\Sales\Model\Order $rmaOrder */
            $rmaOrder = $rma->getOrder();
            /** @var \Magento\Rma\Model\Item $item */
            foreach ($rma->getItemsForDisplay() as $item) {
                $rmaOrderItem = $this->getOrderData($rmaOrder->getItemById($item->getOrderItemId()));
                $rmaItem = $this->getRmaData($item);
                $rmaItem['order_item'] = $rmaOrderItem;
                $returnItems[] = $rmaItem;
            }
            $processedVariables['returned_items'] = $returnItems;
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
            ->setSubject(html_entity_decode($template->getSubject(), ENT_QUOTES))
            ->setEmarsysData([
                "emarsysPlaceholders" => $processedVariables,
                "emarsysEventId" => $emarsysEventApiID,
                "store_id" => $storeId,
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOrderData($item)
    {
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
                'line_total_tax_amount' => $this->_formatPrice($item->getTaxAmount()),
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

            $_product = $item->getProduct();
            $_product->setStoreId($item->getStoreId());

            $base_url = $this->storeManager->getStore($item->getStoreId())->getBaseUrl();
            $base_url = trim($base_url, '/');

            /** @var \Magento\Catalog\Helper\Image $helper */
            try {
                $url = $this->imageHelper
                    ->init($_product, 'product_base_image')
                    ->setImageFile($_product->getImage())
                    ->getUrl();
            } catch (\Exception $e) {
                $url = '';
            }

            $order['_external_image_url'] = $url;

            $order['_url'] = $base_url . "/" . $_product->getUrlPath();
            $order['_url_name'] = $order['product_name'];
            $order['product_description'] = $_product->getData('description');
            $order['short_description'] = $_product->getData('short_description');

            $attributes = $_product->getAttributes();
            $prodData = $_product->getData();
            foreach ($attributes as $attribute) {
                if ($attribute->getFrontendInput() != "gallery" && $attribute->getFrontendInput() != 'price') {
                    if (isset($prodData[$attribute->getAttributeCode()])) {
                        if (!is_array($prodData[$attribute->getAttributeCode()])
                            && ($attributeText = $_product->getAttributeText($attribute->getAttributeCode()))
                        ) {
                            $text = is_object($attributeText) ? $attributeText->getText() : $attributeText;
                        } else {
                            $text = $prodData[$attribute->getAttributeCode()];
                        }
                        $order['attribute_' . $attribute->getAttributeCode()] = $text;
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
            $this->emarsysHelper->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'TransportBuilder::getOrderData()'
            );
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment\Item $item
     * @return array
     */
    public function getShipmentData($item)
    {
        $item = $item->getData();
        unset($item['order'], $item['source']);

        return $item;
    }

    /**
     * @param array
     * @return array
     */
    public function getShipmentTracks($tracks)
    {
        $tracksInfo = [];

        foreach ($tracks as $track) {
            $tracksInfo[] = $track->getData();
        }

        return $tracksInfo;
    }

    /**
     * @param \Magento\Sales\Model\Order\Invoice\Item $item
     * @return array
     */
    public function getInvoiceData($item)
    {
        $item = $item->getData();
        unset($item['invoice'], $item['order'], $item['source']);

        return $item;
    }


    /**
     * @param \Magento\Rma\Model\Item $item
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Json_Exception
     */
    public function getRmaData($item)
    {
        $returnItem = [];
        try {
            /** @var \Magento\Rma\Model\Item\Attribute $attribute */
            foreach ($item->getAttributes() as $attribute) {
                if (!is_null($item->getData($attribute->getAttributeCode()))) {
                    if ($attributeText = $attribute->getFrontend()->getValue($item)) {
                        $returnItem[$attribute->getAttributeCode()] = is_object($attributeText) ? $attributeText->getText() : $attributeText;
                    } else {
                        $returnItem[$attribute->getAttributeCode()] = $item->getData($attribute->getAttributeCode());
                    }
                }
            }
            if ($item->getProductOptions() && !empty($item->getProductOptions())) {
                $productOptions = \Zend_Json::decode($item->getProductOptions());
                if ($productOptions) {
                    $returnItem['product_options'] = $productOptions;
                }
            }
        } catch (\Exception $e) {
            $this->emarsysHelper->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'TransportBuilder::getRmaData()'
            );
        }

        return $returnItem;
    }
}
