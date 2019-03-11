<?php
/**
 * Created by PhpStorm.
 * User: punk
 * Date: 8/27/18
 * Time: 15:43
 */

namespace Emarsys\Emarsys\Model;


class OrderExport extends \Magento\Sales\Model\Order
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Emarsys\Emarsys\Model\ResourceModel\OrderExport::class);
    }
}