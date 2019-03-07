<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\Config\Source;

/**
 * Class Frequency
 * @package Emarsys\Emarsys\Model\Config\Source
 */
class Frequency implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'Hourly', 'label' => 'Hourly'],
            ['value' => 'Daily', 'label' => 'Daily']
        ];
    }
}
