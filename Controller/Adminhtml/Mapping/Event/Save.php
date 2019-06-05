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
use Emarsys\Emarsys\Model\EventFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Emarsys\Emarsys\Helper\Logs;
use Emarsys\Emarsys\Model\EmarsyseventmappingFactory;
use Emarsys\Emarsys\Model\ResourceModel\Emarsyseventmapping;

/**
 * Class Save
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event
 */
class Save extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var EventFactory
     */
    protected $eventFactory;

    /**
     * @var Emarsyseventmapping
     */
    protected $emarsysEventMappingResourse;

    /**
     * @var EmarsyseventmappingFactory
     */
    protected $emarsysEventMappingFactory;

    /**
     * Save constructor.
     * @param Context $context
     * @param EventFactory $eventFactory
     * @param DateTime $date
     * @param Logs $logHelper
     * @param EmarsyseventmappingFactory $emarsysEventMappingFactory
     * @param Emarsyseventmapping $emarsysEventMappingResourse
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        EventFactory $eventFactory,
        DateTime $date,
        Logs $logHelper,
        EmarsyseventmappingFactory $emarsysEventMappingFactory,
        Emarsyseventmapping $emarsysEventMappingResourse,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->emarsysEventMappingFactory = $emarsysEventMappingFactory;
        $this->emarsysEventMappingResourse = $emarsysEventMappingResourse;
        $this->resultPageFactory = $resultPageFactory;
        $this->date = $date;
        $this->eventFactory = $eventFactory;
        $this->logHelper = $logHelper;
        $this->_urlInterface = $context->getUrl();
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $storeId = $this->session->getStoreId();
        $gridSessionData = $this->session->getMappingGridData();
        $resultRedirect = $this->resultRedirectFactory->create();
        $errorStatus = true;
        $returnToStore = false;
        $logsArray['job_code'] = 'Event Mapping';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = 'Save Event Mapping';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Automatic';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $storeId;
        $logsArray['website_id'] = 0;
        $logId = $this->logHelper->manualLogs($logsArray);
        $logsArray['id'] = $logId;
        $logsArray['log_action'] = 'True';

        try {
            if (!empty($gridSessionData)) {
                $this->emarsysEventMappingResourse->truncateMappingTable($storeId);
            }
            $emarsysEventIds = [];
            foreach ($gridSessionData as $key => $value) {
                if (!isset($gridSessionData[$key]['magento_event_id'])
                    || empty($gridSessionData[$key]['magento_event_id'])
                    || !isset($gridSessionData[$key]['emarsys_event_id'])
                    || empty($gridSessionData[$key]['emarsys_event_id'])
                    || in_array((int)$gridSessionData[$key]['emarsys_event_id'], $emarsysEventIds)
                ) {
                    continue;
                }
                $emarsysEventIds[] = (int)$gridSessionData[$key]['emarsys_event_id'];
                $model = $this->emarsysEventMappingFactory->create();
                $model->setStoreId($storeId);
                $model->setMagentoEventId((int)$gridSessionData[$key]['magento_event_id']);
                $model->setEmarsysEventId((int)$gridSessionData[$key]['emarsys_event_id']);
                $model->save();
            }
            $errorStatus = false;
            $returnToStore = true;
            $logsArray['emarsys_info'] = 'Save Event Mapping';
            $logsArray['description'] = 'Saved Event Mapping';
            $logsArray['action'] = 'Event Mapping';
            $logsArray['message_type'] = 'Success';

            $this->logHelper->logs($logsArray);
            $this->messageManager->addSuccessMessage("Events mapped successfully");
        } catch (\Exception $e) {
            $logsArray['emarsys_info'] = 'Save Event Mapping';
            $logsArray['description'] = $e->getMessage();
            $logsArray['action'] = 'Event Mapping not successful.';
            $logsArray['message_type'] = 'Error';
            $this->messageManager->addErrorMessage("Event Mapping Failed. Please refer emarsys logs for more information.");
        }

        if ($errorStatus) {
            $logsArray['messages'] = 'Error occurred while Events Updating Schema';
            $logsArray['status'] = 'error';
        } else {
            $logsArray['messages'] = 'Events Update Schema Successful';
            $logsArray['status'] = 'success';
        }
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        $this->logHelper->manualLogsUpdate($logsArray);

        if ($returnToStore) {
            return $resultRedirect->setUrl(
                $this->_urlInterface->getUrl('emarsys_emarsys/mapping_event', ["store" => $storeId])
            );
        } else {
            return $resultRedirect->setRefererOrBaseUrl();
        }
    }
}
