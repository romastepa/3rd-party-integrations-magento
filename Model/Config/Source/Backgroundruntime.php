<?php

namespace Emarsys\Emarsys\Model\Config\Source;

/**
 * Class Backgroundruntime
 * @package Emarsys\Emarsys\Model\Config\Source
 */
class Backgroundruntime implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [

            ['value' => '00:20', 'label' => __('00:20')],
            ['value' => '02:20', 'label' => __('02:20')],
            ['value' => '04:20', 'label' => __('04:20')],
            ['value' => '06:20', 'label' => __('06:20')]
        ];
    }
}
