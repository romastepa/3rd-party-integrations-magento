<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\System\Backend\Customer;

/**
 * Class Cron
 * @package Emarsys\Emarsys\Model\System\Backend\Customer
 */
class Cron extends \Magento\Framework\App\Config\Value
{
    /**
     * Cron string path
     */
    const CRON_STRING_PATH = 'crontab/default/jobs/emarsys_croncustomersync/schedule/cron_expr';

    /**r
     * Cron model path
     */
    const CRON_MODEL_PATH = 'crontab/default/jobs/emarsys_croncustomersync/run/model';

    /**
     * @var \Magento\Framework\App\Config\ValueFactory
     */
    protected $_configValueFactory;

    /**
     * @var string
     */
    protected $_runModelPath = '';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param string $runModelPath
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        $runModelPath = '',
        array $data = []
    ) {
        $this->_runModelPath = $runModelPath;
        $this->_scopeConfig = $config;
        $this->_configValueFactory = $configValueFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     * @throws \Exception
     */
    public function afterSave()
    {
        $time = $this->getData('groups/emarsys_emarsys/fields/background_runtime/value');
        $frequency = $this->getData('groups/emarsys_emarsys/background_frequency/value');
        $frequencyHourly = $this->getData('groups/emarsys_emarsys/fields/background_frequency/value');

        $frequencyDaily = \Magento\Cron\Model\Config\Source\Frequency::CRON_DAILY;
        $frequencyWeekly = \Magento\Cron\Model\Config\Source\Frequency::CRON_WEEKLY;
        $frequencyMonthly = \Magento\Cron\Model\Config\Source\Frequency::CRON_MONTHLY;
        $timehour = (int)$time[0];
        $timeminute = (int)$time[1];

        if ($time[0] > 0) {
            $cronExprArray = [
                intval($time[1]),                                   # Minute
                intval($time[0]),                                   # Hour
                ($frequency == $frequencyMonthly) ? '1' : '*',      # Day of the Month
                '*',                                                # Month of the Year
                ($frequency == $frequencyWeekly) ? '1' : '*',       # Day of the Week
            ];
        } else {
            $cronExprArray = [
                '*/' . intval($time[1]),                                  # Minute
                '*',                                                    # Hour
                ($frequency == $frequencyMonthly) ? '1' : '*',          # Day of the Month
                '*',                                                    # Month of the Year
                ($frequency == $frequencyWeekly) ? '1' : '*',           # Day of the Week
            ];
        }

        if ($frequencyHourly == "0") {
            if ($timehour) {
                $cronExprString = "0 */" . ltrim($timehour, '0') . " * * * ";
            } elseif ($timeminute && $timehour == 0) {
                $cronExprString = "*/" . ltrim($timeminute, '0') . " * * * * ";
            } else {
                $cronExprString = "* * * * *";
            }
        } else {
            $cronExprString = join(' ', $cronExprArray);
        }

        try {
            $this->_configValueFactory->create()->load(
                self::CRON_STRING_PATH,
                'path'
            )->setValue(
                $cronExprString
            )->setPath(
                self::CRON_STRING_PATH
            )->save();
            $this->_configValueFactory->create()->load(
                self::CRON_MODEL_PATH,
                'path'
            )->setValue(
                $this->_runModelPath
            )->setPath(
                self::CRON_MODEL_PATH
            )->save();
        } catch (\Exception $e) {
            throw new \Exception(__('We can\'t save the cron expression.'));
        }
        return parent::afterSave();
    }
}
