<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Config\Source;

/**
 * Class RecommendedProductOnCategory
 * @package Emarsys\Emarsys\Model\Config\Source
 */
class RecommendedProductOnCategory
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '||', 'label' => 'Please Select'],
            ['value' => 'CATEGORY||category-template', 'label' => 'Category'],
            ['value' => 'DEPARTMENT||simple-tmpl', 'label' => 'Department'],
            ['value' => 'PERSONAL||personal-template', 'label' => 'Personal'],
            ['value' => 'POPULAR||popular-template', 'label' => 'Popular'],
        ];
    }
}
