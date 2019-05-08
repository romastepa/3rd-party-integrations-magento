<?php

namespace Emarsys\Emarsys\Controller\Index;

use Magento\Framework\App\Action\Context;

class Sync extends \Magento\Framework\App\Action\Action
{
    /**
     * Sync constructor.
     *
     * @param Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Emarsys\Emarsys\Helper\Data $emarsysHelper
     * @param \Emarsys\Emarsys\Model\Logs $emarsysLogs
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request,
        \Emarsys\Emarsys\Helper\Data $emarsysHelper,
        \Emarsys\Emarsys\Model\Logs $emarsysLogs,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->emarsysHelper = $emarsysHelper;
        $this->request = $request;
        $this->emarsysLogs = $emarsysLogs;
        $this->storeManager = $storeManager;
    }

    /**
     * Sync action
     */
    public function execute()
    {
        $storeId = $this->storeManager->getStore()->getId();
        try {
            $notificationSecretKey = $this->scopeConfig->getValue('contacts_synchronization/emarsys_emarsys/notification_secret_key');

            if ($this->request->getParam('secret') == $notificationSecretKey) {
               $isTimeBased = $this->getRequest()->getParam('timebased');
                if (!isset($isTimeBased)) {
                    $isTimeBased = '';
                }
                try {
                    $websiteIds = explode(',', $this->getRequest()->getParam('website_ids'));
                    if ($isTimeBased == 1) {
                        $this->emarsysHelper->importSubscriptionUpdates($websiteIds, true);
                    }
                    $this->emarsysLogs->addErrorLog('ok', $storeId, 'sync action index');

                } catch (\Exception $e) {
                    $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'sync action index');
                }
            } else {
                $this->emarsysLogs->addErrorLog('Unauthorized Access', $storeId, 'sync action index');
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'sync action index');
        }
    }
}
