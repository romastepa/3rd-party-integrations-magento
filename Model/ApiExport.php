<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model;

use Magento\Framework\HTTP\ZendClient;
use Emarsys\Emarsys\Helper\Data;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\File\Csv;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Model\Logs as EmarsysModelLogs;
use Magento\Framework\Json\Helper\Data as JsonHelper;

/**
 * Class ApiExport
 * @package Emarsys\Emarsys\Model
 */
class ApiExport extends ZendClient
{
    const MIN_CATALOG_RECORDS_COUNT = 1;

    const API_ERROR_CONNECTION = 'Connection error';

    const API_ERROR_RESPONSE_INVALID = 'Invalid response';

    const DEBUG_KEY = 'EMARSYS_DEBUG_INFO';

    protected $_apiUrl;

    protected $_merchantId;

    protected $_token;

    /**
     * @var Data
     */
    protected $emarsysHelper;

    /**
     * @var Csv
     */
    protected $csvWriter;

    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * @var JsonHelper
     */
    protected $jasonHelper;


    /**
     * ApiExport constructor.
     * @param Data $emarsysHelper
     * @param RawFactory $resultRawFactory
     * @param Csv $csvWriter
     * @param StoreManagerInterface $storeManagerInterface
     * @param Logs $emarsysLogs
     * @param JsonHelper $jsonHelper
     */
    public function __construct(
        Data $emarsysHelper,
        RawFactory $resultRawFactory,
        Csv $csvWriter,
        StoreManagerInterface $storeManagerInterface,
        EmarsysModelLogs $emarsysLogs,
        JsonHelper $jsonHelper
    ) {
        $this->emarsysHelper = $emarsysHelper;
        $this->resultRawFactory = $resultRawFactory;
        $this->csvWriter = $csvWriter;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->emarsysLogs = $emarsysLogs;
        $this->jasonHelper = $jsonHelper;
    }

    /**
     * @param $merchantId
     * @param $token
     */
    public function assignApiCredentials($merchantId, $token)
    {
        $this->_merchantId = $merchantId;
        $this->_token = $token;
    }

    /**
     * Get API Headers
     * @param $token
     * @return array|bool
     */
    public function getApiHeaders($token)
    {
        if (!isset($token)) {
            $token = $this->_token;
        }
        if ($token) {
            $headers = [];
            $headers[] = "Authorization: bearer " . $token;
            $headers[] = "Content-type: text/csv";
            $headers[] = "Accept: text/plain";

            return $headers;
        }
        $storeId = $this->storeManagerInterface->getStore()->getId();
        $this->emarsysLogs->addErrorLog('Api Token Not Found', $storeId, 'ApiExport::getApiHeaders()');

        return false;
    }

    /**
     * @param $apiUrl
     * @param $filePath
     * @return array
     */
    public function apiExport($apiUrl, $filePath)
    {
        $this->_apiUrl = $apiUrl;
        $storeId = $this->storeManagerInterface->getStore()->getId();
        $result = [];
        $result['result'] = 0;
        $result['status'] = '';
        $result['resultBody'] = 'Api Export Failed. API URL or CSV File Not Found.';

        if (!empty($apiUrl) && !empty($filePath) && (file_exists($filePath))) {
            $data = file_get_contents($filePath);
            $response = $this->post($apiUrl, $data);
            if (($response != '')) {
                if ($response->getStatus() == 200) {
                    $result['result'] = 1;
                }
                $result['status'] =  $response->getStatus();
                $result['resultBody'] =  $response->getBody();
            }
        } else {
            $this->emarsysLogs->addErrorLog('Api Export Failed. API URL or CSV File Not Found.', $storeId, 'ApiExport::apiExport()');
        }

        return $result;
    }

    /**
     * Requests API call
     * @param $apiCall
     * @param string $method
     * @param array $data
     * @param bool $jsonDecode
     * @return mixed|string|\Zend_Http_Response
     * @throws \Zend_Http_Client_Exception
     */
    protected function _request($apiCall, $method = \Zend_Http_Client::GET, $data = [], $jsonDecode = true)
    {
        $this->setUri($this->_apiUrl);
        $this->setHeaders($this->getApiHeaders($this->_token));
        $response = '';

        try {
            if ($method == "GET" && ! (empty($data))) {
                $this->setParameterGet($data);
            } else {
                if (!empty($data)) {
                    $this->setRawData($data);
                }
            }

            $responseObject = $this->request($method);
            $response = $responseObject;
            if ($jsonDecode) {
                $response = $this->jasonHelper->jsonDecode($response);
            }
        } catch (\Exception $e) {
            $storeId = $this->storeManagerInterface->getStore()->getId();
            $this->emarsysLogs->addErrorLog(
                'API Test Connection Failed. ' . $e->getMessage(),
                $storeId,
                'ApiExport::_request()'
            );
        }

        return $response;
    }

    /**
     * @param $apiCall
     * @param array $data
     * @return mixed|string|\Zend_Http_Response
     */
    public function post($apiCall, $data = [])
    {
        if (is_object($data)) {
            $data = $data->toArray();
        }

        return $this->_request($apiCall, \Zend_Http_Client::POST, $data, false);
    }

    /**
     * Get API URL
     * @return string
     */
    public function getApiUrl($entityType)
    {
        if ($entityType == \Magento\Catalog\Model\Product::ENTITY) {
            $entityApiUrlKey = $this->emarsysHelper->getProductApiUrlKey();
        } else {
            $entityApiUrlKey = $this->emarsysHelper->getOrderApiUrlKey();
        }
        $emarsysApiUrl = $this->emarsysHelper->getEmarsysApiUrl();
        $apiUrl = $emarsysApiUrl . $this->_merchantId . $entityApiUrlKey;

        return $apiUrl;
    }

    /**
     * get Static Export Array for Emarsys
     * @return array
     */
    public function getCatalogExportCsvHeader()
    {
        return [
            'item',
            'available',
            'title',
            'link',
            'image',
            'category',
            'price'
        ];
    }

    /**
     * Sample Data for Catalog full export test connection.
     * @return array
     */
    public function sampleDataCatalogExport()
    {
        return [
            'test_product_item_1',
            'true',
            'test_product_title_1',
            $this->storeManagerInterface->getStore()->getBaseUrl(),
            $this->storeManagerInterface->getStore()->getBaseUrl(),
            'test_category_1',
            '00.00'
        ];
    }

    /**
     * Get Sales Order Sample Data for Test Connection Button.
     *
     * @param int $store
     * @return array
     */
    public function sampleDataSmartInsightExport($store = 0)
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManagerInterface->getStore($store);

        $emailAsIdentifierStatus = (bool)$store->getConfig($this->emarsysHelper::XPATH_SMARTINSIGHT_EXPORTUSING_EMAILIDENTIFIER);
        if ($emailAsIdentifierStatus) {
            //header ['order', 'timestamp', 'email', 'item', 'price', 'quantity'];
            return [
                '00000',
                '2017-07-07T07:07:07Z',
                'sample@data.com',
                'test_product_item_1',
                '0.00',
                '0'
            ];
        } else {
            //header ['order', 'timestamp', 'customer', 'item', 'price', 'quantity'];
            return [
                '00000',
                '2017-07-07T07:07:07Z',
                'customer_id',
                'test_product_item_1',
                '0.00',
                '0'
            ];
        }

    }

    /**
     * Test Smart Insight API Credentials
     * @return string
     */
    public function testSIExportApi()
    {
        return $this->testApiExport(\Magento\Sales\Model\Order::ENTITY);
    }

    /**
     * Test Catalog Export Api Credentials
     * @return array
     */
    public function testCatalogExportApi()
    {
        return $this->testApiExport(\Magento\Catalog\Model\Product::ENTITY);
    }

    /**
     * @param $entityType
     * @return array
     */
    private function testApiExport($entityType)
    {
        if ($entityType == \Magento\Catalog\Model\Product::ENTITY) {
            $emptyFileHeader = $this->getCatalogExportCsvHeader();
            $sampleData = $this->sampleDataCatalogExport();
        } else {
            $emptyFileHeader = $this->emarsysHelper->getSalesOrderCsvDefaultHeader();
            $sampleData = $this->sampleDataSmartInsightExport();
        }

        $data = [
            $emptyFileHeader,
            $sampleData
        ];

        $fileName = $entityType . '_test_api_export.csv';
        $fileDirectory = $this->emarsysHelper->getEmarsysMediaDirectoryPath('testconnections');
        $this->emarsysHelper->checkAndCreateFolder($fileDirectory);
        $filePath =  $fileDirectory . "/" . $fileName;

        $this->csvWriter
            ->setEnclosure('"')
            ->setDelimiter(',')
            ->saveData($filePath, $data);

        $this->_apiUrl = $apiUrl = $this->getApiUrl($entityType);
        $result = $this->apiExport($apiUrl, $filePath);
        unlink($filePath);

        if (!$result['result'] && $result['status'] == 400) {
            $result['result'] = 1;
        }

        return $result;
    }
}
