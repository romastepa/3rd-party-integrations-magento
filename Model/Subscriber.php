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
        if (!$emarsysHelper->isEmarsysEnabled($websiteId)) {
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
}
