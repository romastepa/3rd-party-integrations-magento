<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model;

use Magento\Cron\Model\ScheduleFactory;
use Magento\Cron\Model\Schedule;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;

/**
 * Class EmarsysCronDetails
 * @package Emarsys\Emarsys\Model
 */
class EmarsysCronDetails extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var ScheduleFactory
     */
    protected $scheduleFactory;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager ;

    /**
     * EmarsysCronDetails constructor.
     * @param Context $context
     * @param ScheduleFactory $scheduleFactory
     * @param Logs $emarsysLogs
     * @param StoreManagerInterface $storeManager
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        ScheduleFactory $scheduleFactory,
        Logs $emarsysLogs,
        StoreManagerInterface $storeManager,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->scheduleFactory = $scheduleFactory;
        $this->emarsysLogs = $emarsysLogs;
        $this->storeManager = $storeManager;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Emarsys\Emarsys\Model\ResourceModel\EmarsysCronDetails');
    }

    /**
     * @param $scheduleId
     * @param $params
     */
    public function addEmarsysCronDetails($scheduleId, $params)
    {
        $this->setScheduleId($scheduleId)
            ->setParams($params)
            ->save();
    }

    /**
     * clear emarsys cron details table
     */
    public function clearEmarsysCronDetails()
    {
        try {
            $itemIdsToRemove = [];

            //get collection of successful, missed and error crons
            $processedCronJobs = $this->scheduleFactory->create()->getCollection()
                ->addFieldToSelect('schedule_id')
                ->addFieldToFilter(
                    'status',
                    ['in' => [Schedule::STATUS_SUCCESS, Schedule::STATUS_MISSED, Schedule::STATUS_ERROR]]
                )->join(
                    ['ecd' => $this->getResource()->getTable('emarsys_cron_details')],
                    'ecd.schedule_id = main_table.schedule_id',
                    []
                );

            if ($processedCronJobs->getSize()) {
                foreach ($processedCronJobs as $job) {
                    array_push($itemIdsToRemove, $job->getScheduleId());
                }
            }

            //get collection of that are already removed from cron_schedule table
            $clearedCronJobs = $this->scheduleFactory->create()->getCollection()->addFieldToSelect('schedule_id');
            $clearedCronJobs->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS)->joinRight(
                ['ecd' => $this->getResource()->getTable('emarsys_cron_details')],
                'ecd.schedule_id = main_table.schedule_id',
                ['ecd.schedule_id']
            )->where('main_table.schedule_id is null');

            if ($clearedCronJobs->getSize()) {
                foreach ($clearedCronJobs as $job) {
                    array_push($itemIdsToRemove, $job->getScheduleId());
                }
            }

            //remove items from emarsys cron details table
            if (!empty($itemIdsToRemove)) {
                $cronDetailsItemsToRemove = $this->getCollection()
                    ->addFieldToFilter('schedule_id', ['in' => $itemIdsToRemove]);
                foreach ($cronDetailsItemsToRemove as $item) {
                    $item->delete();
                }
            }

            return true;
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'EmarsysCronDetails::clearEmarsysCronDetails()'
            );
        }

        return false;
    }
}
