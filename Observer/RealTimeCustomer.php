<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Observer;

use Magento\{
    Customer\Model\CustomerFactory,
    Framework\Event\Observer,
    Framework\Event\ObserverInterface,
    Framework\Registry,
    Newsletter\Model\Subscriber,
    Store\Model\StoreManagerInterface
};
use Emarsys\Emarsys\{
    Model\Api\Contact,
    Model\ResourceModel\Customer,
    Helper\Data as EmarsysHelperData
};

/**
 * Class RealTimeCustomer
 * @package Emarsys\Emarsys\Observer
 */
class RealTimeCustomer implements ObserverInterface
{
    /**
     * @var EmarsysHelperData
     */
    protected $emarsysHelper;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var Contact
     */
    private $contactModel;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Customer
     */
    private $customerResourceModel;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var Subscriber
     */
    protected $subscriber;

    /**
     * RealTimeCustomer constructor.
     *
     * @param CustomerFactory $customerFactory
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param Contact $contactModel
     * @param EmarsysHelperData $emarsysHelper
     * @param Customer $customerResourceModel
     * @param Subscriber $subscriber
     */
    public function __construct(
        CustomerFactory $customerFactory,
        Registry $registry,
        StoreManagerInterface $storeManager,
        Contact $contactModel,
        EmarsysHelperData $emarsysHelper,
        Customer $customerResourceModel,
        Subscriber $subscriber
    ) {
        $this->emarsysHelper = $emarsysHelper;
        $this->registry = $registry;
        $this->contactModel = $contactModel;
        $this->storeManager = $storeManager;
        $this->customerResourceModel = $customerResourceModel;
        $this->customerFactory = $customerFactory;
        $this->subscriber = $subscriber;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $observer->getEvent()->getCustomer();
        $customerId = $customer->getId();
        $storeId = $customer->getStoreId();
        $store = $this->storeManager->getStore($storeId);
        $websiteId = $customer->getWebsiteId();

        if (!$this->emarsysHelper->isContactsSynchronizationEnable($websiteId)) {
            return;
        }

        try {
            if ($store->getConfig(EmarsysHelperData::XPATH_EMARSYS_REALTIME_SYNC) == 1) {
                $customerVar = 'create_customer_variable_' . $customerId;
                if ($this->registry->registry($customerVar) == 'created') {
                    return;
                }
                $this->contactModel->syncContact($customer, $websiteId, $storeId);
                $this->registry->register($customerVar, 'created');
            } else {
                $this->emarsysHelper->syncFail($customerId, $websiteId, $storeId, 0, 1);
            }
        } catch (\Exception $e) {
            $this->emarsysHelper->syncFail($customerId, $websiteId, $storeId, 0, 1);
            $this->emarsysHelper->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'RealTimeCustomer::execute()'
            );
        }
    }
}
