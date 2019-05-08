<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Plugin\Checkout\Model\Checkout;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session;
use Magento\Newsletter\Model\SubscriberFactory;

/**
 * Class LayoutProcessor
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
    ){
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->session = $session;
        $this->subscriberFactory = $subscriberFactory;
    }

    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        array $jsLayout
    ) {
        $flag = 0;
        $store = $this->storeManagerInterface->getStore();
        $storeCode = $store->getCode();
        $websiteId = $store->getWebsiteId();
        $newsLetterConfValue = $this->scopeConfigInterface->getValue(
            'opt_in/subscription_checkout_process/newsletter_sub_checkout_yes_no',
            $storeCode,
            $websiteId
        );
        if ($this->session->isLoggedIn()) {
            $customerEmail = $this->session->getCustomer()->getEmail();
            $subColl = $this->subscriberFactory->create()->getCollection()
                ->addFieldToFilter('subscriber_email', $customerEmail)
                ->addFieldToFilter('subscriber_status', 1);
            if (count($subColl->getData())) {
                $flag++;
            }
        }

        if ((!$this->session->isLoggedIn() || $flag < 1) && $newsLetterConfValue) {
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['shipping-address-fieldset']['children']['subscribe'] = [
                'component' => 'Magento_Ui/js/form/element/abstract',
                'config' => [
                    'customScope' => 'shippingAddress',
                    'template' => 'ui/form/field',
                    'elementTmpl' => 'ui/form/element/checkbox',
                    'options' => [],
                    'id' => 'subscribe'
                ],
                'dataScope' => 'shippingAddress.subscribe',
                'label' => 'Sign Up for Newsletter',
                'provider' => 'checkoutProvider',
                'visible' => true,
                'validation' => [],
                'sortOrder' => 250,
                'id' => 'subscribe',
                'value' => 'subscription'
            ];
        }

        return $jsLayout;
    }
}
