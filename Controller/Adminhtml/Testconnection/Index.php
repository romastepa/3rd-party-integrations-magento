<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Testconnection;

use Emarsys\Emarsys\Helper\Data;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Backend\App\Action\Context;
use Emarsys\Emarsys\Helper\Logs as EmarsysLogs;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Backend\App\Action;

/**
 * Class Index
 * @package Emarsys\Emarsys\Controller\Adminhtml\Testconnection
 */
class Index extends Action
{
    /**
     * @var Data
     */
    protected $emarsysHelper;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var EmarsysLogs
     */
    protected $logsHelper;

    /**
     * Index constructor.
     * @param Data $emarsysHelper
     * @param Context $context
     * @param DateTime $date
     * @param EmarsysLogs $logsHelper
     * @param Config $config
     */
    public function __construct(
        Data $emarsysHelper,
        Context $context,
        DateTime $date,
        EmarsysLogs $logsHelper,
        Config $config
    ) {
        $this->emarsysHelper = $emarsysHelper;
        $this->logsHelper = $logsHelper;
        $this->date = $date;
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     * Emarsys test connection api credentials
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $website = $this->getRequest()->getParam('website');
        if (isset($params['store'])) {
            $storeId = $params['store'];
        } else {
            $storeId = 1;
        }
        $logsArray['job_code'] = 'testconnection';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = 'Test connection initiated';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Manual';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $storeId;
        $logsArray['website_id'] = $website;
        $logId = $this->logsHelper->manualLogs($logsArray);

        $username = trim($params['username']);
        $password = trim($params['password']);
        $endpoint = trim($params['endpoint']);
        $url = trim($params['url']);

        if ($username != "" && $password != '') {
            $scopeType = 'websites';
            $scopeId = $website;
            if ($website == '') {
                $scopeType = 'default';
                $scopeId = 0;
            }
            $this->emarsysHelper->assignApiCredentials($username, $password, $endpoint, $url);
            $pingendpoint = 'settings';
            $response = $this->emarsysHelper->send('GET', $pingendpoint);

            if ($this->is_json($response)) {
                $jsonDecode = \Zend_Json::decode($response);
            } else {
                $jsonDecode['replyCode'] = 10;
            }

            if ($jsonDecode['replyCode'] == 0 && $jsonDecode['replyText'] == "OK") {
                try {
                    //save information in respected configuration.
                    $this->config->saveConfig('emarsys_settings/emarsys_setting/enable', 1, $scopeType, $scopeId);

                    $logsArray['id'] = $logId;
                    $logsArray['emarsys_info'] = 'Test connection';
                    $logsArray['description'] = "Inserted status enbale";
                    $logsArray['action'] = 'Synced to Magento';
                    $logsArray['message_type'] = 'Success';
                    $logsArray['log_action'] = 'sync';
                    $this->logsHelper->logs($logsArray);

                    //save api_endpoint information in respected configuration.
                    $this->config->saveConfig('emarsys_settings/emarsys_setting/emarsys_api_endpoint', $endpoint, $scopeType, $scopeId);

                    $logsArray['id'] = $logId;
                    $logsArray['emarsys_info'] = 'Test connection';
                    $logsArray['description'] = "Inserted endpoint.";
                    $logsArray['action'] = 'Synced to Magento';
                    $logsArray['message_type'] = 'Success';
                    $logsArray['log_action'] = 'sync';
                    $this->logsHelper->logs($logsArray);

                    if ($endpoint == 'custom') {
                        //save custom_url information in respected configuration.
                        $this->config->saveConfig('emarsys_settings/emarsys_setting/emarsys_custom_url', $url, $scopeType, $scopeId);

                        $logsArray['id'] = $logId;
                        $logsArray['emarsys_info'] = 'Test connection';
                        $logsArray['description'] = "Inserted custom url";
                        $logsArray['action'] = 'Synced to Magento';
                        $logsArray['message_type'] = 'Success';
                        $logsArray['log_action'] = 'sync';
                        $this->logsHelper->logs($logsArray);
                    }

                    //save api username information in respected configuration.
                    $this->config->saveConfig('emarsys_settings/emarsys_setting/emarsys_api_username', $username, $scopeType, $scopeId);

                    $logsArray['id'] = $logId;
                    $logsArray['emarsys_info'] = 'Test connection';
                    $logsArray['description'] = "Inserted username";
                    $logsArray['action'] = 'Synced to Magento';
                    $logsArray['message_type'] = 'Success';
                    $logsArray['log_action'] = 'sync';
                    $this->logsHelper->logs($logsArray);

                    //save api password information in respected configuration.
                    $this->config->saveConfig('emarsys_settings/emarsys_setting/emarsys_api_password', $password, $scopeType, $scopeId);

                    $logsArray['id'] = $logId;
                    $logsArray['emarsys_info'] = 'Test connection';
                    $logsArray['description'] = "Inserted Password";
                    $logsArray['action'] = 'Synced to Magento';
                    $logsArray['message_type'] = 'Success';
                    $logsArray['log_action'] = 'sync';
                    $this->logsHelper->logs($logsArray);
                    $this->messageManager->addSuccessMessage('Test connection is successful.');

                    $logsArray['id'] = $logId;
                    $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                    $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                    $logsArray['status'] = 'success';
                    $logsArray['messages'] = 'Test connection Completed';
                    $this->logsHelper->manualLogsUpdate($logsArray);
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage($e->getMessage());
                    $this->messageManager->addErrorMessage('Test connection is failed.');
                    $logsArray['id'] = $logId;
                    $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                    $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                    $logsArray['status'] = 'error';
                    $logsArray['messages'] = 'Test connection failed';
                    $this->logsHelper->manualLogsUpdate($logsArray);
                }
            } else {
                //test api connecton failed.
                $this->messageManager->addErrorMessage('Connection failed. Please check your credentials and try again.');
                $logsArray['id'] = $logId;
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['status'] = 'error';
                $logsArray['messages'] = 'Connection failed. Please check your credentials and try again. ' . json_encode($jsonDecode, JSON_PRETTY_PRINT);
                $this->logsHelper->manualLogsUpdate($logsArray);
            }
        } else {
            //no api credentials found.
            $this->messageManager->addErrorMessage('Please enter the api credentials');
            $logsArray['id'] = $logId;
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['status'] = 'error';
            $logsArray['messages'] = 'Connection failed. Please enter the api credentials.';
            $this->logsHelper->manualLogsUpdate($logsArray);
        }
    }

    /**
     * @param $x
     * @return bool
     */
    private function is_json($x)
    {
        if (!is_string($x) || !trim($x)) {
            return false;
        }
        return (
            // Maybe an empty string, array or object
            $x === '""' ||
            $x === '[]' ||
            $x === '{}' ||
            // Maybe an encoded JSON string
            $x[0] === '"' ||
            // Maybe a flat array
            $x[0] === '[' ||
            // Maybe an associative array
            $x[0] === '{'
        ) && ($x === 'null' || json_decode($x) !== null);
    }
}
