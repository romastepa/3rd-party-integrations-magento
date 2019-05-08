<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Emarsys\Emarsys\Helper\Data;

/**
 * Class Refreshplaceholders
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event
 */
class Refreshplaceholders extends Action
{
    /**
     * @var Data
     */
    protected $emarsysHelper;

    /**
     * Refreshplaceholders constructor.
     * @param Context $context
     * @param Data $EmarsysHelper
     */
    public function __construct(
        Context $context,
        Data $EmarsysHelper
    ) {
        parent::__construct($context);
        $this->emarsysHelper = $EmarsysHelper;
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $placeholderData = $this->getRequest()->getParams();
        $store = $this->getRequest()->getParam('store');
        if (!$store) {
            $storeId = $this->emarsysHelper->getFirstStoreId();
            $returnUrl = $this->getUrl(
                '*/*/refreshplaceholders',
                ['mapping_id' => $placeholderData['mapping_id'], 'store' => $storeId]
            );
            return $this->resultRedirectFactory->create()->setUrl($returnUrl);
        }
        $this->emarsysHelper->refreshPlaceholders(
            $placeholderData['mapping_id'],
            $this->getRequest()->getParam('store')
        );

        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setRefererOrBaseUrl();
    }
}
