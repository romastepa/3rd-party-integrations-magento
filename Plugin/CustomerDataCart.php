<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2019 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Plugin;

use Magento\Checkout\CustomerData\Cart;
use Magento\Quote\Model\Quote\Item;
use Magento\Catalog\Model\Product;

/**
 * Class CustomerData
 * @package Emarsys\Emarsys\Plugin
 */
class CustomerDataCart
{

    /**
     * CustomerDataCart constructor.
     *
     * @param Item $item
     * @param Product $product
     */
    public function __construct(Item $item, Product $product)
    {
        $this->item = $item;
        $this->product = $product;
    }

    /**
     * @param OriginalCustomerDataCart $subject
     * @param array $result
     *
     * @return array
     */
    public function afterGetSectionData(Cart $subject, $result)
    {
        if (!empty($result['items'])) {
            foreach ($result['items'] as &$item) {
                /** @var \Magento\Quote\Model\Quote\Item $itemModel */
                $itemModel = $this->item->load($item['item_id']);
                $item['product_real_id'] = $this->product->getIdBySku($item['product_sku']);
                $item['base_product_price_value'] = round($itemModel->getBaseCalculationPrice(), 2);
            }
        }

        return $result;
    }
}
