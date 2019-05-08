<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml\Cronschedule;

use Magento\Backend\Block\Widget\Container;

/**
 * Class Content
 * @package Emarsys\Emarsys\Block\Adminhtml\Cronschedule
 */
class Content extends Container
{
    /**
     * @var string
     */
    protected $_template = 'cron/grid.phtml';

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        $this->setChild(
            'grid',
            $this->getLayout()->createBlock(
                'Emarsys\Emarsys\Block\Adminhtml\Cronschedule\Grid',
                'emarsys.scheduler.grid'
            )
        );

        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    public function getGridHtml()
    {
        return $this->getChildHtml('grid');
    }
}
