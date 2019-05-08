<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Testconnection;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Config\Model\ResourceModel\Config;
use Emarsys\Emarsys\Model\ApiExport;
use Emarsys\Emarsys\Helper\Logs;

/**
 * Class CatalogApiTestConnection
 * @package Emarsys\Emarsys\Controller\Adminhtml\Testconnection
 */
class CatalogApiTestConnection extends TestConnection
{
    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ApiExport
     */
    protected $apiExport;

    /**
     * @var Logs
     */
    protected $logsHelper;

    /**
     * CatalogApiTestConnection constructor.
     *
     * @param Context $context
     * @param DateTime $date
     * @param Config $config
     * @param ApiExport $apiExport
     * @param Logs $logsHelper
     */
    public function __construct(
        Context $context,
        DateTime $date,
        Config $config,
        ApiExport $apiExport,
        Logs $logsHelper
    )  {
        $this->date = $date;
        $this->config = $config;
        $this->apiExport = $apiExport;
        $this->logsHelper = $logsHelper;
        parent::__construct($context);
    }

    /**
     * Emarsys test connection api credentials
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
        $logsArray['messages'] = 'Catalog API Test Connection Initiated';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Manual';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $storeId;
        $logsArray['website_id'] = $website;
        $logsArray['emarsys_info'] = 'Catalog API Test Connection';
        $logId = $this->logsHelper->manualLogs($logsArray);
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['id'] = $logId;
        $logsArray['action'] = 'Catalog API Test Connection';

        $merchantId = trim($params['merchant_id']);
        $token = trim($params['token']);

        if ($merchantId != '' && $token != '') {
            $scopeType = 'websites';
            $scopeId = $website;
            if ($website == '') {
                $scopeType = 'default';
                $scopeId = 0;
            }

            $this->apiExport->assignApiCredentials($merchantId, $token);
            $response = $this->apiExport->testCatalogExportApi();

            if ($response['result'] == 1 || (in_array($response['status'], ['200', '400']))) {
                try {
                    //save information in respected configuration.
                    $this->config->saveConfig('emarsys_predict/catalog_api_settings/catalog_merchant_id', $merchantId, $scopeType, $scopeId);
                    $this->config->saveConfig('emarsys_predict/catalog_api_settings/catalog_token', $token, $scopeType, $scopeId);

                    $logsArray['description'] = 'Catalog API Test Connection Successful. ' . json_encode($response['resultBody'] , JSON_PRETTY_PRINT);
                    $logsArray['message_type'] = 'Success';
                    $logsArray['log_action'] = 'True';
                    $logsArray['status'] = 'success';
                    $logsArray['messages'] = 'Catalog Test Connection Completed';
                    $this->logsHelper->logs($logsArray);
                    $this->messageManager->addSuccessMessage('Catalog API Test Connection is successful.');
                } catch (\Exception $e) {
                    $logsArray['description'] = 'Catalog API Test Connection Failed Due to Error' . $e->getMessage();
                    $logsArray['message_type'] = 'Error';
                    $logsArray['log_action'] = 'True';
                    $logsArray['status'] = 'error';
                    $logsArray['messages'] = 'Catalog API Test Connection Failed.';
                    $this->logsHelper->logs($logsArray);
                    $this->messageManager->addErrorMessage('Catalog API Test Connection Failed.' . $e->getMessage());
                }
            } else {
                $logsArray['description'] = 'Catalog API Test Connection Failed Due to Error.' . $response['resultBody'];
                $logsArray['message_type'] = 'Error';
                $logsArray['log_action'] = 'True';
                $logsArray['status'] = 'error';
                $logsArray['messages'] = 'Catalog API Test Connection Failed. Please Check Credentials.' . $response['resultBody'];
                $this->logsHelper->logs($logsArray);
                $this->messageManager->addErrorMessage('Catalog API Test Connection Failed. Please Check Credentials.');
            }
        } else {
            $logsArray['description'] = 'Catalog API Test Connection Failed Due to Invalid Credentials. Either Merchant Id or Token not Found. Please check and try again.';
            $logsArray['message_type'] = 'Error';
            $logsArray['log_action'] = 'True';
            $logsArray['status'] = 'error';
            $logsArray['messages'] = 'Catalog API Test Connection Failed Due to Invalid Credentials. Either Merchant Id or Token not Found. Please check and try again.';
            $this->logsHelper->logs($logsArray);
            $this->messageManager->addErrorMessage('Catalog API Test Connection Failed. Please enter the api credentials');
        }

        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        $this->logsHelper->manualLogs($logsArray);
    }
}
