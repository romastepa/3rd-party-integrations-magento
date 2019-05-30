<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Uploadinitialdb;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Emarsys\Emarsys\Model\EmarsysCronDetails;
use Emarsys\Emarsys\Helper\Cron as EmarsysCronHelper;
use Emarsys\Emarsys\Helper\Data as EmarsysHelperData;
use Emarsys\Emarsys\Model\Logs;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Index for API credentials
 * @package Emarsys\Emarsys\Controller\Adminhtml\Uploadinitialdb
 */
class Index extends Action
{
    /**
     * @var EmarsysCronHelper
     */
    protected $cronHelper;

    /**
     * @var EmarsysCronDetails
     */
    protected $emarsysCronDetails;

    /**
     * @var EmarsysHelperData
     */
    protected $emarsysDataHelper;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Index constructor.
     * @param Context $context
     * @param EmarsysCronHelper $cronHelper
     * @param EmarsysCronDetails $emarsysCronDetails
     * @param EmarsysHelperData $emarsysHelper
     * @param Logs $emarsysLogs
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        EmarsysCronHelper $cronHelper,
        EmarsysCronDetails $emarsysCronDetails,
        EmarsysHelperData $emarsysHelper,
        Logs $emarsysLogs,
        StoreManagerInterface $storeManager
    ) {
        $this->cronHelper = $cronHelper;
        $this->emarsysCronDetails = $emarsysCronDetails;
        $this->emarsysDataHelper = $emarsysHelper;
        $this->emarsysLogs = $emarsysLogs;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $data = $this->getRequest()->getParams();
            $websiteId = isset($data['website']) ? $data['website'] : 0;

            //check emarsys enable for website
            if ($this->emarsysDataHelper->getEmarsysConnectionSetting($websiteId)) {
                $isCronjobScheduled = $this->cronHelper->checkCronjobScheduled(
                    EmarsysCronHelper::CRON_JOB_INITIAL_DB_LOAD,
                    null,
                    $websiteId
                );
                if (!$isCronjobScheduled) {
                    //no cron job scheduled yet, schedule a new cron job
                    $cron = $this->cronHelper->scheduleCronjob(
                        EmarsysCronHelper::CRON_JOB_INITIAL_DB_LOAD,
                        null,
                        $websiteId
                    );

                    //format and encode data in json to be saved in the table
                    $params = $this->cronHelper->getFormattedParams($data);

                    //save details cron details table
                    $this->emarsysCronDetails->addEmarsysCronDetails($cron->getScheduleId(), $params);

                    $this->messageManager->addSuccessMessage(__(
                            'A cron named "%1" have been scheduled. All contacts of this website will be exported shortly.',
                            EmarsysCronHelper::CRON_JOB_INITIAL_DB_LOAD
                        ));
                } else {
                    //cron job already scheduled
                    $this->messageManager->addErrorMessage(__('A cron is already scheduled to export contacts for this website'));
                }
            } else {
                //emarsys is disabled for this website
                $this->messageManager->addErrorMessage(__('Emarsys is disabled for the website %1', $websiteId));
            }
        } catch (\Exception $e) {
            //add exception to logs
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'UploadInitialDb::execute()'
            );
            //report error
            $this->messageManager->addErrorMessage(__('There was a problem while contact sync. %1', $e->getMessage()));
        }
    }
}
