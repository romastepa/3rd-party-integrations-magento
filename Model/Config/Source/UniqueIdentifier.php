<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Config\Source;

/**
 * Class UniqueIdentifier
 * @package Emarsys\Emarsys\Model\Config\Source
 */
class UniqueIdentifier implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return []
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'product_id', 'label' => __('Product ID')],
            ['value' => 'sku', 'label' => __('SKU')]
        ];
    }
}
