<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Model\LogScheduleFactory;
use Emarsys\Emarsys\Model\LogsFactory;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Registry;

/**
 * Class Logs
 * @package Emarsys\Emarsys\Helper
 */
class Logs extends AbstractHelper
{
    /**
     * @var LogScheduleFactory
     */
    protected $logScheduleFactory;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @var LogsFactory
     */
    protected $logsFactory;

    /**
     * @var null
     */
    protected $cronSchedule = null;

    /**
     * Logs constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param LogScheduleFactory $logScheduleFactory
     * @param LogsFactory $logsFactory
     * @param DateTime $date
     * @param StateInterface $inlineTranslation
     * @param TransportBuilder $transportBuilder
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        LogScheduleFactory $logScheduleFactory,
        LogsFactory $logsFactory,
        DateTime $date,
        StateInterface $inlineTranslation,
        TransportBuilder $transportBuilder,
        Registry $registry
    ) {
        $this->logScheduleFactory = $logScheduleFactory;
        $this->date = $date;
        $this->registry = $registry;
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->storeManager = $storeManager;
        $this->scopeConfigInterface = $context->getScopeConfig();
        $this->logsFactory = $logsFactory;
        parent::__construct($context);
    }

    /**
     * @param array $logsArray
     * @return string
     */
    public function manualLogs($logsArray = [], $exportCron = 0)
    {
        if (!$this->cronSchedule || $exportCron) {
            $this->cronSchedule = $this->logScheduleFactory->create();
        }
        try {
            if (isset($logsArray['job_code'])) {
                $this->cronSchedule->setJobCode($logsArray['job_code']);
            }

            if (isset($logsArray['schedule_id'])) {
                $this->cronSchedule->setScheduleId($logsArray['schedule_id']);
            }

            if (isset($logsArray['status'])) {
                $this->cronSchedule->setStatus($logsArray['status']);
            }

            if (isset($logsArray['messages'])) {
                $this->cronSchedule->setMessages($logsArray['messages']);
            }

            if (isset($logsArray['created_at'])) {
                $this->cronSchedule->setCreatedAt($logsArray['created_at']);
            }

            if (isset($logsArray['executed_at'])) {
                $this->cronSchedule->setExecutedAt($logsArray['executed_at']);
            }

            if (isset($logsArray['finished_at'])) {
                $this->cronSchedule->setFinishedAt($logsArray['finished_at']);
            }

            if (isset($logsArray['run_mode'])) {
                $this->cronSchedule->setRunMode($logsArray['run_mode']);
            }

            if (isset($logsArray['auto_log'])) {
                $this->cronSchedule->setAutoLog($logsArray['auto_log']);
            }

            if (isset($logsArray['store_id'])) {
                $this->cronSchedule->setStoreId($logsArray['store_id']);
            }

            $this->cronSchedule->save();
            return $this->cronSchedule->getId();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Update latest saving manual log record in cron scheduler
     * @param array $logsArray
     * @return string
     */
    public function manualLogsUpdate($logsArray = [])
    {
        $collection = $this->logScheduleFactory->create()->getCollection();
        $collection->addFieldToFilter('id', $logsArray['id']);
        $result = $collection->getFirstItem();

        if (null !== $result->getId()) {
            $collection = $this->logScheduleFactory->create()->load($logsArray['id']);
            try {
                if (isset($logsArray['status'])) {
                    $collection->setStatus($logsArray['status']);
                }

                if (isset($logsArray['messages'])) {
                    $collection->setMessages($logsArray['messages']);
                }

                if (isset($logsArray['scheduled_at'])) {
                    $collection->setScheduledAt($logsArray['scheduled_at']);
                }

                if (isset($logsArray['executed_at'])) {
                    $collection->setExecutedAt($logsArray['executed_at']);
                }

                if (isset($logsArray['finished_at'])) {
                    $collection->setFinishedAt($logsArray['finished_at']);
                }

                $collection->save();
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        } else {
            return "Log Record not found";
        }
    }

    /**
     * For saving Logs
     * @param array $logsArray
     */
    public function logs($logsArray = [])
    {
        $currentDate = $this->date->date('Y-m-d H:i:s', time());
        $schedulerId = @$logsArray['id'];

        $logsModel = $this->logsFactory->create();
        $logsModel->setLogExecId($schedulerId)
            ->setCreatedAt($currentDate)
            ->setEmarsysInfo(@$logsArray['emarsys_info'])
            ->setDescription(@$logsArray['description'])
            ->setAction(isset($logsArray['action']) ? $logsArray['action'] : 'synced to emarsys')
            ->setMessageType(@$logsArray['message_type'])
            ->setStoreId(@$logsArray['store_id'])
            ->setWebsiteId(isset($logsArray['website_id']) ? $logsArray['website_id'] : 0)
            ->setLogAction(isset($logsArray['log_action']) ? $logsArray['log_action'] : 'sync');

        $logsModel->save();

        $websiteId = isset($logsArray['website_id']) ? $logsArray['website_id'] : 0;
        if ($websiteId == 0) {
            $scopeType = 'default';
        } else {
            $scopeType = 'websites';
        }

        $sendLogReport = $this->scopeConfigInterface->getValue('logs/log_setting/log_report', $scopeType, $websiteId);
        if ($sendLogReport == '' && $websiteId == 0) {
            $sendLogReport = $this->scopeConfigInterface->getValue('logs/log_setting/log_report');
        }
        if ($sendLogReport && $logsArray['message_type'] == 'Error') {
            if ($sendLogReport) {
                $title = ucfirst($logsArray['job_code']) . " : Store - " . $logsArray['store_id'];
                $description = $logsArray['description'];
                $this->errorLogEmail($title, $description);
            }
        }
    }

    /**
     * @param $title
     * @param $errorMsg
     */
    public function errorLogEmail($title, $errorMsg)
    {
        $customerVar = 'emarsys_send_email';
        if ($this->registry->registry($customerVar) == 'sent') {
            return;
        }
        $this->registry->register($customerVar, 'sent');

        $sendLogReport = $this->scopeConfigInterface->getValue('logs/log_setting/log_report');
        $emailReceipient = $this->scopeConfigInterface->getValue('logs/log_setting/log_email_recipient');
        $explodedemailReceipient = explode(",", $emailReceipient);

        if ($sendLogReport == 1) {
            $templateOptions = [
                'area' => \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
                'store' => $this->storeManager->getStore()->getId()];
            $templateVars = [
                'store' => $this->storeManager->getStore(),
                'sync_name' => $title,
                'message' => $errorMsg
            ];
            $from = [
                'email' => "support@emarsys.com",
                'name' => "Support"
            ];
            $this->inlineTranslation->suspend();

            if (is_array($explodedemailReceipient)) {
                for ($i = 0; $i < count($explodedemailReceipient); $i++) {
                    $to = $explodedemailReceipient[$i];
                    $transport = $this->_transportBuilder->setTemplateIdentifier('error_log_email_template')
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($to)
                        ->getTransport();
                    $transport->sendMessage();
                }
            }
            $this->inlineTranslation->resume();
        }
    }

    /**
     * @param $logId
     * @param $mesage
     * @param $websiteId
     */
    public function childLogs($logId, $mesage, $websiteId)
    {
        try {
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Backgroud Time Based Optin Sync Success';
            $logsArray['description'] = $mesage;
            $logsArray['action'] = 'Backgroud Time Based Optin Sync';
            $logsArray['message_type'] = 'Success';
            $logsArray['log_action'] = 'True';
            $logsArray['website_id'] = $websiteId;
            $logsArray['store_id'] = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
            $this->logs($logsArray);
        } catch (\Exception $e) {
            $this->_logger->debug($e->getMessage());
        }
    }
}
