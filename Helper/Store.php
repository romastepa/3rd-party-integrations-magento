<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Helper;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;

/**
 * Class Store
 * @package Emarsys\Emarsys\Helper
 */
class Store extends Template
{
    protected $_storeManager;

    /**
     * Store constructor.
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        $this->_storeManager = $context->getStoreManager();
        parent::__construct($context, $data);
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * Get website identifier
     * @param null $storeId
     * @return int
     */
    public function getWebsiteId($storeId = null)
    {
        if (is_null($storeId)) {
            return $this->_storeManager->getStore()->getWebsiteId();
        } else {
            return $this->_storeManager->getStore($storeId)->getWebsiteId();
        }
    }

    /**
     * Get Store code
     *
     * @return string
     */
    public function getStoreCode()
    {
        return $this->_storeManager->getStore()->getCode();
    }

    /**
     * Get Store name
     *
     * @return string
     */
    public function getStoreName()
    {
        return $this->_storeManager->getStore()->getName();
    }

    /**
     * Get current url for store
     * @param bool $fromStore
     * @return mixed
     */
    public function getStoreUrl($fromStore = true)
    {
        return $this->_storeManager->getStore()->getCurrentUrl($fromStore);
    }

    /**
     * Check if store is active
     * @return mixed
     */
    public function isStoreActive()
    {
        return $this->_storeManager->getStore()->isActive();
    }
}
