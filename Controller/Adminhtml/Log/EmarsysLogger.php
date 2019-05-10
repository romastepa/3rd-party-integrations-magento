<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Emarsys\Emarsys\Model\ResourceModel\Customer;
use Magento\Backend\App\Action\Context;

/**
 * Class EmarsysLogger
 * @package Emarsys\Emarsys\Controller\Adminhtml\Log
 */
class EmarsysLogger extends Action
{
    /**
     * @var Customer
     */
    protected $customerResourceModel;

    /**
     * EmarsysLogger constructor.
     * @param Context $context
     * @param Customer $customerResourceModel
     */
    public function __construct(
        Context $context,
        Customer $customerResourceModel
    ) {
        parent::__construct($context);
        $this->customerResourceModel = $customerResourceModel;
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */

    public function execute()
    {
        $logData = $this->customerResourceModel->getLogsData();
        $filePath = BP . "/var/log/emarsys.log";
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $writer = new \Zend\Log\Writer\Stream($filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        foreach ($logData as $log) {
            $data = "[" . $log['created_at'] . "] : " . $log['message_type'] . " : " . $log['description'];
            $logger->info($data);
        }
        if (file_exists($filePath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename('emarsys.log'));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
        } else {
            $this->messageManager->addSuccessMessage(__('No Logs Found'));
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setRefererOrBaseUrl();

            return $resultRedirect;
        }
    }
}
