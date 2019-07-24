<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2019 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Observer;

use Magento\{
    Customer\Model\Session,
    Framework\Event\Observer,
    Framework\Event\ObserverInterface,
    Framework\App\Request\Http,
    Store\Model\StoreManagerInterface
};
use Emarsys\Emarsys\{
    Model\Api\Subscriber,
    Model\ResourceModel\Customer,
    Helper\Data as EmarsysHelperData
};

/**
 * Class RealTimeSubscriber
 * @package Emarsys\Emarsys\Observer
 */
class RealTimeSubscriber implements ObserverInterface
{
    /**
     * @var Subscriber
     */
    protected $subscriberModel;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Customer
     */
    protected $customerResourceModel;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var EmarsysHelperData
     */
    protected $emarsysHelper;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * RealTimeSubscriber constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param Subscriber $subscriberModel
     * @param Customer $customerResourceModel
     * @param Http $request
     * @param EmarsysHelperData $emarsysHelper
     * @param Session $customerSession
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Subscriber $subscriberModel,
        Customer $customerResourceModel,
        Http $request,
        EmarsysHelperData $emarsysHelper,
        Session $customerSession
    ) {
        $this->subscriberModel = $subscriberModel;
        $this->storeManager = $storeManager;
        $this->customerResourceModel = $customerResourceModel;
        $this->request = $request;
        $this->emarsysHelper = $emarsysHelper;
        $this->customerSession = $customerSession;
    }

    /**
     * @param Observer $observer
     * @return bool|void
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $subscriber = $event->getSubscriber();
        $store = $this->storeManager->getStore($subscriber->getStoreId());
        $subscriberId = $subscriber->getId();
        $storeId = $store->getStoreId();
        $websiteId = $store->getWebsiteId();

        if ($subscriber->getEmarsysNoExport()
            || !$this->emarsysHelper->isContactsSynchronizationEnable($websiteId)
        ) {
            return true;
        }

        $this->customerSession->setWebExtendCustomerEmail($subscriber->getSubscriberEmail());
        try {
            $createSubscriber = $this->emarsysHelper->realtimeTimeBasedOptinSync($subscriber);

            if ($createSubscriber) {
                $result = $this->subscriberModel->syncSubscriber($subscriberId, $storeId);
                $subscriber->setEmarsysNoExport(true);
                return $result;
            }

            return true;
        } catch (\Exception $e) {
            $this->emarsysHelper->syncFail($subscriberId, $websiteId, $storeId, 0, 2);
            $this->emarsysHelper->addErrorLog(
                $e->getMessage(),
                $storeId,
                'RealTimeSubscriber::execute'
            );
        }
        return false;
    }
}
