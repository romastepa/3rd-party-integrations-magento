<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Config\Source;

/**
 * Class RecommendedProductOnOrderThankYouPage
 * @package Emarsys\Emarsys\Model\Config\Source
 */
class RecommendedProductOnOrderThankYouPage
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '||', 'label' => 'Please Select'],
            ['value' => 'PERSONAL||personal-template', 'label' => 'Personal']
        ];
    }
}
