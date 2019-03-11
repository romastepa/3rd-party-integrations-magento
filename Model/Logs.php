<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Class Logs
 * @package Emarsys\Emarsys\Model
 */
class Logs extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager ;

    /**
     * @var ManagerInterface
     */
    protected $messageManagerInterface ;

    /**
     * Logs constructor.
     * @param Context $context
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param ManagerInterface $managerInterface
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        StoreManagerInterface $storeManager,
        ManagerInterface $managerInterface,
        DateTime $dateTime,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->messageManagerInterface = $managerInterface;
        $this->dateTime = $dateTime;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Emarsys\Emarsys\Model\ResourceModel\Logs');
    }

    public function addErrorLog($messages, $storeId, $info)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        try {
            $logsArray['job_code'] = 'Exception';
            $logsArray['status'] = 'error';
            $logsArray['messages'] = $messages;
            $logsArray['created_at'] = $this->dateTime->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->dateTime->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = '';
            $logsArray['auto_log'] = '';
            $logsArray['store_id'] = $storeId;
            $logsHelper = $objectManager->create('Emarsys\Emarsys\Helper\Logs');
            $logId = $logsHelper->manualLogs($logsArray);

            if ($logId) {
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = $info;
                $logsArray['description'] = $messages;
                $logsArray['action'] = '';
                $logsArray['message_type'] = 'error';
                $logsArray['log_action'] = 'fail';
                $logsArray['website_id'] = $this->storeManager->getStore($storeId)->getWebsiteId();
                $logsHelper->logs($logsArray);
            }
        } catch (\Exception $e) {
            $this->messageManagerInterface->addErrorMessage(
                'Unable to Log: ' . $e->getMessage()
            );
        }
    }
}
