<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Config\Source;

/**
 * Class DeleteArchiveDay
 * @package Emarsys\Emarsys\Model\Config\Source
 */
class DeleteArchiveDay
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '90', 'label' => '90'],
            ['value' => '120', 'label' => '120'],
            ['value' => '150', 'label' => '150']
        ];
    }
}
