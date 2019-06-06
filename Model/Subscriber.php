<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model;

use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
use Magento\{
    Customer\Api\AccountManagementInterface,
    Customer\Api\CustomerRepositoryInterface,
    Customer\Api\Data\CustomerInterfaceFactory,
    Customer\Model\Session,
    Framework\Api\DataObjectHelper,
    Framework\App\Config\ScopeConfigInterface,
    Framework\App\ProductMetadataInterface,
    Framework\Data\Collection\AbstractDb,
    Framework\Mail\Template\TransportBuilder,
    Framework\Model\Context,
    Framework\Model\ResourceModel\AbstractResource,
    Framework\Registry,
    Framework\Stdlib\DateTime\DateTime,
    Framework\Translate\Inline\StateInterface,
    Newsletter\Helper\Data,
    Store\Model\StoreManagerInterface
};

/**
 * Class Subscriber
 *
 * @package Emarsys\Emarsys\Model
 */
class Subscriber extends \Magento\Newsletter\Model\Subscriber
{
    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * Subscriber constructor.
     *
     * @param EmarsysHelper $emarsysHelper
     * @param ProductMetadataInterface $productMetadata
     * @param Context $context
     * @param Registry $registry
     * @param Data $newsletterData
     * @param ScopeConfigInterface $scopeConfig
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $customerAccountManagement
     * @param StateInterface $inlineTranslation
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param DateTime|null $dateTime
     * @param CustomerInterfaceFactory|null $customerFactory
     * @param DataObjectHelper|null $dataObjectHelper
     */
    public function __construct
    (
        EmarsysHelper $emarsysHelper,
        ProductMetadataInterface $productMetadata,
        Context $context,
        Registry $registry,
        Data $newsletterData,
        ScopeConfigInterface $scopeConfig,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $customerAccountManagement,
        StateInterface $inlineTranslation,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        DateTime $dateTime = null,
        CustomerInterfaceFactory $customerFactory = null,
        DataObjectHelper $dataObjectHelper = null
    ) {
        $this->emarsysHelper = $emarsysHelper;
        $this->productMetadata = $productMetadata;

        if (version_compare($this->productMetadata->getVersion(), '2.2.6', '>=')) {
            parent::__construct(
                $context,
                $registry,
                $newsletterData,
                $scopeConfig,
                $transportBuilder,
                $storeManager,
                $customerSession,
                $customerRepository,
                $customerAccountManagement,
                $inlineTranslation,
                $resource,
                $resourceCollection,
                $data,
                $dateTime
            );
        } else {
            parent::__construct(
                $context,
                $registry,
                $newsletterData,
                $scopeConfig,
                $transportBuilder,
                $storeManager,
                $customerSession,
                $customerRepository,
                $customerAccountManagement,
                $inlineTranslation,
                $resource,
                $resourceCollection,
                $data,
                $dateTime,
                $customerFactory,
                $dataObjectHelper
            );
        }
    }

    /**
     * @param string $email
     * @return int|void
     * @throws \Exception
     */
    public function subscribe($email)
    {
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        if (!$this->emarsysHelper->isEmarsysEnabled($websiteId)) {
            return parent::subscribe($email);
        } else {
            return $this->subscribeByEmarsys($email);
        }
    }

    /**
     * @param $email
     * @return int|void
     * @throws \Exception
     */
    public function subscribeByEmarsys($email)
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->_storeManager->getStore();
        $this->loadByEmail($email);

        if (!$this->getId()) {
            $this->setSubscriberConfirmCode($this->randomSequence());
        }

        $isConfirmNeed = $store->getConfig(self::XML_PATH_CONFIRMATION_FLAG) == 1 ? true : false;
        if ($optEnable = $store->getConfig(EmarsysHelper::XPATH_OPTIN_ENABLED)) {
            //return single / double opt-in
            $optInType = $store->getConfig(EmarsysHelper::XPATH_OPTIN_EVERYPAGE_STRATEGY);
            if ($optInType == 'singleOptIn') {
                $isConfirmNeed = false;
            } elseif ($optInType == 'doubleOptIn') {
                $isConfirmNeed = true;
            }
        }

        //It will return boolean value, If customer is logged in and email Id is the same.
        $isSubscribeOwnEmail = $this->_customerSession->isLoggedIn()
            && $this->_customerSession->getCustomerDataObject()->getEmail() == $email;

        if (!$this->getId() || $this->getStatus() == self::STATUS_UNSUBSCRIBED || $this->getStatus() == self::STATUS_NOT_ACTIVE) {
            if ($isConfirmNeed) {
                $this->setStatus(self::STATUS_NOT_ACTIVE);
            } else {
                $this->setStatus(self::STATUS_SUBSCRIBED);
            }
        } elseif ($this->getId() && $this->getStatus() == self::STATUS_SUBSCRIBED) {
            if ($isConfirmNeed) {
                $this->setStatus(self::STATUS_NOT_ACTIVE);
            } else {
                $this->setStatus(self::STATUS_SUBSCRIBED);
            }
        }

        $this->setSubscriberEmail($email);

        if ($isSubscribeOwnEmail) {
            try {
                $customer = $this->customerRepository->getById($this->_customerSession->getCustomerId());
                $this->setStoreId($customer->getStoreId());
                $this->setCustomerId($customer->getId());
            } catch (\Exception $e) {
                $this->setStoreId($this->_storeManager->getStore()->getId());
                $this->setCustomerId(0);
            }
        } else {
            $this->setStoreId($this->_storeManager->getStore()->getId());
            $this->setCustomerId(0);
        }

        $this->setStatusChanged(true);

        try {
            $this->save();
            if ($this->getStatus() == self::STATUS_NOT_ACTIVE) {
                $this->sendConfirmationRequestEmail();
            } elseif ($this->getStatus() == self::STATUS_SUBSCRIBED) {
                $this->sendConfirmationSuccessEmail();
            }
            return $this->getStatus();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
