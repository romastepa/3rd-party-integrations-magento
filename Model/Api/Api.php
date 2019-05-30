<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\Api;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Zend_Http_Client;
use Zend_Json;
use Emarsys\Emarsys\Helper\Data as EmarsysHelperData;

/**
 * Class Api
 * API class for Emarsys API wrappers
 *
 * @package Emarsys\Emarsys\Model\Api
 */
class Api extends \Magento\Framework\DataObject
{
    protected $apiUrl;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @var EmarsysHelperData
     */
    protected $emarsysHelper;

    /**
     * Api constructor.
     * By default is looking for first argument as array and assigns it as object
     * attributes This behavior may change in child classes
     *
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfigInterface,
        array $data = []
    ) {
        $this->scopeConfigInterface = $scopeConfigInterface;
        parent::__construct($data);
    }

    public function _construct()
    {
        $this->websiteId = '';
        $this->scope = '';
    }

    /**
     * @param $websiteId
     */
    public function setWebsiteId($websiteId)
    {
        $this->websiteId = $websiteId;
        $this->scope = 'websites';
    }

    /**
     * Return Emarsys Api user name based on config data
     *
     * @return string
     */
    public function getApiUsername()
    {
        $username = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/emarsys_api_username', $this->scope, $this->websiteId);
        if ($username == '' && $this->websiteId == 1) {
            $username = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/emarsys_api_username');
        }
        return $username;
    }

    /**
     * Return Emarsys Api password based on config data
     *
     * @return string
     */
    public function getApiPassword()
    {
        $password = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/emarsys_api_password', $this->scope, $this->websiteId);
        if ($password == '' && $this->websiteId == 1) {
            $password = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/emarsys_api_password');
        }
        return $password;
    }

    /**
     * set Emarsys API URL
     *
     * @return string
     */
    public function setApiUrl()
    {
        $endpoint = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/emarsys_api_endpoint', $this->scope, $this->websiteId);
        if ($endpoint == '' && $this->websiteId == 1) {
            $endpoint = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/emarsys_api_endpoint');
        }
        if ($endpoint == 'custom') {
            $url = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/emarsys_custom_url', $this->scope, $this->websiteId);
            if ($url == '' && $this->websiteId == 1) {
                $url = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/emarsys_custom_url');
            }
            return $this->apiUrl = rtrim($url, '/') . "/";
        } elseif ($endpoint == 'cdn') {
            return $this->apiUrl = EmarsysHelperData::EMARSYS_CDN_API_URL;
        } elseif ($endpoint == 'default') {
            return $this->apiUrl = EmarsysHelperData::EMARSYS_DEFAULT_API_URL;
        }
    }

    /**
     * Return Emarsys API URL
     *
     * @return string
     */
    public function getApiUrl()
    {
        return $this->setApiUrl();
    }

    /**
     * Returns emarsys enabled based on the current scope
     *
     * @return boolean
     */
    protected function _isEnabled()
    {
        $enable = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/enable', $this->scope, $this->websiteId);
        if ($enable == '' && $this->websiteId == 1) {
            $enable = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/enable');
        }
        return $enable;
    }

    /**
     * @param $requestType
     * @param $urlParam
     * @param array $requestBody
     * @return array
     * @throws \Zend_Http_Client_Exception
     * @throws \Zend_Json_Exception
     */
    public function sendRequestOrig($requestType, $urlParam, $requestBody = [])
    {
        $client = new Zend_Http_Client();
        $requestUrl = $this->getApiUrl() . $urlParam;
        $client->setUri($requestUrl);
        switch ($requestType) {
            case 'GET':
                $client->setMethod(Zend_Http_Client::GET);
                $client->setParameterGet($requestBody);
                break;
            case 'POST':
                $client->setMethod(Zend_Http_Client::POST);
                $client->setRawData(Zend_Json::encode($requestBody));
                break;
            case 'PUT':
                $client->setMethod(Zend_Http_Client::PUT);
                $client->setRawData(Zend_Json::encode($requestBody));
                break;
            case 'DELETE':
                $client->setMethod(Zend_Http_Client::DELETE);
                $client->setRawData(Zend_Json::encode($requestBody));
                break;
        }
        $nonce = time();
        $timestamp = gmdate("c");
        $passwordDigest = base64_encode(sha1($nonce . $timestamp . $this->getApiPassword(), false));
        $client->setHeaders([
            'Content-Type' => 'application/json',
            'Accept-encoding' => 'utf-8',
            'X-WSSE' => [
                'X-WSSE: UsernameToken' .
                'Username="' . $this->getApiUsername() . '", ' .
                'PasswordDigest="' . $passwordDigest . '", ' .
                'Nonce="' . $nonce . '", ' .
                'Created="' . $timestamp . '"',
                'Content-type: application/json;charset="utf-8"',
            ],
            'Extension-Version' => '1.0.12+lovebonito',
        ]);
        $response = $client->request();

        return [
            'status' => Zend_Json::decode($response->getStatus()),
            'body' => Zend_Json::decode($response->getBody()),
        ];
    }

    /**
     * @param $requestType
     * @param null $endPoint
     * @param array $requestBody
     * @return array
     * @throws \Exception
     */
    public function sendRequest($requestType, $endPoint = null, $requestBody = [])
    {
        if ($endPoint == 'custom') {
            $endPoint = '';
        }
        if (!in_array($requestType, ['GET', 'POST', 'PUT', 'DELETE'])) {
            throw new \Exception('Send first parameter must be "GET", "POST", "PUT" or "DELETE"');
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        switch ($requestType) {
            case 'GET':
                curl_setopt($ch, CURLOPT_HTTPGET, 1);
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, Zend_Json::encode($requestBody));
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, Zend_Json::encode($requestBody));
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_POSTFIELDS, Zend_Json::encode($requestBody));
                break;
        }
        curl_setopt($ch, CURLOPT_HEADER, false);

        $requestUri = $this->getApiUrl() . $endPoint;
        curl_setopt($ch, CURLOPT_URL, $requestUri);

        /**
         * We add X-WSSE header for authentication.
         * Always use random 'nonce' for increased security.
         * timestamp: the current date/time in UTC format encoded as
         * an ISO 8601 date string like '2010-12-31T15:30:59+00:00' or '2010-12-31T15:30:59Z'
         * passwordDigest looks sg like 'MDBhOTMwZGE0OTMxMjJlODAyNmE1ZWJhNTdmOTkxOWU4YzNjNWZkMw=='
         */
        $nonce = md5(time());
        $timestamp = gmdate("c");
        $passwordDigest = base64_encode(sha1($nonce . $timestamp . $this->getApiPassword(), false));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-WSSE: UsernameToken ' .
            'Username="' . $this->getApiUsername() . '", ' .
            'PasswordDigest="' . $passwordDigest . '", ' .
            'Nonce="' . $nonce . '", ' .
            'Created="' . $timestamp . '"',
            'Content-type: application/json;charset="utf-8"',
            'Extension-Version: 1.0.12+lovebonito',
        ]);

        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Zend_Http_Client');
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        $response = curl_exec($ch);
        $header = curl_getinfo($ch);

        $http_code = 200;
        if (!curl_errno($ch)) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }
        curl_close($ch);

        try {
            $decodedResponse = \Zend_Json::decode($response);
        } catch (\Exception $e) {
            $decodedResponse = false;
        }

        if ($decodedResponse) {
            $response = $decodedResponse;
        }

        return [
            'status' => $http_code,
            'header' => $header,
            'body' => $response,
        ];
    }

    /**
     * @param $arrCustomerData
     * @return mixed|string
     * @throws \Exception
     */
    public function createContactInEmarsys($arrCustomerData)
    {
        return $this->sendRequest('PUT', 'contact/?create_if_not_exists=1', $arrCustomerData);
    }

    /**
     * @param $arrCustomerData
     * {
     *  "keyId": "12596",
     *  "keyValues": [
     *   "1"
     *  ],
     *  "fields": [
     *   "3",
     *   "12596",
     *   "15912",
     *   "12597"
     *  ]
     * }
     * @return array
     * @throws \Exception
     */
    public function getContactData($arrCustomerData)
    {
        return $this->sendRequest('POST', 'contact/getdata', $arrCustomerData);
    }
}
