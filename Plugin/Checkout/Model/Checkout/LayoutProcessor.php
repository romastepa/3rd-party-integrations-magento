<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Plugin\Checkout\Model\Checkout;

use Emarsys\Emarsys\{
    Helper\Data,
    Model\Subscriber
};
use Magento\{
    Framework\App\Config\ScopeConfigInterface,
    Framework\Exception\NoSuchEntityException,
    Store\Model\StoreManagerInterface,
    Customer\Model\Session
};

/**
 * Class LayoutProcessor
 *
 * @package Emarsys\Emarsys\Plugin\Checkout\Model\Checkout
 */
class LayoutProcessor
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Subscriber
     */
    protected $subscriber;

    /**
     * LayoutProcessor constructor.
     *
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param StoreManagerInterface $storeManagerInterface
     * @param Session $session
     * @param Subscriber $subscriber
     */
    public function __construct(
        ScopeConfigInterface $scopeConfigInterface,
        StoreManagerInterface $storeManagerInterface,
        Session $session,
        Subscriber $subscriber
    ) {
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->session = $session;
        $this->subscriber = $subscriber;
    }

    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $processor
     * @param array $jsLayout
     * @return array
     * @throws NoSuchEntityException
     */
    public function afterProcess(\Magento\Checkout\Block\Checkout\LayoutProcessor $processor, $jsLayout)
    {
        $store = $this->storeManagerInterface->getStore();
        $newsLetterConfValue = $store->getConfig(Data::XPATH_OPTIN_SUBSCRIPTION_CHECKOUT_PROCESS);

        if (!$newsLetterConfValue) {
            return $jsLayout;
        }

        $subscribed = false;

        if ($this->session->isLoggedIn()) {
            $customerEmail = $this->session->getCustomer()->getEmail();
            $subColl = $this->subscriber->loadByEmail($customerEmail);
            if ($subColl->getId()) {
                $subscribed = true;
            }
        }

        if ((!$this->session->isLoggedIn() || !$subscribed)) {
            if (isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
                ['shippingAddress']['children']['shipping-address-fieldset']['children'])
            ) {
                $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
                ['shippingAddress']['children']['shipping-address-fieldset']['children']['subscribe'] = [
                    'component' => 'Magento_Ui/js/form/element/abstract',
                    'config' => [
                        'customScope' => 'shippingAddress',
                        'template' => 'ui/form/field',
                        'elementTmpl' => 'ui/form/element/checkbox',
                        'options' => [],
                        'id' => 'subscribe',
                    ],
                    'dataScope' => 'shippingAddress.subscribe',
                    'label' => 'Sign Up for Newsletter',
                    'provider' => 'checkoutProvider',
                    'visible' => true,
                    'validation' => [],
                    'sortOrder' => 250,
                    'id' => 'subscribe',
                    'value' => 'subscription',
                ];
            }
        }

        return $jsLayout;
    }
}
