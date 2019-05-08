<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Config\Source;

/**
 * Class RecommendedProductOnSearchPage
 * @package Emarsys\Emarsys\Model\Config\Source
 */
class RecommendedProductOnSearchPage
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '||', 'label' => 'Please Select'],
            ['value' => 'SEARCH||search-template', 'label' => 'Search'],
            ['value' => 'PERSONAL||personal-template', 'label' => 'Personal']
        ];
    }
}
