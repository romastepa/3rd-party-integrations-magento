<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block;

use Emarsys\Emarsys\Helper\Data;
use Emarsys\Emarsys\Model\Logs;
use Emarsys\Emarsys\Model\ResourceModel\Customer;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Checkout\Model\CartFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Store\Model\ScopeInterface;

/**
 * Class JavascriptTracking
 * @package Emarsys\Emarsys\Block
 */
class JavascriptTracking extends \Magento\Framework\View\Element\Template
{
    /**
     * @var CartFactory
     */
    protected $cartFactory;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var Success
     */
    protected $orderSuccess;

    /**
     * @var Customer
     */
    protected $customerResourceModel;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var Data
     */
    protected $emarsysHelper;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var OrderItemCollectionFactory
     */
    protected $orderItemCollectionFactory;

    /**
     * JavascriptTracking constructor.
     *
     * @param Context $context
     * @param Customer $customerResourceModel
     * @param CartFactory $cartFactory
     * @param OrderFactory $orderFactory
     * @param Http $request
     * @param CategoryFactory $categoryFactory
     * @param ProductFactory $productFactory
     * @param CustomerSession $customerSession
     * @param Data $emarsysHelper
     * @param Logs $emarsysLogs
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Customer $customerResourceModel,
        CartFactory $cartFactory,
        OrderFactory $orderFactory,
        Http $request,
        CategoryFactory $categoryFactory,
        ProductFactory $productFactory,
        CustomerSession $customerSession,
        Data $emarsysHelper,
        Logs $emarsysLogs,
        Registry $registry,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        array $data = []
    ) {
        $this->storeManager = $context->getStoreManager();
        $this->cartFactory = $cartFactory;
        $this->orderFactory = $orderFactory;
        $this->_request = $request;
        $this->customerResourceModel = $customerResourceModel;
        $this->categoryFactory = $categoryFactory;
        $this->productFactory = $productFactory;
        $this->customerSession = $customerSession;
        $this->emarsysHelper = $emarsysHelper;
        $this->emarsysLogs = $emarsysLogs;
        $this->coreRegistry = $registry;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Get Page Handle
     *
     * @return string
     */
    public function getPageHandle()
    {
        return $handle = $this->_request->getFullActionName();
    }

    /**
     * Get Current Category
     *
     * @return string
     */
    public function getCurrentCategory()
    {
        $result = false;
        try {
            $category = $this->coreRegistry->registry('current_category');

            if (isset($category) && $category != '') {
                $categoryName = '';
                $categoryPath = $category->getPath();
                $categoryPathIds = explode('/', $categoryPath);
                $childCats = [];
                if (count($categoryPathIds) > 2) {
                    $pathIndex = 0;
                    foreach ($categoryPathIds as $categoryPathId) {
                        if ($pathIndex <= 1) {
                            $pathIndex++;

                            continue;
                        }
                        $childCat = $this->categoryFactory->create()->setStoreId($this->storeManager->getDefaultStoreView()->getId())->load($categoryPathId);
                        $childCats[] = $childCat->getName();
                    }
                    $categoryName = implode(" > ", $childCats);
                }

                $result = addcslashes($categoryName, "'");
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'getCurrentCategory()'
            );
        }

        return $result;
    }

    /**
     * Get Current Product Sku
     *
     * @return string
     */
    public function getCurrentProductSku()
    {
        $result = false;
        try {
            $product = $this->coreRegistry->registry('current_product');
            if (isset($product) && $product != '') {
                $uniqueIdentifier = $this->emarsysHelper->getUniqueIdentifier($this->storeManager->getStore()->getId());

                if ($uniqueIdentifier == "product_id") {
                    $productIdentifier = $product->getId();
                } else {
                    $productIdentifier = addslashes($product->getSku());
                }

                $result =  $productIdentifier;
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'getCurrentProductSku()'
            );
        }

        return $result;
    }

    /**
     * Get Page Handle From Db
     *
     * @return array
     */
    public function getPageHandleStatus()
    {
        $websiteId = $this->getWebsiteId();
        $scope = ScopeInterface::SCOPE_WEBSITES;
        $handle = $this->_request->getParam('full_action_name');
        if (!$handle) {
            $handle = $this->getPageHandle();
        }
        $pageResult = [];

        $pageHandles = [
            'cms_index_index' => Data::XPATH_CMS_INDEX_INDEX,
            'catalog_category_view' => Data::XPATH_CATALOG_CATEGORY_VIEW,
            'catalog_product_view' => Data::XPATH_CATALOG_PRODUCT_VIEW,
            'checkout_cart_index' => Data::XPATH_CHECKOUT_CART_INDEX,
            'checkout_onepage_success' => Data::XPATH_CHECKOUT_ONEPAGE_SUCCESS,
            'catalogsearch_result_index' => Data::XPATH_CATALOGSEARCH_RESULT_INDEX
        ];

        if (array_key_exists($handle, $pageHandles)) {
            $jsStatus = $this->getJsEnableStatusForAllPages();
            if ($jsStatus == 1) {
                $path = $pageHandles[$handle];
                $pageValue = $this->customerResourceModel->getDataFromCoreConfig($path, $scope, $websiteId);
                if ($pageValue == '' && $websiteId == 1) {
                    $pageValue = $this->customerResourceModel->getDataFromCoreConfig($path);
                }
                $pageData = explode('||', $pageValue);
                $pageResult['logic'] = $pageData[0];
                $pageResult['templateId'] = $pageData[1];
                $pageResult['status'] = 'Valid';
            } else {
                $pageResult['status'] = 'Invalid';
            }
        } else {
            $pageResult['status'] = 'Invalid';
        }

        return $pageResult;
    }

    /**
     * Get Search Param
     *
     * @return bool|mixed
     */
    public function getSearchResult()
    {
        $result = false;
        try {
            $q = $this->_request->getParam('q');
            if ($q != '') {
                $result =  $q;
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'getSearchResult()'
            );
        }

        return $result;
    }

    /**
     * Get Ajax Update Url
     *
     * @return string
     */
    public function getAjaxUpdateUrl()
    {
        return $this->getUrl(
            'emarsys/index/ajaxupdate',
            ['_secure' => true]
        );
    }

    /**
     * Get Merchant Id from DB
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMerchantId()
    {
        return $this->storeManager->getStore()->getConfig(Data::XPATH_WEBEXTEND_MERCHANT_ID);
    }

    /**
     * Get Status of Web Extended Javascript integration from DB
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getJsEnableStatusForAllPages()
    {
        return (bool)$this->storeManager->getStore()->getConfig(Data::XPATH_WEBEXTEND_JS_TRACKING_ENABLED);
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isTestModeEnabled()
    {
        return (bool)$this->storeManager->getStore()->getConfig(Data::XPATH_WEBEXTEND_MODE);
    }

    /**
     * Get All Items of the Cart
     *
     * @return mixed
     */
    public function getAllCartItems()
    {
        return $this->cartFactory->create()->getQuote()->getAllItems();
    }

    /**
     * Load Product By ID
     *
     * @param $id
     * @return $this
     */
    public function getLoadProduct($id)
    {
        return $this->productFactory->create()->load($id);
    }

    /**
     * Get Order Information
     *
     * @return array|bool
     */
    public function getOrderData()
    {
        try {
            $orderIds = $this->customerSession->getWebExtendNewOrderIds();

            if (empty($orderIds) || !is_array($orderIds)) {
                return false;
            }

            $taxIncluded = $this->emarsysHelper->isIncludeTax();
            $useBaseCurrency = $this->emarsysHelper->isUseBaseCurrency();
            $result = [];

            foreach ($orderIds as $_orderId) {
                $order = $this->orderFactory->create()->load($_orderId);
                $orderData = [];
                foreach ($order->getAllVisibleItems() as $item) {
                    $qty = $item->getQtyOrdered();
                    $product = $this->getLoadProduct($item->getProductId());

                    if (($item->getProductType() == \Magento\Bundle\Model\Product\Type::TYPE_CODE) && (!$product->getPriceType())) {
                        $collection = $this->orderItemCollectionFactory->create()
                            ->addAttributeToFilter('parent_item_id', ['eq' => $item['item_id']])
                            ->load();
                        $bundleBaseDiscount = 0;
                        $bundleDiscount = 0;
                        foreach ($collection as $collPrice) {
                            $bundleBaseDiscount += $collPrice['base_discount_amount'];
                            $bundleDiscount += $collPrice['discount_amount'];
                        }
                        if ($taxIncluded) {
                            $price = $useBaseCurrency ? ($item->getBaseRowTotal() + $item->getBaseTaxAmount()) - ($bundleBaseDiscount) : ($item->getRowTotal() + $item->getTaxAmount()) - ($bundleDiscount);
                        } else {
                            $price = $useBaseCurrency ? $item->getBaseRowTotal() - $bundleBaseDiscount : $item->getRowTotal() - $bundleDiscount;
                        }
                    } else {
                        if ($taxIncluded) {
                            $price = $useBaseCurrency ? ($item->getBaseRowTotal()  + $item->getBaseTaxAmount()) - $item->getBaseDiscountAmount() : ($item->getRowTotal() + $item->getTaxAmount()) - $item->getDiscountAmount();
                        } else {
                            $price = $useBaseCurrency ? $item->getBaseRowTotal() - $item->getBaseDiscountAmount() : $item->getRowTotal() - $item->getDiscountAmount();
                        }
                    }

                    $uniqueIdentifier = $this->emarsysHelper->getUniqueIdentifier($this->storeManager->getStore()->getId());
                    if ($uniqueIdentifier == "product_id") {
                        $sku = $item->getProductId();
                    } else {
                        $sku = addslashes($item->getSku());
                    }
                    $orderData[] = [
                        'item' => addslashes($sku),
                        'price' => $price,
                        'quantity' => $qty
                    ];
                }
                $result[] = [
                    'orderId' => $order->getIncrementId(),
                    'items' => $orderData,
                    'email' => $order->getCustomerEmail(),
                ];
            }

            if (!empty($result)) {
                $this->customerSession->setWebExtendNewOrderIds([]);
                return \Zend_Json::encode($result);
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'getOrderData()'
            );
        }

        return false;
    }

    /**
     * Get Website Id
     *
     * @return int
     */
    public function getWebsiteId()
    {
        return $this->storeManager->getStore()->getWebsiteId();
    }

    /**
     * Get Cart Items Data in Json Format
     *
     * @return bool|string
     */
    public function getCartItemsJsonData()
    {
        $returnData = false;
        try {
            $allItems = $this->getAllCartItems();
            $useBaseCurrency = $this->emarsysHelper->isUseBaseCurrency();

            if ($allItems != "") {
                $jsData = [];
                foreach ($allItems as $item) {
                    if ($item->getParentItemId()) {
                        continue;
                    }
                    $productSku = $this->getLoadProduct($item->getProductId())->getSku();
                    $price = $useBaseCurrency ? $item->getBaseRowTotal() : $item->getRowTotal();
                    $uniqueIdentifier = $this->emarsysHelper->getUniqueIdentifier($this->storeManager->getStore()->getId());

                    if ($uniqueIdentifier == "product_id") {
                        $sku = $item->getProductId();
                    } else {
                        $sku = addslashes($item->getSku());
                    }
                    $qty = $item->getQty();
                    $jsData[] = "{item: '" . addslashes($sku) . "', price: $price, quantity: $qty}";
                }

                $returnData = implode($jsData, ',');
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'getCartItemsJsonData()'
            );
        }

        return $returnData;
    }

    /**
     * Get Customer Id
     *
     * @return bool|string
     */
    public function getCustomerId()
    {
        try {
            if ($this->customerSession->isLoggedIn()) {
                return $this->getLoggedInCustomerEmail();
            } else {
                $customerId = $this->customerSession->getWebExtendCustomerId();

                if (!empty($customerId)) {
                    $this->customerSession->setWebExtendCustomerId('');
                    $this->customerSession->setWebExtendCustomerEmail('');

                    return $customerId;
                }
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'getCustomerId'
            );
        }

        return false;
    }

    /**
     * Get logged in customer email
     *
     * @return bool|string
     */
    public function getLoggedInCustomerEmail()
    {
        $loggedInCustomerEmail = false;

        if ($this->customerSession->isLoggedIn()) {
            $customer = $this->customerSession->getCustomer();
            $loggedInCustomerEmail = addslashes($customer->getEmail());
        }

        return $loggedInCustomerEmail;
    }

    /**
     * Get customer email
     *
     * @return string
     */
    public function getCustomerEmailAddress()
    {
        $customerEmail = false;

        try {
            $sessionEmail = $this->customerSession->getWebExtendCustomerEmail();

            if ($this->customerSession->isLoggedIn()) {
                $loggedInCustomerEmail = $this->getLoggedInCustomerEmail();
                if (\Zend_Validate::is($loggedInCustomerEmail, 'EmailAddress')) {
                    $customerEmail = $loggedInCustomerEmail;
                }
            } elseif (isset($sessionEmail)) {
                if (!empty($sessionEmail) && \Zend_Validate::is($sessionEmail, 'EmailAddress')) {
                    $customerEmail = $sessionEmail;
                }
            }
            $this->customerSession->setWebExtendCustomerEmail('');
            $this->customerSession->setWebExtendCustomerId('');
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'getCustomerEmailAddress()'
            );
        }

        return $customerEmail;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreSlug()
    {
        return $this->storeManager->getStore()->getCode();
    }
}
