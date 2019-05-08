<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Cron;

use Emarsys\Emarsys\Model\Product as EmarsysProductModel;
use Emarsys\Emarsys\Helper\Cron as EmarsysCronHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Emarsys\Emarsys\Model\Logs;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ProductBulkExport
 * @package Emarsys\Emarsys\Cron
 */
class ProductBulkExport
{
    /**
     * @var EmarsysCronHelper
     */
    protected $cronHelper;

    /**
     * @var JsonHelper
     */
    protected $jsonHelper;

    /**
     * @var EmarsysProductModel
     */
    protected $emarsysProductModel;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager ;

    /**
     * ProductBulkExport constructor.
     *
     * @param EmarsysCronHelper $cronHelper
     * @param JsonHelper $jsonHelper
     * @param EmarsysProductModel $emarsysProductModel
     * @param Logs $emarsysLogs
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        EmarsysCronHelper $cronHelper,
        JsonHelper $jsonHelper,
        EmarsysProductModel $emarsysProductModel,
        Logs $emarsysLogs,
        StoreManagerInterface $storeManager
    ) {
        $this->cronHelper = $cronHelper;
        $this->jsonHelper = $jsonHelper;
        $this->emarsysProductModel =  $emarsysProductModel;
        $this->emarsysLogs = $emarsysLogs;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        try {
            set_time_limit(0);
            $currentCronInfo = $this->cronHelper->getCurrentCronInformation(
                \Emarsys\Emarsys\Helper\Cron::CRON_JOB_CATALOG_BULK_EXPORT
            );

            if ($currentCronInfo) {
                $data = $this->jsonHelper->jsonDecode($currentCronInfo->getParams());
                $includeBundle = $data['includeBundle'];
                $excludedCategories = $data['excludeCategories'];

                $this->emarsysProductModel->consolidatedCatalogExport(\Emarsys\Emarsys\Helper\Data::ENTITY_EXPORT_MODE_MANUAL, $includeBundle, $excludedCategories);
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'ProductBulkExport::execute()'
            );
        }
    }
}
