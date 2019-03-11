<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Cron;

use Emarsys\Emarsys\Model\Logs;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Model\EmarsysCronDetails;

/**
 * Class CleanCronDetails
 * @package Emarsys\Emarsys\Cron
 */
class CleanCronDetails
{
    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager ;

    /**
     * @var EmarsysCronDetails
     */
    protected $emarsysCronDetails;

    /**
     * CleanCronDetails constructor.
     * @param Logs $emarsysLogs
     * @param StoreManagerInterface $storeManager
     * @param EmarsysCronDetails $emarsysCronDetails
     */
    public function __construct(
        Logs $emarsysLogs,
        StoreManagerInterface $storeManager,
        EmarsysCronDetails $emarsysCronDetails
    ) {
        $this->emarsysLogs = $emarsysLogs;
        $this->storeManager = $storeManager;
        $this->emarsysCronDetails = $emarsysCronDetails;
    }

    public function execute()
    {
        try {
            //clear cron details table
            $this->emarsysCronDetails->clearEmarsysCronDetails();
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'CleanCronDetails::execute()'
            );
        }
    }
}
