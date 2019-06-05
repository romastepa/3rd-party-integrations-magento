<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\Config\Source;

/**
 * Class LogDays
 * @package Emarsys\Emarsys\Model\Config\Source
 */
class LogDays implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '2', 'label' => '2'],
            ['value' => '5', 'label' => '5'],
            ['value' => '10', 'label' => '10'],
            ['value' => '15', 'label' => '15'],
            ['value' => '30', 'label' => '30'],
        ];
    }
}
