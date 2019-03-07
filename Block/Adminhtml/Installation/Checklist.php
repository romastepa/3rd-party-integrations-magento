<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Installation;

use Emarsys\Emarsys\Helper\Data;
use Emarsys\Emarsys\Helper\Customer;
use Magento\Backend\Block\Template\Context;

/**
 * Class Checklist
 * @package Emarsys\Emarsys\Block\Adminhtml\Installation
 */
class Checklist extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $requestInterface;

    /**
     * @var Data
     */
    protected $emarsysHelperData;

    /**
     * @var Customer
     */
    protected $emarsysHelperCustomer;

    /**
     * Checklist constructor.
     * @param Context $context
     * @param Data $emarsysHelperData
     * @param Customer $emarsysHelperCustomer
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $emarsysHelperData,
        Customer $emarsysHelperCustomer,
        array $data = []
    )
    {
        $this->storeManagerInterface = $context->getStoreManager();
        $this->requestInterface = $context->getRequest();
        $this->emarsysHelperData = $emarsysHelperData;
        $this->emarsysHelperCustomer = $emarsysHelperCustomer;
        parent::__construct($context, $data);
    }

    public function getEmarsysCustomerFieldNames()
    {
        $storeId = $this->getStoreId();
        $emarsysCustomerFieldNames = [];
        if ($storeId) {
            $emarsysCustomerFields = $this->emarsysHelperCustomer->getEmarsysCustomerSchema($storeId);
            if (is_array($emarsysCustomerFields) && isset($emarsysCustomerFields['data'])) {
                $emarsysCustomerDataFields = $emarsysCustomerFields['data'];
                foreach ($emarsysCustomerDataFields as $CustomerField) {
                    $emarsysCustomerFieldNames[] = $CustomerField['name'];
                }
            }
        }
        return $emarsysCustomerFieldNames;
    }

    public function getStore()
    {
        $storeId = $this->getStoreId();
        if ($storeId) {
            $store = $this->storeManagerInterface->getStore($storeId);
            return $store;
        }
        return false;
    }

    public function getStoreId()
    {
        return $this->requestInterface->getParam('store');
    }

    public function getRequirementsInfo($storeId)
    {
        return $this->emarsysHelperData->getRequirementsInfo($storeId);
    }
}
