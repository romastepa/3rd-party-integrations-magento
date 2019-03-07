<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\WebDav;

use Emarsys\Emarsys\Helper\Data;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\File\Csv;

/**
 * Class WebDavExport
 * @package Emarsys\Emarsys\Model\WebDav
 */
class WebDavExport extends Curl
{
    /**
     * @var Data
     */
    protected $emarsysHelper;

    /**
     * @var Csv
     */
    protected $csvWriter;

    /**
     * WebDavExport constructor.
     * @param Data $emarsysHelper
     * @param Csv $csvWriter
     */
    public function __construct(
        Data $emarsysHelper,
        Csv $csvWriter
    ) {
        $this->emarsysHelper = $emarsysHelper;
        $this->csvWriter = $csvWriter;
    }

    /**
     * @return array
     */
    public function getApiHeaders()
    {
        $headers = [];
        $headers[] = "Content-type: text/csv";
        $headers[] = "Accept: text/plain";

        return $headers;
    }

    /**
     * @param $filePath
     * @param $fileName
     * @param $webDavUrl
     * @param $webDavUser
     * @param $webDavPass
     * @return bool|\Magento\Framework\Phrase|string
     */
    public function apiExport($filePath, $fileName, $webDavUrl, $webDavUser, $webDavPass)
    {
        $response = [
            'status' => false,
            'response_body' => '',
        ];

        if ($filePath && $fileName && $webDavUrl && $webDavUser && $webDavPass) {
            $remoteFileName = $webDavUrl . $fileName;
            $data = file_get_contents($filePath);
            $options = [
                CURLOPT_URL => $remoteFileName,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_HTTPAUTH => CURLAUTH_ANY,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_USERPWD => "$webDavUser:$webDavPass"
            ];

            $this->setHeaders($this->getApiHeaders());
            $this->setCredentials($webDavUser, $webDavPass);
            $this->setOptions($options);
            $this->makeRequest('PUT', $remoteFileName, $data);

            $status = $this->getStatus();
            $response['response_body'] = strip_tags($this->getBody());

            if (in_array($status, ['100', '200', '201', '202', '203', '204', '205', '206'])) {
                $response['status'] = true;
                if ($status == '204') {
                    $response['response_body'] = __('File have been uploaded succesfully. A file with the same name already exists.');
                }
            }
        } else {
            $response['response_body'] = __('Invalid Credentials. Either Webdav credentials, file name or file path missing.');
        }

        return $response;
    }

    /**
     * @param $webDavUrl
     * @param $webDavUser
     * @param $webDavPass
     * @return bool|int|\Magento\Framework\Phrase|string
     */
    public function testWebDavConnection($webDavUrl, $webDavUser, $webDavPass)
    {
        $fileName = 'test_connection.csv';
        $data = [
            ['test'],
            ['test']
        ];

        $fileDirectory = $this->emarsysHelper->getEmarsysMediaDirectoryPath('testconnections');
        $this->emarsysHelper->checkAndCreateFolder($fileDirectory);
        $filePath =  $fileDirectory . "/" . $fileName;

        $this->csvWriter
            ->setEnclosure('"')
            ->setDelimiter(',')
            ->saveData($filePath, $data);

        $exportStatus = $this->apiExport($filePath, $fileName, $webDavUrl, $webDavUser, $webDavPass);
        unlink($filePath);

        return $exportStatus;
    }
}
