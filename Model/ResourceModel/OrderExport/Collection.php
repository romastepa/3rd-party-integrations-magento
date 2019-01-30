<?php
/**
 * Created by PhpStorm.
 * User: punk
 * Date: 8/27/18
 * Time: 15:44
 */

namespace Emarsys\Emarsys\Model\ResourceModel\OrderExport;


class Collection extends \Magento\Sales\Model\ResourceModel\Order\Collection
{

    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->entitySnapshot = null;
        $this->_init(\Emarsys\Emarsys\Model\OrderExport::class, \Emarsys\Emarsys\Model\ResourceModel\OrderExport::class);
    }

    /**
     * @inheritdoc
     */
    protected function beforeAddLoadedItem(\Magento\Framework\DataObject $item)
    {
        return $item;
    }
}