<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model;

/**
 * Class Message
 * @package Emarsys\Emarsys\Model
 */
class Message extends \Magento\Framework\Mail\Message
{
    /**
     * @var array
     */
    protected $emarsysData = [];

    /**
     * @param $emarsysData
     * @return mixed
     */
    public function setEmarsysData($emarsysData)
    {
        return $this->emarsysData = $emarsysData;
    }

    /**
     * @return array
     */
    public function getEmarsysData()
    {
        return $this->emarsysData;
    }
}
