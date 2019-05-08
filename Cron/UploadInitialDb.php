<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Cron;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Config\Model\ResourceModel\Config;
use Emarsys\Emarsys\Helper\Cron as EmarsysCronHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Emarsys\Emarsys\Model\WebDav\WebDav;
use Emarsys\Emarsys\Model\Logs;

/**
 * Class UploadInitialDb
 * @package Emarsys\Emarsys\Cron
 */
class UploadInitialDb
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var EmarsysCronHelper
     */
    protected $cronHelper;

    /**
     * @var JsonHelper
     */
    protected $jsonHelper;

    /**
     * @var WebDav
     */
    protected $webDavModel;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * UploadInitialDb constructor.
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param EmarsysCronHelper $cronHelper
     * @param JsonHelper $jsonHelper
     * @param WebDav $webDavModel
     * @param Logs $emarsysLogs
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Config $config,
        EmarsysCronHelper $cronHelper,
        JsonHelper $jsonHelper,
        WebDav $webDavModel,
        Logs $emarsysLogs
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->cronHelper = $cronHelper;
        $this->jsonHelper = $jsonHelper;
        $this->webDavModel = $webDavModel;
        $this->emarsysLogs = $emarsysLogs;
    }

    public function execute()
    {
        try {
            $currentCronInfo = $this->cronHelper->getCurrentCronInformation(EmarsysCronHelper::CRON_JOB_INITIAL_DB_LOAD);

            if (!$currentCronInfo) return;

            $data = $this->jsonHelper->jsonDecode($currentCronInfo->getParams());

            //if the default configuration is set then the website id won't come
            if (!isset($data['website']) || $data['website'] == 0) {
                foreach ($this->storeManager->getStores(false) as $storeData) {
                    $data['store'] = $storeData->getId();
                    $data['website'] = $storeData->getWebsiteId();
                    $this->webDavModel->syncFullContactUsingWebDav(EmarsysCronHelper::CRON_JOB_INITIAL_DB_LOAD, $data);
                }
                $scopeType = 'default';
                $websiteId = 0;
            } else {
                foreach ($this->storeManager->getStores(false) as $storeData) {
                    if ($data['website'] == $storeData->getWebsiteId()) {
                        $data['store'] = $storeData->getId();
                        $this->webDavModel->syncFullContactUsingWebDav(EmarsysCronHelper::CRON_JOB_INITIAL_DB_LOAD, $data);
                    }
                }
                $scopeType = 'websites';
                $websiteId = $data['website'];
            }

            $initialLoad = $data['initial_load'];
            $attribute = $data['attribute'];
            $attributeValue = $data['attributevalue'];
            $this->config->saveConfig('contacts_synchronization/initial_db_load/initial_db_load', $initialLoad, $scopeType, $websiteId);
            $this->config->saveConfig('contacts_synchronization/initial_db_load/attribute', $attribute, $scopeType, $websiteId);
            $this->config->saveConfig('contacts_synchronization/initial_db_load/attribute_value', $attributeValue, $scopeType, $websiteId);

        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'UploadInitialDb::execute()'
            );
        }
    }
}
