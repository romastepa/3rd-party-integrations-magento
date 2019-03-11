<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Emarsys\Emarsys\Model\ProductFactory;
use Emarsys\Emarsys\Model\ResourceModel\Product\CollectionFactory;
use Emarsys\Emarsys\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Model\Logs;
use Emarsys\Emarsys\Helper\Logs as EmarsysHelperLogs;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Emarsys\Emarsys\Model\ResourceModel\Product;

/**
 * Class SaveRecommended
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Product
 */
class SaveRecommended extends \Magento\Backend\App\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var Product
     */
    protected $resourceModelProduct;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * SaveRecommended constructor.
     * @param Context $context
     * @param Attribute $eavAttribute
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param ProductFactory $productFactory
     * @param CollectionFactory $productAttributeCollection
     * @param Data $emarsysHelper
     * @param StoreManagerInterface $storeManager
     * @param Logs $emarsysLogs
     * @param EmarsysHelperLogs $logHelper
     * @param DateTime $date
     * @param Product $resourceModelProduct
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        Attribute $eavAttribute,
        ScopeConfigInterface $scopeConfigInterface,
        ProductFactory $productFactory,
        CollectionFactory $productAttributeCollection,
        Data $emarsysHelper,
        StoreManagerInterface $storeManager,
        Logs $emarsysLogs,
        EmarsysHelperLogs $logHelper,
        DateTime $date,
        Product $resourceModelProduct,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->eavAttribute = $eavAttribute;
        $this->session = $context->getSession();
        $this->resultPageFactory = $resultPageFactory;
        $this->_storeManager = $storeManager;
        $this->date = $date;
        $this->logHelper = $logHelper;
        $this->emarsysHelper = $emarsysHelper;
        $this->emarsysLogs = $emarsysLogs;
        $this->productFactory = $productFactory;
        $this->resourceModelProduct = $resourceModelProduct;
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->productAttributeCollection = $productAttributeCollection;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('store')) {
            $storeId = $this->getRequest()->getParam('store');
        } else {
            $storeId = $this->emarsysHelper->getFirstStoreId();
        }
        $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
        try {
            $recommendedArray = [];
            $logsArray['job_code'] = 'Product Mapping';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'Running Update Schema';
            $logsArray['description'] = 'Started Recommended Mapping';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['website_id'] = $websiteId;
            $logsArray['store_id'] = $storeId;
            $logId = $this->logHelper->manualLogs($logsArray);
            $model = $this->productFactory->create();
            /**
             *Here We need set the recommended attribute values
             */
            $data = $this->resourceModelProduct->getProductAttributeLabelId($storeId);

            $recommendedData = [
                'sku' => ['emarsys_attr_code' => $data[0]],
                'name' => ['emarsys_attr_code' => $data[1]],
                'url_key' => ['emarsys_attr_code' => $data[2]],
                'image' => ['emarsys_attr_code' => $data[3]],
                'category_ids' => ['emarsys_attr_code' => $data[4]],
                'price' => ['emarsys_attr_code' => $data[5]]
            ];

            //Remove existing data
            $this->resourceModelProduct->deleteRecommendedMappingExistingAttr($recommendedData, $storeId);
            foreach ($recommendedData as $key => $value) {
                $mappedAttributeCode = $this->productAttributeCollection->create()
                    ->addFieldToFilter('magento_attr_code', ['eq' => $key])
                    ->addFieldToFilter('store_id', ['eq' => $storeId])
                    ->getFirstItem()
                    ->getEmarsysAttrCode();
                if ($key == '' || $mappedAttributeCode) {
                    continue;
                }
                $recommendedArray[] = $value['magento_attr_code'] = $key;
                $model = $this->productFactory->create();
                $model = $model->setData($value);
                $model->setStoreId($storeId);
                $model->save();
            }
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Saved Recommended Mapping';
            $logsArray['action'] = 'Saved Recommended Mapping';
            $logsArray['message_type'] = 'Success';
            $logsArray['description'] = 'Saved Recommended Mapping as ' . print_r($recommendedArray, true);
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['log_action'] = 'True';
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Product Recommended Mapping Saved Successfully';
            $this->logHelper->logs($logsArray);
            $this->logHelper->manualLogs($logsArray);
            $this->messageManager->addSuccessMessage("Recommended Product attributes mapped successfully");
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'Save Recommended(Product)');
            $this->messageManager->addErrorMessage("Error occurred while mapping Product attribute");
        }
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setRefererOrBaseUrl();
    }
}
