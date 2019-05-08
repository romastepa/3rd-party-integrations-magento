<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Observer;

use Psr\Log\LoggerInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Model\Api\Contact;
use Emarsys\Emarsys\Helper\Data;
use Emarsys\Emarsys\Model\ResourceModel\Customer;
use Emarsys\Emarsys\Model\Logs;
use Magento\Newsletter\Model\Subscriber;

/**
 * Class RealTimeCustomer
 * @package Emarsys\Emarsys\Observer
 */
class RealTimeCustomer implements ObserverInterface
{
    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

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
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * @var Subscriber
     */
    protected $subscriber;

    /**
     * RealTimeCustomer constructor.
     * @param LoggerInterface $logger
     * @param CustomerFactory $customerFactory
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param Contact $contactModel
     * @param Data $dataHelper
     * @param Customer $customerResourceModel
     * @param Logs $emarsysLogs
     */
    public function __construct(
        LoggerInterface $logger,
        CustomerFactory $customerFactory,
        Registry $registry,
        StoreManagerInterface $storeManager,
        Contact $contactModel,
        Data $dataHelper,
        Customer $customerResourceModel,
        Logs $emarsysLogs,
        Subscriber $subscriber
    ) {
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
        $this->registry = $registry;
        $this->contactModel = $contactModel;
        $this->storeManager = $storeManager;
        $this->customerResourceModel = $customerResourceModel;
        $this->customerFactory = $customerFactory;
        $this->emarsysLogs = $emarsysLogs;
        $this->subscriber = $subscriber;

    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $data = $observer->getEvent();
            $customer = $data->getCustomer();
            $storeId = $customer->getStoreId();
            $websiteId = $customer->getWebsiteId();

            if ($this->dataHelper->isEmarsysEnabled($websiteId) == 'false') {
                return;
            }

            $subscriberId = 0;
            $isNewCustomer = true;
            if (method_exists($observer->getEvent()->getCustomer(), 'getOrigData')) {
                $isNewCustomer = $observer->getEvent()->getCustomer()->getOrigData('NewCustomerCheck');
            }
            if ($isNewCustomer) {
                $this->registry->unregister('NewCustomerIdSet');
                $this->registry->register('NewCustomerIdSet',$customer->getId());

                $checkSubscriber = $this->subscriber->loadByEmail($customer->getEmail());
                $subscriberId = $checkSubscriber->getId();
            }

            $forceMagentoIDAsKeyID = $beforeSaveEmailAddress = false;
            if (method_exists($observer->getEvent()->getCustomer(),'getOrigData')) {
                $beforeSaveEmailAddress = $observer->getEvent()->getCustomer()->getOrigData('customer_email');
            }
            if ($beforeSaveEmailAddress != $observer->getEvent()->getCustomer()->getEmail()) {
                $forceMagentoIDAsKeyID = true;
            }

            $realtimeStatus = $this->customerResourceModel->getDataFromCoreConfig(
                'contacts_synchronization/emarsys_emarsys/realtime_sync',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $websiteId
            );

            if (isset($data['email'])) {
                $customerData = $this->customerFactory->create()->setWebsiteId($websiteId)->loadByEmail($data['email']);
                $customerId = $customerData->getEntityId();
                $websiteId = $customerData->getWebsiteId();
            } else {
                $customerId = $customer->getId();
            }

            if ($realtimeStatus == 1) {
                $customerVar = 'create_customer_variable_' . $customerId;
                if ($this->registry->registry($customerVar) == 'created') {
                    return;
                }
                $this->contactModel->syncContact($customerId, $websiteId, $storeId, 0, $forceMagentoIDAsKeyID,
                    $subscriberId);
                $this->registry->register($customerVar, 'created');
            } else {
                $this->dataHelper->syncFail($customerId, $websiteId, $storeId, 0, 1);
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'RealTimeCustomer::execute()'
            );
        }
    }
}
