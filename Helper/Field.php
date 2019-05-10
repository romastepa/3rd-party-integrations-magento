<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Emarsys\Emarsys\Model\ResourceModel\Customer as EmarsysResourceModelCustomer;
use Emarsys\Emarsys\Model\Api\Api;

/**
 * Class Field
 * @package Emarsys\Emarsys\Helper
 */
class Field extends AbstractHelper
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var EmarsysResourceModelCustomer
     */
    protected $customerResourceModel;

    /**
     * Field constructor.
     * @param Data $dataHelper
     * @param Api $api
     * @param Context $context
     * @param EmarsysResourceModelCustomer $customer
     */
    public function __construct(
        Data $dataHelper,
        Api $api,
        Context $context,
        EmarsysResourceModelCustomer $customer
    ) {
        ini_set('default_socket_timeout', 5000);
        $this->logger = $context->getLogger();
        $this->api = $api;
        $this->dataHelper = $dataHelper;
        $this->customerResourceModel = $customer;
    }

    /**
     * @param $storeId
     * @return array
     * @throws \Exception
     * @throws \Zend_Json_Exception
     */
    public function getEmarsysOptionSchema($storeId)
    {
        $this->dataHelper->getEmarsysAPIDetails($storeId);
        $emarsysContactFields = $this->customerResourceModel->getEmarsysContactFields($storeId);
        $emarsysFieldOptions = [];
        foreach ($emarsysContactFields as $emarsysField) {
            if ($emarsysField['type'] == "singlechoice" || $emarsysField['type'] == "multichoice") {
                $response = $this->dataHelper->send('GET', 'field/' . $emarsysField['emarsys_field_id'] . '/choice');
                $jsonDecode = \Zend_Json::decode($response);
                if (is_array($jsonDecode['data'])) {
                    foreach ($jsonDecode['data'] as $optionField) {
                        $emarsysFieldOptions[$emarsysField['emarsys_field_id']][] = $optionField;
                    }
                }
            }
        }

        return $emarsysFieldOptions;
    }
}
