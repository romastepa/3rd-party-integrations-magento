<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Setup;

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
