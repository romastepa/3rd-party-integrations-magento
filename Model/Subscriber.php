<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model;

use Magento\Store\Model\ScopeInterface;

/**
 * Class Subscriber
 * @package Emarsys\Emarsys\Model
 */
class Subscriber extends \Magento\Newsletter\Model\Subscriber
{
    /**
     * Subscribes by email
     *
     * @param string $email
     * @throws \Exception
     * @return int
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function subscribe($email)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $emarsysHelper = $objectManager->get('\Emarsys\Emarsys\Helper\Data');
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        if ($emarsysHelper->isEmarsysEnabled($websiteId) == 'false') {
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

        $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        $this->loadByEmail($email);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $emarsysHelper = $objectManager->get('\Emarsys\Emarsys\Helper\Data');
        $request       = $objectManager->get('\Magento\Framework\App\RequestInterface');
        $http          = $objectManager->get('\Magento\Framework\HTTP\PhpEnvironment\RemoteAddress');
        $this->setData(['ip' => $http->getRemoteAddress(), 'page' => $request->getParam('page')]);

        if (!$this->getId()) {
            $this->setSubscriberConfirmCode($this->randomSequence());
        }

        $isConfirmNeed = $this->_scopeConfig->getValue(
            self::XML_PATH_CONFIRMATION_FLAG,
            ScopeInterface::SCOPE_STORE
        ) == 1 ? true : false;

        if ($optEnable = $this->_scopeConfig->getValue('opt_in/optin_enable/enable_optin', ScopeInterface::SCOPE_WEBSITES, $websiteId)) {
            //return single / double opt-in
            $optInType = $this->_scopeConfig->getValue('opt_in/optin_enable/opt_in_strategy', ScopeInterface::SCOPE_WEBSITES, $websiteId);
            if ($optInType == 'singleOptIn') {
                $isConfirmNeed = false;
            } elseif ($optInType == 'doubleOptIn') {
                $isConfirmNeed = true;
            }
        }
        $isOwnSubscribes = false;

        //It will return boolean value, If customer is logged in and email Id is the same.
        $isSubscribeOwnEmail = $this->_customerSession->isLoggedIn()
            && $this->_customerSession->getCustomerDataObject()->getEmail() == $email;

        $optinForcedConfirmation = $emarsysHelper->isOptinForcedConfirmationEnabled($websiteId);
        $isOwnSubscribes = $isSubscribeOwnEmail;

        if (!$this->getId() || $this->getStatus() == self::STATUS_UNSUBSCRIBED
            || $this->getStatus() == self::STATUS_NOT_ACTIVE) {
            if ($isConfirmNeed === true && $optinForcedConfirmation == true) {
                $this->setStatus(self::STATUS_NOT_ACTIVE);
            } elseif ($isConfirmNeed === true && $optinForcedConfirmation == false) {
                $this->setStatus(self::STATUS_NOT_ACTIVE);
            } else {
                $this->setStatus(self::STATUS_SUBSCRIBED);
            }
        } elseif ($this->getId() && $this->getStatus() == self::STATUS_SUBSCRIBED) {
            // Who have subID and status subscribed trying for 2nd time or more
            if ($isConfirmNeed === true && $optinForcedConfirmation == true) {
                $this->setStatus(self::STATUS_NOT_ACTIVE);
            } elseif ($isConfirmNeed === true && $optinForcedConfirmation == false) {
                $this->setStatus(self::STATUS_SUBSCRIBED);
            } else {
                $this->setStatus(self::STATUS_SUBSCRIBED);
            }
        } elseif ($this->getId() && ($this->getStatus() == self::STATUS_UNSUBSCRIBED) ||
            $this->getStatus() == self::STATUS_NOT_ACTIVE) {
            // Who have subID and status UnSubscribed or not active trying for 2nd time or more
            if ($isConfirmNeed === true && $optinForcedConfirmation == true) { //Double optin
                $this->setStatus(self::STATUS_NOT_ACTIVE);
            } elseif ($isConfirmNeed === true && $optinForcedConfirmation == false) { //Double optin
                $this->setStatus(self::STATUS_NOT_ACTIVE);
            } else {
                $this->setStatus(self::STATUS_SUBSCRIBED);
            }
        } elseif ($this->getId() && $isOwnSubscribes) {
            //loged in customer with subscription
            if ($isConfirmNeed === true && $optinForcedConfirmation == true) { //Double optin
                $this->setStatus(self::STATUS_NOT_ACTIVE);
            } elseif ($isConfirmNeed === true && $optinForcedConfirmation == false) { //Double optin
                $this->setStatus(self::STATUS_SUBSCRIBED);
            } else {
                $this->setStatus(self::STATUS_SUBSCRIBED);
            }
        }
        if ($isOwnSubscribes) {
            //loged in customer with subscription
            if ($isConfirmNeed === true && $optinForcedConfirmation == true) { //Double optin
                $this->setStatus(self::STATUS_NOT_ACTIVE);
            } elseif ($isConfirmNeed === true && $optinForcedConfirmation == false) { //Double optin
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
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
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
	    if($this->getStatus() == self::STATUS_NOT_ACTIVE) {
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
