<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Config\Source;

/**
 * Class IdentityRegistered
 * @package Emarsys\Emarsys\Model\Config\Source
 */
class IdentityRegistered implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'customer_id', 'label' => __('Customer Id')],
            ['value' => 'email_address', 'label' => __('Email Address')]
        ];
    }
}
