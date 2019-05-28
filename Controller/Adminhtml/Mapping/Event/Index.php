<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
use Emarsys\Emarsys\Model\EmarsyseventmappingFactory as EmarsysEventsFactory;
use Emarsys\Emarsys\Helper\Logs as EmarsysHelperLogs;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

/**
 * Class Index
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event
 */
class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var EmarsysEventsFactory
     */
    protected $emarsysEventsFactory;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * @var EmarsysHelperLogs
     */
    protected $logsHelper;

    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param EmarsysHelper $emarsysHelper
     * @param EmarsysEventsFactory $emarsysEventsFactory
     * @param EmarsysHelperLogs $logsHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        EmarsysHelper $emarsysHelper,
        EmarsyseventsFactory $emarsysEventsFactory,
        EmarsysHelperLogs $logsHelper,
        StoreManager $storeManager
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->emarsysHelper = $emarsysHelper;
        $this->emarsysEventsFactory = $emarsysEventsFactory;
        $this->logsHelper = $logsHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     * @throws \Exception
     */
    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store');
        if (!$storeId) {
            $storeId = $this->emarsysHelper->getFirstStoreId();
            return $this->resultRedirectFactory->create()->setUrl($this->getUrl('*/*', ['store' => $storeId]));
        }

        $store = $this->storeManager->getStore($storeId);

        $eventMappingCollection = $this->emarsysEventsFactory->create()->getCollection()->addFieldToFilter('store_id', $storeId);
        if ($this->emarsysHelper->isEmarsysEnabled($store->getWebsiteId())) {
            if (!$eventMappingCollection->getSize()) {
                $logsArray['job_code'] = 'Event Mapping';
                $logsArray['status'] = 'started';
                $logsArray['messages'] = 'Running Update Schema';
                $logsArray['created_at'] = date('Y-m-d H:i:s');
                $logsArray['executed_at'] = date('Y-m-d H:i:s');
                $logsArray['run_mode'] = 'Automatic';
                $logsArray['auto_log'] = 'Complete';
                $logsArray['store_id'] = $storeId;
                $logsArray['website_id'] = $store->getWebsiteId();
                $logId = $this->logsHelper->manualLogs($logsArray);
                $logsArray['id'] = $logId;

                $this->emarsysHelper->importEvents($storeId, $logId);
                $this->emarsysHelper->insertFirstTime($storeId);

                return $this->resultRedirectFactory->create()->setUrl($this->getUrl('*/*', ['store' => $storeId]));
            }
        }
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $this->_setActiveMenu('Emarsys_Emarsys::emarsys_emarsysadminindex7');
        $resultPage->addBreadcrumb(__('Emarsys - Event Mapping'), __('Emarsys - Event Mapping'));
        $resultPage->getConfig()->getTitle()->prepend(__('Emarsys - Event Mapping'));

        return $resultPage;
    }
}
