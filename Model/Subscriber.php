<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model;

use Emarsys\Emarsys\Helper\Data\Proxy as EmarsysHelper;
use Magento\{
    Customer\Api\AccountManagementInterface,
    Customer\Api\CustomerRepositoryInterface,
    Customer\Model\Session,
    Framework\App\Config\ScopeConfigInterface,
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
     * Subscriber constructor.
     *
     * @param EmarsysHelper $emarsysHelper
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
     * @param DateTime|null $dateTime
     * @param array $data
     */
    public function __construct
    (
        EmarsysHelper $emarsysHelper,
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
        DateTime $dateTime = null,
        array $data = []
    ) {
        $this->emarsysHelper = $emarsysHelper;
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
            $data
        );
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
        $optinForcedConfirmation = $this->emarsysHelper->isOptinForcedConfirmationEnabled($store->getWebsiteId());
        $isOwnSubscribes = $isSubscribeOwnEmail;

        if (!$this->getId() || $this->getStatus() == self::STATUS_UNSUBSCRIBED || $this->getStatus() == self::STATUS_NOT_ACTIVE) {
            if ($isConfirmNeed && ($optinForcedConfirmation || !$optinForcedConfirmation)) {
                $this->setStatus(self::STATUS_NOT_ACTIVE);
            } else {
                $this->setStatus(self::STATUS_SUBSCRIBED);
            }
        } elseif (($this->getId() && $this->getStatus() == self::STATUS_SUBSCRIBED) || $isOwnSubscribes) {
            if ($isConfirmNeed && $optinForcedConfirmation) {
                $this->setStatus(self::STATUS_NOT_ACTIVE);
            } elseif ($isConfirmNeed && !$optinForcedConfirmation) {
                $this->setStatus(self::STATUS_SUBSCRIBED);
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

    /**
     * Load subscriber info by customerId
     *
     * @param int $customerId
     * @return $this
     */
    public function loadByCustomerId($customerId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $http    = $objectManager->get('\Magento\Framework\HTTP\PhpEnvironment\RemoteAddress');
        $request = $objectManager->get('\Magento\Framework\App\RequestInterface');
        $page    = (!empty($request->getParam('page')) != false) ? 'customer_my_account' : 'customer_registration';
        try {
            $customerData = $this->customerRepository->getById($customerId);
            $customerData->setStoreId($this->_storeManager->getStore()->getId());
            $data = $this->getResource()->loadByCustomerData($customerData);
            $this->setData(['ip' => $http->getRemoteAddress(), 'page' => $page]);
            $this->addData($data);
            if (!empty($data) && $customerData->getId() && !$this->getCustomerId()) {
                $this->setCustomerId($customerData->getId());
                $this->setSubscriberConfirmCode($this->randomSequence());
                $this->save();
            }
        } catch (NoSuchEntityException $e) {
        }
        return $this;
    }


    /**
     * Sends out confirmation success email
     *
     * @return $this
     */
    public function sendConfirmationSuccessEmail()
    {
        if ($this->getImportMode()) {
            return $this;
        }

        if (!$this->_scopeConfig->getValue(
                self::XML_PATH_SUCCESS_EMAIL_TEMPLATE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ) || !$this->_scopeConfig->getValue(
                self::XML_PATH_SUCCESS_EMAIL_IDENTITY,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        ) {
            return $this;
        }

        $this->inlineTranslation->suspend();

        $this->_transportBuilder->setTemplateIdentifier(
            $this->_scopeConfig->getValue(
                self::XML_PATH_SUCCESS_EMAIL_TEMPLATE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        )->setTemplateOptions(
            [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $this->_storeManager->getStore()->getId(),
            ]
        )->setTemplateVars(
            ['subscriber' => $this]
        )->setFrom(
            $this->_scopeConfig->getValue(
                self::XML_PATH_SUCCESS_EMAIL_IDENTITY,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        )->addTo(
            $this->getEmail(),
            $this->getName()
        );
        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();

        $this->inlineTranslation->resume();

        return $this;
    }
}
