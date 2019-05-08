<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Class Customer
 * @package Emarsys\Emarsys\Helper
 */
class Customer extends AbstractHelper
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Data
     */
    protected $emarsysHelper;

    /**
     * Customer constructor.
     * @param Context $context
     * @param Data $emarsysHelper
     */
    public function __construct(
        Context $context,
        Data $emarsysHelper
    ) {
        ini_set('default_socket_timeout', 1000);
        $this->context = $context;
        $this->emarsysHelper = $emarsysHelper;
    }

    /**
     * @param $storeId
     * @return bool|mixed
     */
    public function getEmarsysCustomerSchema($storeId)
    {
        try {
            $this->emarsysHelper->getEmarsysAPIDetails($storeId);
            $response = $this->emarsysHelper->send('GET', 'field/translate/en');
            return \Zend_Json::decode($response);
        } catch (\Exception $e) {
            $this->emarsysHelper->addErrorLog(htmlentities($e->getMessage()), $storeId, 'getEmarsysCustomerSchema');
            return false;
        }
    }
}
