<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Orderexport;

use Magento\Backend\Block\Widget\Form\Container;
use Emarsys\Emarsys\Controller\Adminhtml\Orderexport;

/**
 * Class Form
 * @package Emarsys\Emarsys\Block\Adminhtml\Orderexport
 */
class Form extends \Magento\Backend\Block\Widget\Form
{
    protected $_template = 'bulkexport/bulkexport.phtml';

    /**
     * Form constructor.
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setId('orderExportForm');
    }

    /**
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->isFromError = $this->getRequest()->getParam('error') === 'true';
        return parent::_beforeToHtml();
    }

    /**
     * @return mixed
     */
    public function getStoreId()
    {
        $storeId = $this->getRequest()->getParam('store');
        return $storeId;
    }
}
