<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2019 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class InstallSchema
 * @package Emarsys\Emarsys\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();
        //Create table 'emarsys_order_export_status'
        if ($connection->isTableExists($installer->getTable('emarsys_order_export_status')) == false) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('emarsys_order_export_status'))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                    'Id'
                )
                ->addColumn(
                    'order_id',
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Order Id'
                )
                ->addColumn(
                    'exported',
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Exported'
                )
                ->addColumn(
                    'status_code',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Status Code'
                )
                ->setComment('Magento Order Export Status');
            $installer->getConnection()->createTable($table);
        }

        if ($connection->isTableExists($installer->getTable('emarsys_creditmemo_export_status')) == false) {
            //Create table 'emarsys_creditmemo_export_status'
            $table = $installer->getConnection()
                ->newTable($installer->getTable('emarsys_creditmemo_export_status'))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                    'Id'
                )
                ->addColumn(
                    'order_id',
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Order Id'
                )
                ->addColumn(
                    'exported',
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Exported'
                )
                ->addColumn(
                    'status_code',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Status Code'
                )
                ->setComment('Magento Credit Memo Export Status');
            $installer->getConnection()->createTable($table);
        }
        //Create table 'emarsys_order_queue'
        if ($connection->isTableExists($installer->getTable('emarsys_order_queue')) == false) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('emarsys_order_queue'))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                    'Id'
                )
                ->addColumn(
                    'entity_id',
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Entity Id'
                )
                ->addColumn(
                    'website_id',
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Website Id'
                )
                ->addColumn(
                    'store_id',
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Store Id'
                )
                ->addColumn(
                    'entity_type_id',
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Entity Type Id'
                )
                ->setComment('Entity type id');
            $installer->getConnection()->createTable($table);
        }
        $installer->endSetup();
    }
}
