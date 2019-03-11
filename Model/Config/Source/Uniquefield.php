<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Config\Source;

/**
 * Class Uniquefield
 * @package Emarsys\Emarsys\Model\Config\Source
 */
class Uniquefield implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'email', 'label' => __('Email')],
            ['value' => 'magento_id', 'label' => __('Magento Id')],
            ['value' => 'unique_id', 'label' => __('Email#WebsiteId#StoreId')]
        ];
    }
}
