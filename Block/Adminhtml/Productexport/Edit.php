<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Productexport;

use Magento\Backend\Block\Widget\Form\Container;

/**
 * Class Edit
 * @package Emarsys\Emarsys\Block\Adminhtml\Productexport
 */
class Edit extends Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Edit constructor.
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Request\Http $request
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Request\Http $request,
        array $data = []
    ) {
        $this->getRequest = $request;
        $this->storeManager = $context->getStoreManager();
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Support edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'entity_id';
        $this->_blockGroup = 'Emarsys_Emarsys';
        $this->_controller = 'adminhtml_productexport';
        $storeId = $this->getRequest->getParam('store');
        if ($storeId != '') {
            $url = $this->getUrl("emarsys_emarsys/productexport/productExport", ["storeId" => $storeId]);
        } else {
            $url = $this->getUrl("emarsys_emarsys/productexport/productExport");
        }

        parent::_construct();

        $this->buttonList->remove('back');
        $this->buttonList->remove('save');
        $this->buttonList->remove('reset');
        if (isset($storeId)) {
            $this->buttonList->add(
                'showreport',
                [
                    'label' => __("Export"),
                    'class' => 'save primary',
                    'onclick' => "bulkProductExportData('" . $url . "')",
                ]
            );
        }
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
