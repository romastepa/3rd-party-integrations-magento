<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Observer;

use Emarsys\Emarsys\Model\Logs;
use Magento\{
    Framework\Event\ObserverInterface,
    Store\Model\StoreManagerInterface,
    Framework\Registry as Registry,
    Customer\Model\CustomerFactory
};

/**
 * Class CustomerSaveBefore
 * @package Emarsys\Emarsys\Observer
 */
class CustomerSaveBefore implements ObserverInterface
{
    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * CustomerSaveBefore constructor.
     * @param Logs $emarsysLogs
     * @param StoreManagerInterface $storeManager
     * @param Registry $registry
     * @param CustomerFactory $customerFactory
     */
    public function __construct(
        Logs $emarsysLogs,
        StoreManagerInterface $storeManager,
        Registry $registry,
        CustomerFactory $customerFactory
    ) {
        $this->emarsysLogs = $emarsysLogs;
        $this->storeManager = $storeManager;
        $this->_registry = $registry;
        $this->customerFactory = $customerFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $customer = $observer->getEvent()->getCustomer();
            if ($customer->getId()) {
                $customer->setOrigData('customer_email', $customer->getEmail());
            } else {
                $customer->setOrigData('NewCustomerCheck', true);
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'GetCustomerBeforeSave Observer'
            );
        }
    }
}
