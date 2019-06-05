<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Helper;

use Magento\{
    Framework\App\Helper\AbstractHelper,
    Framework\App\Helper\Context,
    Cron\Model\Schedule,
    Cron\Model\ScheduleFactory,
    Framework\App\ObjectManager,
    Framework\Stdlib\DateTime\DateTime as MagentoDateTime,
    Framework\Stdlib\DateTime\TimezoneInterface
};
use Emarsys\Emarsys\{
    Model\EmarsysCronDetailsFactory,
    Model\Logs as Emarsyslogs
};

/**
 * Class Cron
 * @package Emarsys\Emarsys\Helper
 */
class Cron extends AbstractHelper
{
    const CRON_JOB_CUSTOMER_SYNC_QUEUE = 'emarsys_customer_sync_queue';

    const CRON_JOB_CUSTOMER_BULK_EXPORT_WEBDAV = 'emarsys_customer_bulk_export_webdav';

    const CRON_JOB_CUSTOMER_BULK_EXPORT_API = 'emarsys_customer_bulk_export_api';

    //subscribers related
    const CRON_JOB_SUBSCRIBERS_BULK_EXPORT_WEBDAV = 'emarsys_subscriber_bulk_export_webdav';

    const CRON_JOB_SUBSCRIBERS_BULK_EXPORT_API = 'emarsys_subscriber_bulk_export_api';

    //product related
    const CRON_JOB_CATALOG_BULK_EXPORT = 'emarsys_catalog_bulk_export';

    const CRON_JOB_CATALOG_SYNC = 'emarsys_product_sync';

    //smart insight related
    const CRON_JOB_SI_SYNC_QUEUE = 'emarsys_smartinsight_sync_queue';

    const CRON_JOB_SI_BULK_EXPORT = 'emarsys_smartinsight_bulk_export';

    /**
     * @var ScheduleFactory
     */
    protected $scheduleFactory;

    /**
     * @var EmarsysCronDetailsFactory
     */
    protected $emarsysCronDetails;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * @var MagentoDateTime
     */
    protected $dateTime;

    /**
     * @var TimezoneInterface
     */
    private $timezoneConverter;

    /**
     * Cron constructor.
     *
     * @param Context $context
     * @param ScheduleFactory $scheduleFactory
     * @param EmarsysCronDetailsFactory $emarsysCronDetails
     * @param Emarsyslogs $emarsysLogs
     * @param MagentoDateTime $dateTime
     * @param TimezoneInterface $timezoneConverter
     */
    public function __construct(
        Context $context,
        ScheduleFactory $scheduleFactory,
        EmarsysCronDetailsFactory $emarsysCronDetails,
        Emarsyslogs $emarsysLogs,
        MagentoDateTime $dateTime,
        TimezoneInterface $timezoneConverter = null
    ) {
        parent::__construct($context);
        $this->scheduleFactory = $scheduleFactory;
        $this->emarsysCronDetails = $emarsysCronDetails;
        $this->emarsysLogs = $emarsysLogs;
        $this->dateTime = $dateTime;
        $this->timezoneConverter = $timezoneConverter ?: ObjectManager::getInstance()->get(TimezoneInterface::class);
    }

    /**
     * Returns true if cron job is scheduled
     * @param $jobCode
     * @param null $storeId
     * @param null $websiteBasedChecking
     * @return bool
     */
    public function checkCronjobScheduled($jobCode, $storeId = null, $websiteBasedChecking = null)
    {
        try {
            $cronExist = false;
            $cronJobs = $this->scheduleFactory->create()->getCollection()
                ->addFieldToFilter('job_code', $jobCode)
                ->addFieldToFilter(
                    'status',
                    ['in' => [Schedule::STATUS_PENDING, Schedule::STATUS_RUNNING]]
                );

            $cronJobs->getSelect()->join(
                ['ecd' => $cronJobs->getTable('emarsys_cron_details')],
                'ecd.schedule_id = main_table.schedule_id',
                ['ecd.params']
            );

            if ($cronJobs->getSize()) {
                foreach ($cronJobs as $job) {
                    $jobsParams = \Zend_Json::decode($job->getParams(), true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \InvalidArgumentException('Unable to unserialize value.');
                    }

                    if (is_null($websiteBasedChecking)) {
                        $jobsStoreId = $jobsParams['storeId'];
                        if ($jobsStoreId == $storeId) {
                            if (!$cronExist) {
                                $cronExist = true;
                            }
                        }
                    } else {
                        $jobsWebsiteId = $jobsParams['website'];
                        if ($jobsWebsiteId == $websiteBasedChecking) {
                            if (!$cronExist) {
                                $cronExist = true;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                'CheckCronjobScheduled',
                $e->getMessage(),
                0,
                'Cron::checkCronjobScheduled()'
            );

            return false;
        }

        return $cronExist;
    }

    /**
     * Schedule new cron job
     *
     * @param $jobCode
     * @param null $storeId
     * @param null $websiteBasedChecking
     * @return Schedule
     * @throws \Exception
     */
    public function scheduleCronJob($jobCode, $storeId = null, $websiteBasedChecking = null)
    {
        try {
            $cronExist = false;
            $schedulerId = '';
            $cron = $this->scheduleFactory->create();

            $cronJobs = $this->scheduleFactory->create()->getCollection()
                ->addFieldToFilter('job_code', $jobCode)
                ->addFieldToFilter(
                    'status',
                    ['in' => [Schedule::STATUS_PENDING]]
                );

            $cronJobs->getSelect()->joinLeft(
                ['ecd' => $cronJobs->getTable('emarsys_cron_details')],
                'ecd.schedule_id = main_table.schedule_id',
                ['ecd.params']
            );

            if ($cronJobs->getSize()) {
                foreach ($cronJobs as $job) {
                    $jobsParams = \Zend_Json::decode($job->getParams());

                    if (is_null($websiteBasedChecking)) {
                        $jobsStoreId = $jobsParams['storeId'];
                        if ($jobsStoreId == $storeId) {
                            if (!$cronExist) {
                                $cronExist = true;
                                $schedulerId = $job->getScheduleId();
                            }
                        }
                    } else {
                        $jobsWebsiteId = $jobsParams['website'];
                        if ($jobsWebsiteId == $websiteBasedChecking) {
                            if (!$cronExist) {
                                $cronExist = true;
                                $schedulerId = $job->getScheduleId();
                            }
                        }
                    }
                }
                if ($cronExist && ($schedulerId != '')) {
                    $cron = $this->scheduleFactory->create()->load($schedulerId);
                }
            }
        } catch (\Exception $e) {
            $cron = $this->scheduleFactory->create();
        }

        $time = $this->timezoneConverter->date($this->dateTime->gmtTimestamp())->format('Y-m-d H:i');
        $time = strtotime($time);

        /** @var ScheduleFactory $cron */
        $result = $cron->setJobCode($jobCode)
            ->setCronExpr('* * * * *')
            ->setStatus(Schedule::STATUS_PENDING)
            ->setCreatedAt(strftime('%Y-%m-%d %H:%M:%S', $time))
            ->setScheduledAt(strftime('%Y-%m-%d %H:%M', $time + 60));

        $valid = $cron->trySchedule();

        if ($valid) {
            $cron->save();
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * @param $jobCode
     * @return $this|bool
     */
    public function getCurrentCronInformation($jobCode)
    {
        try {
            $cron = $this->scheduleFactory->create()->getCollection()
                ->addFieldToFilter('job_code', $jobCode)
                ->addFieldToFilter(
                    'status',
                    ['in' => [Schedule::STATUS_RUNNING]]
                )->getFirstItem();

            if ($cron && $cron->getId()) {
                $cronId = $cron->getId();
                $cronInfo = $this->emarsysCronDetails->create()->load($cronId);
                if ($cronInfo && $cronInfo->getId()) {
                    return $cronInfo;
                }
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                'getCurrentCronInformation',
                $e->getMessage(),
                0,
                'Cron::getCurrentCronInformation()'
            );
        }

        return false;
    }

    /**
     * @param $exportMode
     * @return array
     */
    public function getJobDetail($exportMode)
    {
        $jobDetails = [];

        switch ($exportMode) {
            case self::CRON_JOB_CUSTOMER_BULK_EXPORT_WEBDAV:
                $jobDetails['job_code'] = 'customer';
                $jobDetails['job_title'] = 'Customer Bulk Export';
                break;
            case self::CRON_JOB_CUSTOMER_BULK_EXPORT_API:
                $jobDetails['job_code'] = 'customer';
                $jobDetails['job_title'] = 'Customer Bulk Export';
                break;
            case self::CRON_JOB_SUBSCRIBERS_BULK_EXPORT_WEBDAV:
                $jobDetails['job_code'] = 'subscriber';
                $jobDetails['job_title'] = 'Subscriber Bulk Export';
                break;
            default:
                $jobDetails['job_code'] = 'customer';
                $jobDetails['job_title'] = 'Customer Bulk Export';
                break;
        }

        return $jobDetails;
    }

    /**
     * @param array $params
     * @return array
     */
    public function getFormattedParams($params = [])
    {
        if (isset($params['key'])) {
            unset($params['key']);
        }

        if (isset($params['form_key'])) {
            unset($params['form_key']);
        }

        return \Zend_Json::encode($params);
    }
}
