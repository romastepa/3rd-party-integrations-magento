<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2019 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model;

/**
 * Class Api
 * @package Emarsys\Emarsys\Model
 */
class Api extends \Magento\Framework\HTTP\ZendClient
{
    public $_apiUrl;
    public $_username;
    public $_password;

    /**
     * @param $params
     * @return \Magento\Framework\HTTP\ZendClient
     */
    public function setParams($params)
    {
        $this->_apiUrl = $params['api_url'];
        $this->_username = $params['api_username'];
        $this->_password = $params['api_password'];
        $this->config['timeout'] = 60;
        return parent::__construct($this->_apiUrl);
    }

    /**
     * @return string
     */
    protected function _getWSSEHeader()
    {
        $nonce = md5(time());
        $timestamp = gmdate("c");
        $passwordDigest = base64_encode(sha1($nonce . $timestamp . $this->_password, false));
        return sprintf(
            'UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"',
            $this->_username,
            $passwordDigest,
            $nonce,
            $timestamp
        );
    }

    /**
     * @param $apiCall
     * @param string $method
     * @param array $data
     * @param bool $jsonDecode
     * @return array|string
     * @throws \Exception
     * @throws \Zend_Http_Client_Exception
     */
    protected function _request($apiCall, $method = 'GET', $data = [], $jsonDecode = true)
    {
        $this->setUri($this->_apiUrl . $apiCall);
        $this->setHeaders([
            'Content-Type' => 'application/json',
            'Accept-encoding' => 'utf-8',
            'X-WSSE' => $this->_getWSSEHeader(),
            'Extension-Version' => '1.0.12+easyspirit',
        ]);
        $response = [];
        try {
            if ($method == "GET" && !(empty($data))) {
                $this->setParameterGet($data);
            } else {
                if (!empty($data)) {
                    $this->setRawData(json_encode($data));
                }
            }
            $responseObject = $this->request($method);
            $response = $responseObject->getBody();
            if ($jsonDecode) {
                $response = json_decode($response, true);
            }
        } catch (\Exception $e) {

        }
        return $response;
    }

    /**
     * @param $apiCall
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function post($apiCall, $data = [])
    {
        if (is_object($data)) {
            $data = $data->toArray();
        }
        return $this->_request($apiCall, \Zend_Http_Client::POST, $data);
    }

    /**
     * @param $apiCall
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function put($apiCall, $data = [])
    {
        if (is_object($data)) {
            $data = $data->toArray();
        }
        return $this->_request($apiCall, \Zend_Http_Client::PUT, $data);
    }

    /**
     * @param $apiCall
     * @param array $data
     * @param bool $jsonDecode
     * @return array
     * @throws \Exception
     */
    public function get($apiCall, $data = [], $jsonDecode = true)
    {
        return $this->_request($apiCall, \Zend_Http_Client::GET, $data, $jsonDecode);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function ping()
    {
        return $this->get('settings');
    }
}
