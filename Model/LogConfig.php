<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model;

/**
 * Class LogConfig
 * @package Emarsys\Emarsys\Model
 */
class LogConfig extends \Magento\Framework\DataObject
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\App\Config\ValueInterface
     */
    protected $_backendModel;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;

    /**
     * @var \Magento\Framework\App\Config\ValueFactory
     */
    protected $_configValueFactory;

    /**
     * LogConfig constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Config\ValueInterface $backendModel
     * @param \Magento\Framework\DB\Transaction $transaction
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\ValueInterface $backendModel,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        $data = []
    ) {
        parent::__construct($data);
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_backendModel = $backendModel;
        $this->_transaction = $transaction;
        $this->_configValueFactory = $configValueFactory;
    }

    /**
     * Function for getting Config value of current store
     * @param $path
     * @return mixed
     */
    public function getCurrentStoreConfigValue($path)
    {
        $storeId = $this->_storeManager->getStore()->getId();
        return $this->_scopeConfig->getValue($path, 'store', $storeId);
    }

    /**
     * Function for setting Config value of current store
     * @param string $path ,
     * @param string $value ,
     */
    public function setCurrentStoreConfigValue($path, $value)
    {
        $store = $this->_storeManager->getStore();
        $storeId = $store->getId();
        $storeCode = $store->getCode();
        $data = [
            'path' => $path,
            'scope' => 'stores',
            'scope_id' => $storeId,
            'scope_code' => $storeCode,
            'value' => $value,
        ];

        $this->_backendModel->addData($data);
        $this->_transaction->addObject($this->_backendModel);
        $this->_transaction->save();
    }
}
