<?php

namespace Emarsys\Emarsys\Controller\Index;

use Emarsys\Emarsys\Helper\Data;
use Emarsys\Emarsys\Model\Logs;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;

class Sync extends \Magento\Framework\App\Action\Action
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Data
     */
    protected $emarsysHelper;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * Sync constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Http $request
     * @param Data $emarsysHelper
     * @param Logs $emarsysLogs
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Http $request,
        Data $emarsysHelper,
        Logs $emarsysLogs
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->emarsysHelper = $emarsysHelper;
        $this->request = $request;
        $this->emarsysLogs = $emarsysLogs;
    }

    /**
     * Sync action
     */
    public function execute()
    {
        try {
            $notificationSecretKey = $this->scopeConfig->getValue('contacts_synchronization/emarsys_emarsys/notification_secret_key');
            if ($this->request->getParam('secret') == $notificationSecretKey) {
                $websiteIds = explode(',', $this->getRequest()->getParam('website_ids'));
                foreach ($websiteIds as $websiteId) {
                    $this->emarsysHelper->importSubscriptionUpdates($websiteId, true);
                }
            } else {
                $this->emarsysLogs->addErrorLog('Unauthorized Access', 0, 'sync action index');
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), 0, 'sync action index');
        }
    }
}
