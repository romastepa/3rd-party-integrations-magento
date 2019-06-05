<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model;

/**
 * Class Config
 * @package Emarsys\Emarsys\Model
 */
class Config extends \Magento\Framework\DataObject
{
    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
    }

    /**
     * Config constructor.
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
     * @param $path
     * @return mixed
     * Function for getting Config value of current store
     */
    public function getCurrentStoreConfigValue($path)
    {
        $storeCode = $this->_storeManager->getStore()->getCode();
        return $this->_scopeConfig->getValue($path, 'store', $storeCode);
    }

    /**
     * Function for setting Config value of current store
     * @param string $path,
     * @param string $value,
     */
    public function setCurrentStoreConfigValue($path, $value)
    {
        $store = $this->_storeManager->getStore();
        $storeCode = $store->getCode();
        $storeId = $store->getId();
        $data = [
            'path' => $path,
            'scope' =>  'stores',
            'scope_id' => $storeId,
            'scope_code' => $storeCode,
            'value' => $value,
        ];

        $this->_backendModel->addData($data);
        $this->_transaction->addObject($this->_backendModel);
        $this->_transaction->save();
    }
}
