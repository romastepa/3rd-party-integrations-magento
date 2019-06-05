<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
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

        //Create table 'emarsys_contact_field'
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_contact_field'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )->addColumn(
                'emarsys_field_id',
                Table::TYPE_INTEGER,
                150,
                [],
                'emarsys_contact_field id'
            )->addColumn(
                'name',
                Table::TYPE_TEXT,
                150,
                ['nullable' => false],
                'Name of the field'
            )
            ->addColumn(
                'type',
                Table::TYPE_TEXT,
                null,
                ['unsigned' => true],
                'Type whether choice or input'
            )->addColumn(
                'string_id',
                Table::TYPE_TEXT,
                null,
                ['unsigned' => true],
                'string key'
            )
            ->addColumn(
                'language_code',
                Table::TYPE_TEXT,
                150,
                ['nullable' => false],
                'en or nl etc'
            )->addColumn(
                'store_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Store Id'
            )
            ->setComment('Contact Fields');
        $installer->getConnection()->createTable($table);

        //Create table 'magento_event_templates'
        $table = $installer->getConnection()
            ->newTable($installer->getTable('magento_event_templates'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'magento_event_template_name',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Magento Event Template Name'
            )
            ->addColumn(
                'emarsys_event_template',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Emarsys Event Template'
            )
            ->addColumn(
                'store_id',
                Table::TYPE_INTEGER,
                null,
                [],
                'Store Id'
            )
            ->setComment('Magento Event Templates');

        $installer->getConnection()->createTable($table);

        //Create table 'emarsys_variable_mapping'
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_variable_mapping'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'magento_variable',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Magento Variable'
            )
            ->addColumn(
                'magento_event_template',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Magento Event Template'
            )
            ->addColumn(
                'emarsys_variable',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Emarsys Variable'
            )
            ->addColumn(
                'store_id',
                Table::TYPE_INTEGER,
                null,
                [],
                'Store Id'
            )
            ->setComment('Emarsys Variable Mapping');

        $installer->getConnection()->createTable($table);

        //Create table 'emarsys_contact_field_option'
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_contact_field_option'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'ID'
            )
            ->addColumn(
                'field_id',
                Table::TYPE_INTEGER,
                150,
                [],
                'emarsys_customer_fields fk'
            )
            ->addColumn(
                'option_id',
                Table::TYPE_INTEGER,
                '64k',
                [],
                'Emarsys Option Id'
            )
            ->addColumn(
                'option_name',
                Table::TYPE_TEXT,
                '64k',
                [],
                'Emarsys Option Name'
            )->addColumn(
                'store_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Store Id'
            )
            ->setComment('Emarsys options related to choice fields');
        $installer->getConnection()->createTable($table);

        //Create table 'emarsys_magento_customer_field_mapping'
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_customer_option_mapping'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'magento_option_attr_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Magento option id fk'
            )
            ->addColumn(
                'emarsys_option_id',
                Table::TYPE_INTEGER,
                null,
                [],
                'emarsys option fk'
            )
            ->addColumn(
                'store_id',
                Table::TYPE_INTEGER,
                null,
                [],
                'Store Id'
            )
            ->setComment('Emarsys Magento field mapping');

        $installer->getConnection()->createTable($table);

        //Create table 'emarsys_magento_customer_field_mapping'
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_customer_field_mapping'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'magento_attribute_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Magento customer attribute id fk'
            )
            ->addColumn(
                'emarsys_contact_field',
                Table::TYPE_INTEGER,
                null,
                [],
                'emarsys customer fields fk'
            )
            ->addColumn(
                'magento_custom_attribute_id',
                Table::TYPE_INTEGER,
                null,
                [],
                'Magento Custom Attribute Id'
            )
            ->addColumn(
                'store_id',
                Table::TYPE_INTEGER,
                null,
                [],
                'Store Id'
            )
            ->setComment('Emarsys Magento field mapping');
        $installer->getConnection()->createTable($table);

        //Create table 'emarsys_magento_order_field_mapping'
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_order_field_mapping'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'magento_column_name',
                Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Magento Tables Column Names'
            )
            ->addColumn(
                'emarsys_order_field',
                Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Emarsys Order Fields'
            )
            ->addColumn(
                'store_id',
                Table::TYPE_INTEGER,
                null,
                [],
                'Store Id'
            )
            ->setComment('Emarsys Magento order mapping');
        $installer->getConnection()->createTable($table);

        //Create table 'emarsys_option_mapping'
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_option_mapping'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'magento_option_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Code'
            )
            ->addColumn(
                'emarsys_option_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Label'
            )->addColumn(
                'emarsys_field_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'emarsys_field_id'
            )
            ->addColumn(
                'store_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Store Id'
            )
            ->setComment('magento emarsys option mapping');
        $installer->getConnection()->createTable($table);

        // Create table 'emarsys_product_sync_temp'
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_product_sync_temp'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )->addColumn(
                'emarsys_inventory_id',
                Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Emarsys inventory Id'
            )->addColumn(
                'magento_sku',
                Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Magento Sku'
            )->addColumn(
                'magento_id',
                Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Magento Id'
            )->addColumn(
                'magento_lastsyncdate',
                Table::TYPE_DATETIME,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Magento Last Sync Date'
            )->addColumn(
                'emarsys_lastsyncdate',
                Table::TYPE_DATETIME,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Emarsys Last Sync Date'
            )->addColumn(
                'store_id',
                Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Store Id'
            )->addColumn(
                'entity_ref',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Entity Reference'
            )->addColumn(
                'flg',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Flag'
            );

        $installer->getConnection()->createTable($table);

        //Create table 'emarsys_product_mapping'
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_product_mapping'))
            ->addColumn(
                'emarsys_contact_field',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'magento_attr_code',
                Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Magento Attribute Code'
            )->addColumn(
                'emarsys_attr_code',
                Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Emarsys Attribute Code'
            )->addColumn(
                'sync_direction',
                Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Sync Direction'
            )->addColumn(
                'store_id',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Store Id'
            )
            ->setComment('emarsys product mapping');
        $installer->getConnection()->createTable($table);

        //Create table 'emarsys_emarsys_product_attributes'
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_emarsys_product_attributes'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'code',
                Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Code'
            )
            ->addColumn(
                'label',
                Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Label'
            )
            ->addColumn(
                'field_type',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Field Type'
            )->addColumn(
                'store_id',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Store Id'
            )
            ->setComment('emarsys emarsys product attributes');
        $installer->getConnection()->createTable($table);

        // Create table 'emarsys_custom_product_attributes'
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_custom_product_attributes'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )->addColumn(
                'attributeid',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Attribute Id'
            )->addColumn(
                'description',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Description'
            )->addColumn(
                'controlltype',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Controll Type'
            )->addColumn(
                'store_id',
                Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Store Id'
            )
            ->setComment('Emarsys Custom Product Attributes');
        $installer->getConnection()->createTable($table);

        //Create table 'emarsys_events'
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_events'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )->addColumn(
                'event_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Event Id'
            )->addColumn(
                'emarsys_event',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Emarsys Event'
            )->addColumn(
                'store_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Store Id'
            )
            ->setComment('Emarsys Events');
        $installer->getConnection()->createTable($table);

        //Create table 'emarsys_magento_events' for magento events
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_magento_events'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'ID'
            )->addColumn(
                'magento_event',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Magento Event'
            )->addColumn(
                'config_path',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Magento Template Configuration path'
            )
            ->setComment('Magento Events');
        $installer->getConnection()->createTable($table);

        //Create table 'emarsys_event_mapping' for mapping emarsys magento events with emarsys events.
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_event_mapping'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'ID'
            )->addColumn(
                'magento_event_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Magento Event ID'
            )->addColumn(
                'emarsys_event_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Emarsys Event ID'
            )
            ->addColumn(
                'store_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Store Id'
            )
            ->setComment('Emarsys Event Mapping');

        $installer->getConnection()->createTable($table);

        //Create table 'emarsys_order_export_status'
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

        //Create table 'emarsys_order_queue'
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

        //Create Table 'emarsys_magento_customer_attributes'
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_magento_customer_attributes'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'attribute_code',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Attribute Code'
            )
            ->addColumn(
                'attribute_code_custom',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Attribute Code Custom'
            )
            ->addColumn(
                'frontend_label',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Frontend Label'
            )
            ->addColumn(
                'entity_type_id',
                Table::TYPE_INTEGER,
                null,
                [],
                'Entity Type Id'
            )->addColumn(
                'store_id',
                Table::TYPE_INTEGER,
                null,
                [],
                'Store Id'
            );
        $installer->getConnection()->createTable($table);

        // create table 'emarsys_suite2_queue'
        $emarsys_suite2_queue = $installer->getTable('emarsys_suite2_queue');
        $table = $installer->getConnection()->newTable($installer->getTable($emarsys_suite2_queue))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'ID'
            )->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => NULL],
                'Entity ID'
            )->addColumn(
                'website_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => NULL],
                'Website ID'
            )->addColumn(
                'store_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => NULL],
                'Store ID'
            )->addColumn(
                'entity_type_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => NULL],
                'Entity type ID'
            )->addColumn(
                'params',
                Table::TYPE_TEXT,
                '2M',
                ['default' => null],
                'Extra parameters'
            )->addColumn(
                'hit_count',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Hit Count'
            );
        $installer->getConnection()->createTable($table);

        // create table 'emarsys_placeholders_mapping'
        $emarsys_placeholders_mapping = $installer->getTable('emarsys_placeholders_mapping');
        $table = $installer->getConnection()->newTable($installer->getTable($emarsys_placeholders_mapping))->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
            'ID'
        )->addColumn(
            'event_mapping_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Event Mapping Id'
        )->addColumn(
            'magento_placeholder_name',
            Table::TYPE_TEXT,
            null,
            ['nullable' => false],
            'Magento Placeholder Name'
        )->addColumn(
            'emarsys_placeholder_name',
            Table::TYPE_TEXT,
            null,
            ['nullable' => false],
            'Emarsys Placeholder Name'
        )->addColumn(
            'store_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Store Id'
        );
        $installer->getConnection()->createTable($table);

        //Create table 'emarsys_log_details'
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_log_details'))
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
                ['nullable' => false],
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
        $installer->getConnection()->createTable($table);

        // Create table 'emarsys_log_cron_schedule'
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_log_cron_schedule'))
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
        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
