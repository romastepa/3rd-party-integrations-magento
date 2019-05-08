<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\FtpTestConnection;

use Magento\Backend\App\Action;
use Emarsys\Emarsys\Helper\Data;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface as Logger;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Model\ResourceModel\Customer;
use Magento\Framework\App\Request\Http;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Index
 * @package Emarsys\Emarsys\Controller\Adminhtml\FtpTestConnection
 */
class Index extends Action
{
    /**
     * @var CustomerFactory
     */
    protected $customer;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Customer
     */
    protected $customerResourceModel;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Http
     */
    protected $getRequest;

    /**
     * @var Logger
     */
    protected $logger;
    
    /*
     * @var scopeConfig
     */
    protected $scopeConfig;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param DateTime $date
     * @param CustomerFactory $customer
     * @param StoreManagerInterface $storeManager
     * @param Customer $customerResourceModel
     * @param Http $request
     * @param Config $config
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        DateTime $date,
        CustomerFactory $customer,
        StoreManagerInterface $storeManager,
        Customer $customerResourceModel,
        Http $request,
        Config $config,
        Logger $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->customer = $customer;
        $this->storeManager = $storeManager;
        $this->resultPageFactory = $resultPageFactory;
        $this->customerResourceModel = $customerResourceModel;
        $this->date = $date;
        $this->config = $config;
        $this->getRequest = $request;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * Emarsys test connection api credentials
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $data = $this->getRequest->getParams();
        if ($data['ftpssl'] == 1) {
            $ftpConnection = @ftp_ssl_connect($data['hostname'], $data['port'], 30);
        } else {
            $ftpConnection = @ftp_connect($data['hostname'], $data['port'], 30);
        }
        if ($ftpConnection != '') {
            $ftpLogin = @ftp_login($ftpConnection, $data['username'], $data['password']);
            if ($ftpLogin == 1) {
                $pasvstat = true;
                if ($data['passivemode'] == 1) {
                    $pasvstat = @ftp_pasv($ftpConnection, true);
                }
                if ($pasvstat) {
                    // Save value in DB
                    $scope = ScopeInterface::SCOPE_WEBSITES;
                    $websiteId = $data['website'];
                    $this->config->saveConfig('emarsys_settings/ftp_settings/hostname', $data['hostname'], $scope, $websiteId);
                    $this->config->saveConfig('emarsys_settings/ftp_settings/port', $data['port'], $scope, $websiteId);
                    $this->config->saveConfig('emarsys_settings/ftp_settings/username', $data['username'], $scope, $websiteId);
                    $this->config->saveConfig('emarsys_settings/ftp_settings/ftp_password', $data['password'], $scope, $websiteId);
                    $this->config->saveConfig('emarsys_settings/ftp_settings/ftp_bulk_export_dir', $data['bulkexpdir'], $scope, $websiteId);
                    $this->config->saveConfig('emarsys_settings/ftp_settings/useftp_overssl', $data['ftpssl'], $scope, $websiteId);
                    $this->config->saveConfig('emarsys_settings/ftp_settings/usepassive_mode', $data['passivemode'], $scope, $websiteId);
                    $this->messageManager->addSuccessMessage('Test connection successful !!!');
                } else {
                    $this->messageManager->addErrorMessage('Failed to connect using passive mode.');
                }
            } else {
                $this->messageManager->addErrorMessage('Failed to login with server !!!');
            }
        } else {
            $this->messageManager->addErrorMessage('Failed to connect with server !!!');
        }
    }
}
