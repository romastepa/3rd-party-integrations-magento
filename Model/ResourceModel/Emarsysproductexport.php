<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\ResourceModel;

/**
 * Class Emarsysproductexport
 * @package Emarsys\Emarsys\Model\ResourceModel
 */
class Emarsysproductexport extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define main table
     * @return void
     */
    protected function _construct()
    {
        $this->_init('emarsys_product_export', 'entity_id');
    }

    /**
     * Save Products to temp table
     *
     * @param array $products
     * @return \Zend_Db_Statement_Interface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveBulkProducts($products)
    {
        $lines = $bind = array();

        foreach ($products as $row) {
            $line = array();
            foreach ($row as $value) {
                $line[] = '?';
                $bind[] = $value;
            }
            $lines[] = sprintf('(%s)', implode(', ', $line));
        }

        $sql =  sprintf(
            'INSERT INTO %s (%s) VALUES%s ON DUPLICATE KEY UPDATE %s',
            $this->getMainTable(),
            'entity_id, params',
            implode(', ', $lines),
            '`params` = CONCAT(`params` , \'' . \Emarsys\Emarsys\Model\Emarsysproductexport::EMARSYS_DELIMITER . '\' , VALUES(`params`))'
        );

        return $this->getConnection()->query($sql , $bind);
    }

    /**
     * Truncate a table
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function truncateTable()
    {
        return $this->getConnection()->truncateTable($this->getMainTable());
    }
}