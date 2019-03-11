<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Config\Source;

/**
 * Class RecommendedProductOnPages
 * @package Emarsys\Emarsys\Model\Config\Source
 */
class RecommendedProductOnPages
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => 'Please Select'],
            ['value' => 'cms_index_index', 'label' => 'Home'],
            ['value' => 'catalog_category_view', 'label' => 'Department'],
            ['value' => 'catalog_category_layered', 'label' => 'Category'],
            ['value' => 'catalog_product_view', 'label' => 'Related'],
            ['value' => 'checkout_cart_index', 'label' => 'Cart'],
            ['value' => 'catalogsearch_result_index', 'label' => 'Search'],
            ['value' => 'catalog_product_view', 'label' => 'Also Brought'],
            ['value' => 'cms_index_index', 'label' => 'Personal'],
            ['value' => 'checkout_onepage_success', 'label' => 'Purchase'],
        ];
    }
}
