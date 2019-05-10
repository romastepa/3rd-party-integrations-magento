<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Plugin\Checkout\Model\Checkout;

use Emarsys\Emarsys\Helper\Data;
use Magento\{
    Framework\App\Config\ScopeConfigInterface,
    Store\Model\StoreManagerInterface,
    Customer\Model\Session,
    Newsletter\Model\SubscriberFactory
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
     * @var SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * LayoutProcessor constructor.
     *
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param StoreManagerInterface $storeManagerInterface
     * @param Session $session
     * @param SubscriberFactory $subscriberFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfigInterface,
        StoreManagerInterface $storeManagerInterface,
        Session $session,
        SubscriberFactory $subscriberFactory
    ) {
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->session = $session;
        $this->subscriberFactory = $subscriberFactory;
    }

    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $processor
     * @param array $jsLayout
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterProcess(\Magento\Checkout\Block\Checkout\LayoutProcessor $processor, $jsLayout) {
        $store = $this->storeManagerInterface->getStore();
        $newsLetterConfValue = $store->getConfig(Data::XPATH_OPTIN_SUBSCRIPTION_CHECKOUT_PROCESS);

        if (!$newsLetterConfValue) {
            return $jsLayout;
        }

        $flag = 0;

        if ($this->session->isLoggedIn()) {
            $customerEmail = $this->session->getCustomer()->getEmail();
            $subColl = $this->subscriberFactory->create()->getCollection()
                ->addFieldToFilter('subscriber_email', $customerEmail)
                ->addFieldToFilter('subscriber_status', 1);
            if (count($subColl->getData())) {
                $flag++;
            }
        }

        if ((!$this->session->isLoggedIn() || $flag < 1)) {
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
