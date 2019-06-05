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
            ['value' => '15', 'label' => '15'],
            ['value' => '30', 'label' => '30'],
            ['value' => '45', 'label' => '45'],
            ['value' => '60', 'label' => '60'],
            ['value' => '90', 'label' => '90']
        ];
    }
}
