<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Productexport;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Request\Http;
use Emarsys\Emarsys\Helper\Data as EmarsysHelperData;
use Magento\Catalog\Model\ProductFactory;
use Emarsys\Emarsys\Model\ResourceModel\Product as ProductResourceModel;
use Emarsys\Emarsys\Helper\Cron as EmarsysCronHelper;
use Emarsys\Emarsys\Model\EmarsysCronDetails;
use Emarsys\Emarsys\Model\Logs;

/**
 * Class ProductExport
 * @package Emarsys\Emarsys\Controller\Adminhtml\Productexport
 */
class ProductExport extends Action
{
    /**
     * @var Http
     */
    protected $request;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var EmarsysHelperData
     */
    protected $emarsysDataHelper;

    /**
     * @var ProductFactory
     */
    protected $productCollectionFactory;

    /**
     * @var ProductResourceModel
     */
    protected $productResourceModel;

    /**
     * @var EmarsysCronHelper
     */
    protected $cronHelper;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * ProductExport constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Http $request
     * @param EmarsysHelperData $emarsysHelper
     * @param ProductFactory $productCollectionFactory
     * @param ProductResourceModel $productResourceModel
     * @param EmarsysCronHelper $cronHelper
     * @param EmarsysCronDetails $emarsysCronDetails
     * @param Logs $emarsysLogs
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Http $request,
        EmarsysHelperData $emarsysHelper,
        ProductFactory $productCollectionFactory,
        ProductResourceModel $productResourceModel,
        EmarsysCronHelper $cronHelper,
        EmarsysCronDetails $emarsysCronDetails,
        Logs $emarsysLogs
    ) {
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->emarsysDataHelper = $emarsysHelper;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productResourceModel = $productResourceModel;
        $this->cronHelper = $cronHelper;
        $this->emarsysCronDetails = $emarsysCronDetails;
        $this->emarsysLogs = $emarsysLogs;
        parent::__construct($context);
    }

    /**
     * product export action
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        try {
            //collect params
            $data = $this->request->getParams();
            $storeId = $data['storeId'];
            $store = $this->storeManager->getStore($storeId);
            $websiteId = $store->getWebsiteId();

            //check emarsys enabled for the website
            if ($this->emarsysDataHelper->getEmarsysConnectionSetting($websiteId)) {

                //check feed export enabled for the website
                if ($this->emarsysDataHelper->isCatalogExportEnabled($websiteId)) {
                    $productCollection = $this->productCollectionFactory->create()->getCollection()
                        ->addStoreFilter($storeId)
                        ->addWebsiteFilter($websiteId)
                        ->addAttributeToFilter('visibility', ["neq" => 1]);

                    //check product collection exist
                    if ($productCollection) {
                        //check product attributes mapping exist
                        $mappedAttributes = $this->productResourceModel->getMappedProductAttribute($storeId);
                        if (isset($mappedAttributes) && count($mappedAttributes) != '') {

                            $isCronjobScheduled = $this->cronHelper->checkCronjobScheduled(EmarsysCronHelper::CRON_JOB_CATALOG_BULK_EXPORT, $storeId);
                            if (!$isCronjobScheduled) {
                                //no cron job scheduled yet, schedule a new cron job
                                $cron = $this->cronHelper->scheduleCronjob(EmarsysCronHelper::CRON_JOB_CATALOG_BULK_EXPORT, $storeId);

                                //format and encode data in json to be saved in the table
                                $params = $this->cronHelper->getFormattedParams($data);

                                //save details cron details table
                                $this->emarsysCronDetails->addEmarsysCronDetails($cron->getScheduleId(), $params);

                                $this->messageManager->addSuccessMessage(
                                    __(
                                        'A cron named "%1" have been scheduled to export products for the store %2.',
                                        EmarsysCronHelper::CRON_JOB_CATALOG_BULK_EXPORT,
                                        $store->getName()
                                    ));
                            } else {
                                //cron job already scheduled
                                $this->messageManager->addErrorMessage(__('A cron is already scheduled to export products for the store %1 ', $store->getName()));
                            }
                        } else {
                            //no products attribute mapping found for this store
                            $this->messageManager->addErrorMessage(__('Product Attributes are not mapped for the store %1 ', $store->getName()));
                        }
                    } else {
                        //no products found for this store
                        $this->messageManager->addErrorMessage(__('No Product found for the store %1 ', $store->getName()));
                    }
                } else {
                    //catalog feed export is disabled for this website
                    $this->messageManager->addErrorMessage(__('Catalog Feed Export is Disabled for the store %1.', $store->getName()));
                }
            } else {
                //emarsys is disabled for this website
                $this->messageManager->addErrorMessage(__('Emarsys is disabled for the website %1', $websiteId));
            }
        } catch (\Exception $e) {
            //add exception to logs
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'ProductExport::execute()'
            );
            //report error
            $this->messageManager->addErrorMessage(__('There was a problem while product export. %1', $e->getMessage()));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $url = $this->getUrl("emarsys_emarsys/productexport/index", ["store" => $storeId]);

        return $resultRedirect->setPath($url);
    }
}
