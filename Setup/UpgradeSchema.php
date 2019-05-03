<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2019 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class UpgradeSchema
 * @package Emarsys\Emarsys\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    const EMARSYS_CRON_SUPPORT_TABLE = 'emarsys_cron_details';

    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '1.0.6', '<')) {
            $this->createEmarsysCronTable($setup);
            $this->removeDataFromCoreConfigData($setup);
        }

        if (version_compare($context->getVersion(), "1.0.7", "<")) {
            $tableName = $setup->getTable('emarsys_product_export');
            $connection = $setup->getConnection();
            if ($connection->isTableExists($tableName) == false) {
                $table = $connection
                    ->newTable($tableName)
                    ->addColumn(
                        'entity_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                        'Product Id'
                    )->addColumn(
                        'params',
                        \Magento\Framework\DB\Ddl\Table::TYPE_BLOB,
                        '64k',
                        [],
                        'Product Params'
                    )
                    ->setComment('Catalog Product Export');
                $setup->getConnection()->createTable($table);
            }
        }

        if (version_compare($context->getVersion(), "1.0.14", "<")) {
            $tableName = $setup->getTable('emarsys_log_details');
            $connection = $setup->getConnection();
            if ($connection->isTableExists($tableName) == false) {
                //Create table 'emarsys_log_details'
                $table = $setup->getConnection()
                    ->newTable($setup->getTable('emarsys_log_details'))
                    ->addColumn(
                        'id',
                        Table::TYPE_INTEGER,
                        null,
                        ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                        'Id'
                    )
                    ->addColumn(
                        'log_exec_id',
                        Table::TYPE_INTEGER,
                        10,
                        ['unsigned' => true, 'nullable' => false],
                        'schedule_id'
                    )
                    ->addColumn(
                        'created_at',
                        Table::TYPE_DATETIME,
                        null,
                        ['nullable' => false],
                        'created_at'
                    )
                    ->addColumn(
                        'emarsys_info',
                        Table::TYPE_TEXT,
                        255,
                        ['nullable' => false],
                        'messages'
                    )
                    ->addColumn(
                        'description',
                        Table::TYPE_TEXT,
                        2000,
                        ['nullable' => false],
                        'description'
                    )
                    ->addColumn(
                        'action',
                        Table::TYPE_TEXT,
                        255,
                        ['nullable' => false],
                        'action'
                    )
                    ->addColumn(
                        'log_action',
                        Table::TYPE_TEXT,
                        255,
                        ['nullable' => false],
                        'log_action'
                    )
                    ->addColumn(
                        'before_change',
                        Table::TYPE_TEXT,
                        2000,
                        ['nullable' => false],
                        'before_change'
                    )
                    ->addColumn(
                        'after_change',
                        Table::TYPE_TEXT,
                        2000,
                        ['nullable' => false],
                        'after_change'
                    )
                    ->addColumn(
                        'message_type',
                        Table::TYPE_TEXT,
                        255,
                        ['nullable' => false],
                        'message_type'
                    )
                    ->addColumn(
                        'store_id',
                        Table::TYPE_INTEGER,
                        11,
                        ['nullable' => false],
                        'store_id'
                    )
                    ->addColumn(
                        'website_id',
                        Table::TYPE_INTEGER,
                        11,
                        ['nullable' => false],
                        'website_id'
                    );
                $setup->getConnection()->createTable($table);
            }

            $tableName = $setup->getTable('emarsys_log_cron_schedule');
            if ($connection->isTableExists($tableName) == false) {
                // Create table 'emarsys_log_cron_schedule'
                $table = $setup->getConnection()
                    ->newTable($setup->getTable('emarsys_log_cron_schedule'))
                    ->addColumn(
                        'id',
                        Table::TYPE_INTEGER,
                        null,
                        ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                        'Id'
                    )
                    ->addColumn(
                        'job_code',
                        Table::TYPE_TEXT,
                        255,
                        ['nullable' => false],
                        'job_code'
                    )
                    ->addColumn(
                        'status',
                        Table::TYPE_TEXT,
                        7,
                        ['nullable' => false],
                        'status'
                    )
                    ->addColumn(
                        'messages',
                        Table::TYPE_TEXT,
                        2000,
                        ['nullable' => false],
                        'messages'
                    )
                    ->addColumn(
                        'created_at',
                        Table::TYPE_DATETIME,
                        null,
                        ['nullable' => false],
                        'created_at'
                    )
                    ->addColumn(
                        'executed_at',
                        Table::TYPE_DATETIME,
                        null,
                        ['nullable' => false],
                        'executed_at'
                    )
                    ->addColumn(
                        'finished_at',
                        Table::TYPE_DATETIME,
                        null,
                        ['nullable' => false],
                        'finished_at'
                    )
                    ->addColumn(
                        'run_mode',
                        Table::TYPE_TEXT,
                        255,
                        ['nullable' => false],
                        'run_mode'
                    )
                    ->addColumn(
                        'auto_log',
                        Table::TYPE_TEXT,
                        2000,
                        ['nullable' => false],
                        'auto_log'
                    )
                    ->addColumn(
                        'store_id',
                        Table::TYPE_INTEGER,
                        11,
                        ['nullable' => false],
                        'store_id'
                    );
                $setup->getConnection()->createTable($table);
            }

            $connection->truncateTable($setup->getTable('emarsys_log_details'));
            $connection->truncateTable($setup->getTable('emarsys_log_cron_schedule'));

            $connection->changeColumn(
                $setup->getTable('emarsys_log_details'),
                'log_exec_id',
                'log_exec_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => false,
                    'length'   => 10,
                    'comment' => 'emarsys_log_cron_schedule id',
                ]
            );

            $connection->addIndex(
                $setup->getTable('emarsys_log_details'),
                $setup->getIdxName($setup->getTable('emarsys_log_details'), 'log_exec_id'),
                ['log_exec_id']
            );

            $connection->addForeignKey(
                $setup->getFkName(
                    $setup->getTable('emarsys_log_details'),
                    'log_exec_id',
                    $setup->getTable('emarsys_log_cron_schedule'),
                    'id'
                ),
                $setup->getTable('emarsys_log_details'),
                'log_exec_id',
                $setup->getTable('emarsys_log_cron_schedule'),
                'id'
            );

            $this->removeDataFromCoreConfigData($setup);
        }

        $setup->endSetup();
    }

    /**
     * @param $setup
     */
    protected function removeDataFromCoreConfigData($setup)
    {
        $paths = [
            'crontab/default/jobs/emarsys_sync/schedule/cron_expr',
            'crontab/default/jobs/shcema_check/schedule/cron_expr',
            'crontab/default/jobs/emarsys_smartinsight_sync/schedule/cron_expr',
            'crontab/default/jobs/emarsys_productexport_sync/schedule/cron_expr',
            'crontab/default/jobs/emarsys_smartinsight_sync_queue/schedule/cron_expr',
        ];

        foreach ($paths as $path) {
            $setup->getConnection()
                ->delete($setup->getTable('core_config_data'), "path='$path'");
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    protected function createEmarsysCronTable(SchemaSetupInterface $setup)
    {
        /**
         * Create table 'emarsys_cron_details'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable(self::EMARSYS_CRON_SUPPORT_TABLE))
            ->addColumn(
                'schedule_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Schedule Id'
            )
            ->addColumn(
                'params',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Params'
            )
            ->setComment('Emarsys Cron Support Table');
        $setup->getConnection()->createTable($table);
    }
}
