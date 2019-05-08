<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml\Support;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Backend\Block\Widget\Context;
use Emarsys\Emarsys\Helper\Data;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\AdminNotification\Model\Inbox;
use Magento\AdminNotification\Model\ResourceModel\Inbox\Collection;
use Magento\AdminNotification\Model\InboxFactory;
use Emarsys\Emarsys\Model\Logs;
use Magento\Backend\Block\Widget\Form as MagentoBackendBlockWidgetForm;
use Magento\Framework\Notification\MessageInterface;

/**
 * Class About
 * @package Emarsys\Emarsys\Block\Adminhtml\Support
 */
class About extends MagentoBackendBlockWidgetForm
{
    CONST NOTIFICATION_TITLE = 'Emarsys Connector Version LATEST_VERSION is now available';

    CONST NOTIFICATION_DESCRIPTION = 'This update addressing various reported issues / enhancements. <a href="RELEASE_URL" target="_blank">Click Here</a> for more information';

    /**
     * @var Data
     */
    protected $emarsysHelper;

    /**
     * @var Inbox
     */
    private $adminNotification;

    /**
     * @var Collection
     */
    private $notificationCollectionFactory;

    /**
     * @var InboxFactory
     */
    private $inboxFactory;

    /**
     * @var JsonHelper
     */
    private $jsonHelper;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * About constructor.
     *
     * @param Context $context
     * @param Data $emarsysHelper
     * @param JsonHelper $jsonHelper
     * @param Inbox $adminNotification
     * @param Collection $notificationCollectionFactory
     * @param InboxFactory $inboxFactory
     * @param DateTime $date
     * @param Logs $emarsysLogs
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $emarsysHelper,
        JsonHelper $jsonHelper,
        Inbox $adminNotification,
        Collection $notificationCollectionFactory,
        InboxFactory $inboxFactory,
        DateTime $date,
        Logs $emarsysLogs,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->emarsysHelper = $emarsysHelper;
        $this->jsonHelper = $jsonHelper;
        $this->adminNotification = $adminNotification;
        $this->notificationCollectionFactory = $notificationCollectionFactory;
        $this->inboxFactory = $inboxFactory;
        $this->date = $date;
        $this->emarsysLogs = $emarsysLogs;
        $this->storeManager = $context->getStoreManager();
        $this->scopeConfigInterface = $context->getScopeConfig();
        $this->setTemplate('about.phtml');
    }

    /**
     * Get Emarsys Version Information
     * @return array
     */
    public function getEmarsysVersionInfo()
    {
        try {
            $installedModuleVersion = $this->emarsysHelper->getEmarsysVersion();
            $latestAvailableVersion = 'N/A';
            $emarsysVersionInfo = [
                'latest' => $latestAvailableVersion,
                'installed' => $installedModuleVersion,
            ];
            $notificationMessage = '';
            $errorStatus = false;
            $emarsysLatestVersionInfo = $this->emarsysHelper->getEmarsysLatestVersionInfo();
            $emarsysLatestVersionInfo = $this->jsonHelper->jsonDecode($emarsysLatestVersionInfo, TRUE);

            if (!empty($emarsysLatestVersionInfo)) {
                if (isset($emarsysLatestVersionInfo['tag_name'])) {
                    $latestAvailableVersion = $emarsysLatestVersionInfo['tag_name'];

                    switch (version_compare($installedModuleVersion, $latestAvailableVersion)) {
                        case 0:
                            $notificationMessage .= "<div style='color:green; text-align: center; padding: 15px; border: 1px solid #ddd;background: #fff;font-size: 16px;margin:  8px 0;'>Congratulations, You are using the latest version of this Extension </div> ";
                            break;
                        case -1:
                            $title = str_replace('LATEST_VERSION', $latestAvailableVersion, self::NOTIFICATION_TITLE);
                            $description = str_replace('RELEASE_URL', $this->getReadMoreUrl(), self::NOTIFICATION_DESCRIPTION);
                            $dateAdded = $this->date->date('Y-m-d H:i:s', strtotime('-7 days'));

                            $adminNotification = $this->notificationCollectionFactory->addFieldToFilter('title', ['eq' => $title])
                                ->addFieldToFilter('date_added', ['gteq' => $dateAdded])
                                ->setOrder('date_added', 'DESC');

                            if (count($adminNotification) == 0) {
                                $this->adminNotification->add(MessageInterface::SEVERITY_NOTICE, $title, $description, $this->getReadMoreUrl(), true);
                            } else {
                                foreach ($adminNotification as $notification) {
                                    $notification = $this->inboxFactory->create()->load($notification->getNotificationId());
                                    if ($notification->getNotificationId()) {
                                        $notification->setIsRead(0);
                                        $notification->setIsRemove(0);
                                        $notification->save();
                                        break;
                                    }
                                }
                            }
                            break;
                        default:
                            $notificationMessage .= "<div style='color:red; text-align: center; padding: 15px; border: 1px solid red;background: #fff;font-size: 16px;margin:  8px 0;'>Sorry, Something Seems Wrong With The Installed Module Version.</div> ";
                    }
                } else {
                    $errorStatus = true;
                    $notificationMessage .= "<div style='color:red; text-align: center; padding: 15px; border: 1px solid red;background: #fff;font-size: 16px;margin:  8px 0;'>Sorry, No Latest Release Found</div> ";
                    $this->emarsysLogs->addErrorLog(
                        $emarsysLatestVersionInfo['message'],
                        $this->storeManager->getStore()->getId(),
                        'getEmarsysVersionInfo()'
                    );
                }
            }

            $emarsysVersionInfo = [
                'latest' => $latestAvailableVersion,
                'installed' => $installedModuleVersion,
            ];
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'getEmarsysVersionInfo()'
            );
        }

        return [
            'version_details' => $emarsysVersionInfo,
            'notification' => $notificationMessage,
            'error_status' => $errorStatus
        ];
    }

    /**
     * Get read More URL
     * @return mixed
     */
    public function getReadMoreUrl()
    {
        return $this->scopeConfigInterface->getValue(Data::EMARSYS_RELEASE_URL, 'default', 0);
    }
}

