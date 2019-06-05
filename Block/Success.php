<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2019 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block;

use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
use Magento\{
    Checkout\Model\Session,
    Customer\Model\Session as CustomerSession,
    Framework\View\Element\Template,
    Framework\View\Element\Template\Context,
    Sales\Model\Order,
    Sales\Model\OrderFactory,
    Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory
};

/**
 * Class Success
 * @package Emartech\Emarsys\Block
 */
class Success extends Template
{
    /**
     * @var Session 
     */
    protected $checkoutSession;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OrderItemCollectionFactory
     */
    protected $orderItemCollectionFactory;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * Success constructor.
     *
     * @param Session $checkoutSession
     * @param OrderFactory $orderFactory
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     * @param CustomerSession $customerSession
     * @param EmarsysHelper $emarsysHelper
     * @param Context $context
     */
    public function __construct(
        Session $checkoutSession,
        OrderFactory $orderFactory,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        CustomerSession $customerSession,
        EmarsysHelper $emarsysHelper,
        Context $context
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->customerSession = $customerSession;
        $this->emarsysHelper = $emarsysHelper;
        parent::__construct($context);
    }

    /**
     * @return Order | false
     */
    protected function getOrder($orderId)
    {
        return $this->orderFactory->create()->load($orderId);
    }

    protected function getLineItems($orderId)
    {
        $items = [];
        $order = $this->getOrder($orderId);
        $taxIncluded = $this->emarsysHelper->isIncludeTax($order->getStoreId());
        $useBaseCurrency = $this->emarsysHelper->isUseBaseCurrency($order->getStoreId());

        foreach ($order->getAllVisibleItems() as $item) {
            $qty = $item->getQtyOrdered();
            $product = $this->getLoadProduct($item->getProductId());

            if (($item->getProductType() == \Magento\Bundle\Model\Product\Type::TYPE_CODE)
                && !$product->getPriceType()
            ) {
                $collection = $this->orderItemCollectionFactory->create()
                    ->addAttributeToFilter('parent_item_id', ['eq' => $item['item_id']])
                    ->load();
                $bundleBaseDiscount = 0;
                $bundleDiscount = 0;
                foreach ($collection as $collPrice) {
                    $bundleBaseDiscount += $collPrice['base_discount_amount'];
                    $bundleDiscount += $collPrice['discount_amount'];
                }
                if ($taxIncluded) {
                    $price = $useBaseCurrency
                        ? ($item->getBaseRowTotalInclTax() - $bundleBaseDiscount)
                        : ($item->getRowTotalInclTax() - $bundleDiscount)
                    ;
                } else {
                    $price = $useBaseCurrency
                        ? ($item->getBaseRowTotal() - $bundleBaseDiscount)
                        : $item->getRowTotal() - $bundleDiscount
                    ;
                }
            } else {
                if ($taxIncluded) {
                    $price = $useBaseCurrency
                        ? ($item->getBaseRowTotalInclTax() - $item->getBaseDiscountAmount())
                        : ($item->getRowTotalInclTax() - $item->getDiscountAmount())
                    ;
                } else {
                    $price = $useBaseCurrency
                        ? ($item->getBaseRowTotal() - $item->getBaseDiscountAmount())
                        : ($item->getRowTotal() - $item->getDiscountAmount())
                    ;
                }
            }

            $uniqueIdentifier = $this->emarsysHelper->getUniqueIdentifier();
            if ($uniqueIdentifier == "product_id") {
                $sku = $item->getProductId();
            } else {
                $sku = $item->getSku();
            }
            $items[] = [
                'item' => addslashes($sku),
                'price' => $price,
                'quantity' => $qty
            ];
        }

        return [
            'orderId' => $order->getIncrementId(),
            'items' => $items,
            'email' => $order->getCustomerEmail(),
        ];
    }

    public function getOrderData()
    {
        $orderIds = $this->customerSession->getWebExtendNewOrderIds();

        $orderData = [];
        if (!empty($orderIds)) {
            foreach ($orderIds as $orderId) {
                $orderData[] = $this->getLineItems($orderId);
            }
        }

        if (!empty($orderData)) {
            $this->customerSession->setWebExtendNewOrderIds([]);
        }

        return \Zend_Json::encode($orderData);
    }
}
