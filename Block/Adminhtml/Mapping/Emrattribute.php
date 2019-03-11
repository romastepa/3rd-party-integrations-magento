<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml\Mapping;

/**
 * Class Emrattribute
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping
 */
class Emrattribute extends \Magento\Backend\Block\Widget\Container
{
    /**
     * @var string
     */
    protected $_template = 'mapping/emrattribute/view.phtml';

    /**
     * Emrattribute constructor.
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Emarsys\Emarsys\Model\ResourceModel\Emrattribute\CollectionFactory $CollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Emarsys\Emarsys\Model\ResourceModel\Emrattribute\CollectionFactory $CollectionFactory,
        $data = []
    ) {
        $this->CollectionFactory = $CollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        $this->setChild(
            'grid',
            $this->getLayout()->createBlock('Emarsys\Emarsys\Block\Adminhtml\Mapping\Emrattribute\Grid', 'emarsys.order.grid')
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

    /**
     * @return mixed
     */
    public function emarattarData()
    {
        $store_id = $this->getRequest()->getParam('store');
        $result = $this->CollectionFactory->create()->addFieldToFilter('store_id', ['eq' => $store_id])->getData();

        return $result;
    }
}
