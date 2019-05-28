<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Setup;

use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Class InstallData
 * @package Emarsys\Emarsys\Setup
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var CustomerSetupFactory
     */
    protected $customerSetupFactory;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * InstallData constructor.
     *
     * @param CustomerSetupFactory $customerSetupFactory
     * @param AttributeSetFactory $attributeSetFactory
     * @param ProductMetadataInterface $productMetadata
     * @param ResourceConnection $ResourceConnection
     */
    public function __construct(
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory,
        ProductMetadataInterface $productMetadata,
        ResourceConnection $ResourceConnection
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->productMetadata = $productMetadata;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->ResourceConnection = $ResourceConnection;
    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var CustomerSetup $customerSetup */
        $connection = $this->ResourceConnection->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        $table = $this->ResourceConnection->getTableName('emarsys_magento_events');
        if ($this->productMetadata->getEdition() == 'Enterprise') {
            $connection->query("INSERT INTO $table (`magento_event`,`config_path`) VALUES
			('Email Header','design/email/header_template'),
			('Email Footer','design/email/footer_template'),
			('Contact Form','contact/email/email_template'),
			('Credit Memo Update', 'sales_email/creditmemo_comment/template'),
			('Credit Memo Update for Guest', 'sales_email/creditmemo_comment/guest_template'),
			('Forgot Admin Password', 'admin/emails/forgot_email_template'),
			('Forgot Password', 'customer/password/forgot_email_template'),
			('Gift Registry Sharing', 'magento_giftregistry/sharing_email/template'),
			('Gift Registry Update', 'magento_giftregistry/update_email/template'),
			('Invoice Update', 'sales_email/invoice_comment/template'),
			('Invoice Update for Guest', 'sales_email/invoice_comment/guest_template'),
			('New Credit Memo', 'sales_email/creditmemo/template'),
			('New Credit Memo for Guest', 'sales_email/creditmemo/guest_template'),
			('New Invoice', 'sales_email/invoice/template'),
			('New Invoice for Guest', 'sales_email/invoice/guest_template'),
			('New Order', 'sales_email/order/template'),
			('New Order for Guest', 'sales_email/order/guest_template'),
			('New RMA', 'sales_email/magento_rma/template'),
			('New RMA for Guest', 'sales_email/magento_rma/guest_template'),
			('New Shipment', 'sales_email/shipment/template'),
			('New Shipment for Guest', 'sales_email/shipment/guest_template'),
			('New Account', 'customer/create_account/email_template'),
			('New Account Confirmation Key', 'customer/create_account/email_confirmation_template'),
			('New Account Confirmed', 'customer/create_account/email_confirmed_template'),
			('Newsletter Subscription Confirmation', 'newsletter/subscription/confirm_email_template'),
			('Newsletter Subscription Success', 'newsletter/subscription/success_email_template'),
			('Newsletter Unsubscribe Success', 'newsletter/subscription/un_email_template'),
			('Order Update', 'sales_email/order_comment/template'),
			('Order Update For Guest', 'sales_email/order_comment/guest_template'),
			('RMA Admin Comments', 'sales_email/magento_rma_comment/template'),
			('RMA Admin Comments for Guest', 'sales_email/magento_rma_comment/guest_template'),
			('RMA Authorization', 'sales_email/magento_rma_auth/template'),
			('RMA Authorization for Guest', 'sales_email/magento_rma_auth/guest_template'),
			('RMA Customer Comments', 'sales_email/magento_rma_customer_comment/template'),
			('Remind Password', 'customer/password/remind_email_template'),
			('Rewards Points Balance Update', 'magento_reward/notification/balance_update_template'),
			('Rewards Points Expiry Warning', 'magento_reward/notification/expiry_warning_template'),
			('Send Product to a Friend', 'sendfriend/email/template'),
			('Share Wish List', 'wishlist/email/email_template'),
			('Shipment Update', 'sales_email/shipment_comment/template'),
			('Shipment Update for Guest', 'sales_email/shipment_comment/guest_template'),
			('Store Credit Update', 'customer/magento_customerbalance/email_template');");
        } else {
            $connection->query("INSERT INTO $table (`magento_event`,`config_path`) VALUES
			('Email Header','design/email/header_template'),
			('Email Footer','design/email/footer_template'),
			('Contact Form','contact/email/email_template'),
			('Credit Memo Update', 'sales_email/creditmemo_comment/template'),
			('Credit Memo Update for Guest', 'sales_email/creditmemo_comment/guest_template'),
			('Forgot Admin Password', 'admin/emails/forgot_email_template'),
			('Forgot Password', 'customer/password/forgot_email_template'),
			('Invoice Update', 'sales_email/invoice_comment/template'),
			('Invoice Update for Guest', 'sales_email/invoice_comment/guest_template'),
			('New Credit Memo', 'sales_email/creditmemo/template'),
			('New Credit Memo for Guest', 'sales_email/creditmemo/guest_template'),
			('New Invoice', 'sales_email/invoice/template'),
			('New Invoice for Guest', 'sales_email/invoice/guest_template'),
			('New Order', 'sales_email/order/template'),
			('New Order for Guest', 'sales_email/order/guest_template'),
			('New Shipment', 'sales_email/shipment/template'),
			('New Shipment for Guest', 'sales_email/shipment/guest_template'),
			('New Account', 'customer/create_account/email_template'),
			('New Account Confirmation Key', 'customer/create_account/email_confirmation_template'),
			('New Account Confirmed', 'customer/create_account/email_confirmed_template'),
			('Newsletter Subscription Confirmation', 'newsletter/subscription/confirm_email_template'),
			('Newsletter Subscription Success', 'newsletter/subscription/success_email_template'),
			('Newsletter Unsubscribe Success', 'newsletter/subscription/un_email_template'),
			('Order Update', 'sales_email/order_comment/template'),
			('Order Update For Guest', 'sales_email/order_comment/guest_template'),
			('Remind Password', 'customer/password/remind_email_template'),
			('Send Product to a Friend', 'sendfriend/email/template'),
			('Share Wish List', 'wishlist/email/email_template'),
			('Shipment Update', 'sales_email/shipment_comment/template'),
			('Shipment Update for Guest', 'sales_email/shipment_comment/guest_template');");
        }
    }
}
