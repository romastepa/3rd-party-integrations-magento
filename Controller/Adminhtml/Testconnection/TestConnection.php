<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Testconnection;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

/**
 * Class Testconnection
 * @package Emarsys\Emarsys\Controller\Adminhtml\Testconnection
 */
abstract class TestConnection extends Action
{
    /**
     * Testconnection constructor.
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @param $x
     * @return bool
     */
    public function is_json($x)
    {
        if (!is_string($x) || !trim($x)) {
            return false;
        }
        return (
            // Maybe an empty string, array or object
            $x === '""' ||
            $x === '[]' ||
            $x === '{}' ||
            // Maybe an encoded JSON string
            $x[0] === '"' ||
            // Maybe a flat array
            $x[0] === '[' ||
            // Maybe an associative array
            $x[0] === '{'
        ) && ($x === 'null' || json_decode($x) !== null);
    }
}
