<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2019 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File as FilesystemIoFile;
use Magento\Framework\Filesystem\Io\Ftp;
use Psr\Log\LoggerInterface;

/**
 * Class Data
 *
 * @package Emarsys\Emarsys\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const MODULE_NAME = 'Emarsys_Emarsys';

    const Version = '2.1';

    const OPTIN_PRIORITY = 'Emarsys';

    //XML Path of Emarsys Credentials
    const XPATH_EMARSYS_ENABLED = 'emarsys_settings/emarsys_setting/enable';

    //XML Path of FTP Credentials
    const XPATH_EMARSYS_FTP_HOSTNAME = 'emarsys_settings/ftp_settings/hostname';

    const XPATH_EMARSYS_FTP_PORT = 'emarsys_settings/ftp_settings/port';

    const XPATH_EMARSYS_FTP_USERNAME = 'emarsys_settings/ftp_settings/username';

    const XPATH_EMARSYS_FTP_PASSWORD = 'emarsys_settings/ftp_settings/ftp_password';

    const XPATH_EMARSYS_FTP_BULK_EXPORT_DIR = 'emarsys_settings/ftp_settings/ftp_bulk_export_dir';

    const XPATH_EMARSYS_FTP_USEFTP_OVER_SSL = 'emarsys_settings/ftp_settings/useftp_overssl';

    const XPATH_EMARSYS_FTP_USE_PASSIVE_MODE = 'emarsys_settings/ftp_settings/usepassive_mode';

    const BATCH_SIZE = 1000;

    const ENTITY_EXPORT_MODE_AUTOMATIC = 'Automatic';

    const ENTITY_EXPORT_MODE_MANUAL = 'Manual';

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var FilesystemIoFile
     */
    protected $filesystemIoFile;

    /**
     * @var Ftp
     */
    protected $ftp;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param DirectoryList $directoryList
     * @param FilesystemIoFile $filesystemIoFile
     * @param Ftp $ftp
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        DirectoryList $directoryList,
        FilesystemIoFile $filesystemIoFile,
        Ftp $ftp,
        LoggerInterface $logger
    ) {
        $this->context = $context;
        $this->storeManager = $storeManager;
        $this->directoryList = $directoryList;
        $this->filesystemIoFile = $filesystemIoFile;
        $this->ftp = $ftp;
        $this->logger = $logger;

        parent::__construct($context);
    }

    /**
     * @return int
     */
    public function getFirstStoreId()
    {
        $stores = $this->storeManager->getStores();
        $store = current($stores);
        $firstStoreId = $store->getId();

        return $firstStoreId;
    }

    /**
     * @param $websiteId
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFirstStoreIdOfWebsite($websiteId)
    {
        /** @var \Magento\Store\Api\Data\WebsiteInterface $websiteId */
        $website = $this->storeManager->getWebsite($websiteId);

        $defaultStore = @$website->getDefaultStore();
        if ($defaultStore && $defaultStore->getId()) {
            $firstStoreId = $defaultStore->getId();
        } else {
            $stores = $website->getStores();
            $store = current($stores);
            $firstStoreId = $store->getId();
        }

        return $firstStoreId;
    }

    /**
     * Checks whether emarsys is enabled or not.
     *
     * @param null $websiteId
     * @return boolean
     */
    public function isEmarsysEnabled($websiteId = null)
    {
        return true;
    }

    /**
     * Check FTP Connection
     *
     * @param $hostname
     * @param $username
     * @param $password
     * @param $port
     * @param $ftpSsl
     * @param $passiveMode
     * @return bool
     */
    public function checkFtpConnection($hostname, $username, $password, $port, $ftpSsl, $passiveMode)
    {
        $result = false;
        try {
            if (!$username || !$password || !$hostname || !$port) {
                return $result;
            }

            if ($ftpSsl == 1) {
                $ftpConnId = @ftp_ssl_connect($hostname, $port);
            } else {
                $ftpConnId = @ftp_connect($hostname, $port);
            }
            if ($ftpConnId != '') {
                $ftpLogin = @ftp_login($ftpConnId, $username, $password);
                if ($ftpLogin == 1) {
                    $passsiveState = true;
                    if ($passiveMode == 1) {
                        $passsiveState = @ftp_pasv($ftpConnId, true);
                    }
                    if ($passsiveState) {
                        $result = true;
                        @ftp_close($ftpConnId);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return $result;
    }

    /**
     * @param mixed $store
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function checkFtpConnectionByStore($store)
    {
        $result = false;

        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore($store);
        try {
            $hostname = $store->getConfig(self::XPATH_EMARSYS_FTP_HOSTNAME);
            $port = $store->getConfig(self::XPATH_EMARSYS_FTP_PORT);
            $username = $store->getConfig(self::XPATH_EMARSYS_FTP_USERNAME);
            $password = $store->getConfig(self::XPATH_EMARSYS_FTP_PASSWORD);
            $ftpSsl = $store->getConfig(self::XPATH_EMARSYS_FTP_USEFTP_OVER_SSL);
            $passiveMode = $store->getConfig(self::XPATH_EMARSYS_FTP_USE_PASSIVE_MODE);

            if (!$username || !$password || !$hostname || !$port) {
                return $result;
            }
            $result = $this->ftp->open(
                [
                    'host' => $hostname,
                    'port' => $port,
                    'user' => $username,
                    'password' => $password,
                    'ssl' => $ftpSsl ? true : false,
                    'passive' => $passiveMode ? true : false,
                ]
            );
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return $result;
    }

    /**
     * @param mixed $store
     * @param string $filePath
     * @param string $filename
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function moveFileToFtp($store, $filePath, $filename)
    {
        $result = false;
        if ($this->checkFtpConnectionByStore($store)) {
            $result = $this->ftp->write($filename, $filePath);
            $this->ftp->close();
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getSalesOrderCsvDefaultHeader()
    {
        return [
            's_steren_card',
            's_store',
            'order',
            'item',
            'quantity',
            'unit_price',
            'c_sales_amount',
            's_sub_category',
            's_movement_type',
            's_state',
            'customer',
            'date',
        ];
    }

    /**
     * @param $folderName
     * @return bool
     * @throws \Exception
     */
    public function checkAndCreateFolder($folderName)
    {
        if ($this->filesystemIoFile->checkAndCreateFolder($folderName)) {
            if ($this->filesystemIoFile->chmod($folderName, 0775, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $folderName
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getEmarsysMediaDirectoryPath($folderName)
    {
        return $this->directoryList->getPath(DirectoryList::MEDIA) . '/emarsys/' . $folderName;
    }

    /**
     * @param $folderName
     * @param $csvFilePath
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getEmarsysMediaUrlPath($folderName, $csvFilePath)
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
            . 'emarsys/' . $folderName . '/' . basename($csvFilePath);
    }

    /**
     * @param string $fileDirectory
     * @return bool
     */
    public function removeFilesInFolder($fileDirectory)
    {
        if ($handle = opendir($fileDirectory)) {
            while (false !== ($file = readdir($handle))) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $filePath = $fileDirectory . '/' . $file;
                $fileLastModified = filemtime($filePath);
                if ((time() - $fileLastModified) > 1 * 24 * 3600) {
                    unlink($filePath);
                }
            }
            closedir($handle);
        }
        return true;
    }
}
