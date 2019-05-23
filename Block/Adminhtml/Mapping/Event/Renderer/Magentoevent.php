<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Event\Renderer;

use Emarsys\Emarsys\Model\ResourceModel\Emarsysmagentoevents\CollectionFactory;
use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

/**
 * Class Magentoevent
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping\Event\Renderer
 */
class Magentoevent extends AbstractRenderer
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;


    /**
     * Magentoevent constructor.
     *
     * @param CollectionFactory $collectionFactory
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        Context $context,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        $collection = $this->collectionFactory->create()
            ->addFilter("id", $row->getData("magento_event_id"))
            ->getFirstItem();

        return $collection->getData("magento_event");
    }
}
