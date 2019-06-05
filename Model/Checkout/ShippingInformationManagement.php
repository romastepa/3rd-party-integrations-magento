<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Model\Logs;
use Magento\Checkout\Model\ShippingInformationManagement as CheckoutShippingInformationManagement;
use Magento\Checkout\Api\Data\ShippingInformationInterface;

/**
 * Class ShippingInformationManagement
 * @package Emarsys\Emarsys\Model\Checkout
 */
class ShippingInformationManagement
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * ShippingInformationManagement constructor.
     *
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Logs $emarsysLogs
     */
    public function __construct(
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        Logs $emarsysLogs
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->emarsysLogs = $emarsysLogs;
    }

    /**
     * @param CheckoutShippingInformationManagement $subject
     * @param $cartId
     * @param ShippingInformationInterface $addressInformation
     */
    public function beforeSaveAddressInformation(
        CheckoutShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        try {
            $extAttributes = $addressInformation->getExtensionAttributes();
            $storeId = $this->storeManager->getStore()->getStoreId();

            if ($extAttributes && is_object($extAttributes)) {
                $isCustomerSubscribed = $extAttributes->getSubscribe();
                $attributeData = explode("||", $isCustomerSubscribed);

                if ($attributeData[0] == 1) {
                    $this->checkoutSession->setNewsletterSubCheckout(1);
                } else {
                    $this->checkoutSession->setNewsletterSubCheckout(0);
                }
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'beforeSaveAddressInformation');
        }
    }
}