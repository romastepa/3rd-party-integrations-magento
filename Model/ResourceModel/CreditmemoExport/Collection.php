<?php
/**
 * Created by PhpStorm.
 * User: punk
 * Date: 8/27/18
 * Time: 15:44
 */

namespace Emarsys\Emarsys\Model\ResourceModel\CreditmemoExport;


class Collection extends \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection
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
        $this->_init(\Emarsys\Emarsys\Model\CreditmemoExport::class, \Emarsys\Emarsys\Model\ResourceModel\CreditmemoExport::class);
    }

    /**
     * @inheritdoc
     */
    protected function beforeAddLoadedItem(\Magento\Framework\DataObject $item)
    {
        return $item;
    }
}