<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Config\Source;

/**
 * Class RecommendedProductOnCart
 * @package Emarsys\Emarsys\Model\Config\Source
 */
class RecommendedProductOnCart
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '||', 'label' => 'Please Select'],
            ['value' => 'CART||cart-template', 'label' => 'CART'],
            ['value' => 'RELATED||related-template', 'label' => 'Related'],
            ['value' => 'ALSO_BOUGHT||alsobought-template', 'label' => 'Also Bought'],
            ['value' => 'PERSONAL||personal-template', 'label' => 'Personal']
        ];
    }
}
