<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Config\Source;

/**
 * Class Emarsystosyncin
 * @package Emarsys\Emarsys\Model\Config\Source
 */
class Emarsystosyncin implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '00:20', 'label' => __('00:20')],
            ['value' => '10:20', 'label' => __('10:20')]
        ];
    }
}
