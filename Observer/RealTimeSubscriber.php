<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Model\Api\Subscriber;
use Emarsys\Emarsys\Model\ResourceModel\Customer;
use Magento\Framework\App\Request\Http;
use Emarsys\Emarsys\Helper\Data;
use Magento\Customer\Model\Session;

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
     * @var Data
     */
    protected $dataHelper;

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
     * @param Data $dataHelper
     * @param Session $customerSession
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Subscriber $subscriberModel,
        Customer $customerResourceModel,
        Http $request,
        Data $dataHelper,
        Session $customerSession
    ) {
        $this->subscriberModel = $subscriberModel;
        $this->storeManager = $storeManager;
        $this->customerResourceModel = $customerResourceModel;
        $this->request = $request;
        $this->dataHelper = $dataHelper;
        $this->customerSession = $customerSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();
        $subscriber = $event->getSubscriber();
        $subscriberId = $subscriber->getId();
        $store = $this->storeManager->getStore();
        $storeId = $store->getStoreId();
        $websiteId = $store->getWebsiteId();
        $pageHandle = $this->request->getFullActionName();

        $realtimeStatus = $this->customerResourceModel->getDataFromCoreConfig(
            'contacts_synchronization/emarsys_emarsys/realtime_sync',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );

        $subscriberObjEmailSaved = $subscriber->getSubscriberEmail();

        $subscriberEmailChangeFlag = false;
        if (method_exists($subscriber, 'getOrigData') && $subscriberObjEmailSaved != $subscriber->getOrigData('subscriber_email')){
            $subscriberEmailChangeFlag = true;
        }

        $this->customerSession->setWebExtendCustomerEmail($subscriber->getSubscriberEmail());

        if ($realtimeStatus == 1) {
            $frontendFlag = 1;
            $this->dataHelper->realtimeTimeBasedOptinSync($subscriber);
            $result = $this->subscriberModel->syncSubscriber(
                $subscriberId,
                $storeId,
                $frontendFlag,
                $pageHandle,
                $websiteId,
                0,
                $subscriberEmailChangeFlag
            );

            if ($result['apiResponseStatus'] == '200') {
                return;
            }
        } else {
            $this->dataHelper->syncFail($subscriberId, $websiteId, $storeId, 0, 2);
        }
    }
}


