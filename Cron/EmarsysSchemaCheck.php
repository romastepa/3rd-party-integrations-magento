<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Cron;

use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Model\Api\Api as EmarsysApiApi;
use Emarsys\Emarsys\Helper\Event;
use Emarsys\Emarsys\Model\Logs;

/**
 * Class EmarsysSchemaCheck
 * @package Emarsys\Emarsys\Cron
 */
class EmarsysSchemaCheck
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var EmarsysApiApi
     */
    protected $api;

    /**
     * @var Event
     */
    protected $eventHelper;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * EmarsysSchemaCheck constructor.
     * @param StoreManagerInterface $storeManager
     * @param EmarsysApiApi $api
     * @param Event $eventHelper
     * @param Logs $emarsysLogs
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        EmarsysApiApi $api,
        Event $eventHelper,
        Logs $emarsysLogs
    ) {
        $this->storeManager = $storeManager;
        $this->api = $api;
        $this->eventHelper = $eventHelper;
        $this->emarsysLogs = $emarsysLogs;
    }

    public function execute()
    {
        try {
            $websites = $this->storeManager->getWebsites();
            foreach ($websites as $website) {
                $websiteId = $website->getWebsiteId();
                $emarsysApiIds = [];
                $this->api->setWebsiteId($websiteId);
                $response = $this->api->sendRequest('GET', 'event');

                if ($response['body']['data']) {
                    foreach ($response['body']['data'] as $eventInfo)
                        $emarsysApiIds[] = $eventInfo['id'];
                }

                $emarsysLocalIds = $this->eventHelper->getLocalEmarsysEvents($websiteId);
                $result = array_diff($emarsysApiIds, $emarsysLocalIds);

                if (count($result)) {
                    $this->eventHelper->saveEmarsysEventSchemaNotification();
                    break;
                }
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'EmarsysSchemaCheck::execute()'
            );
        }
    }
}
