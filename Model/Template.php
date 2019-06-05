<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model;

/**
 * Class Template
 * @package Emarsys\Emarsys\Model
 */
class Template extends \Magento\Email\Model\Template
{
    /**
     * @param string $placeholder
     * @return string
     */
    public function getProcessedVariable($placeholder = '')
    {
        $variables = $this->_getVars();

        $processor = $this->getTemplateFilter()
            ->setUseSessionInUrl(false)
            ->setPlainTemplateMode($this->isPlain())
            ->setIsChildTemplate($this->isChildTemplate())
            ->setTemplateProcessor([$this, 'getTemplateContent']);

        $variables['this'] = $this;

        $isDesignApplied = $this->applyDesignConfig();

        // Set design params so that CSS will be loaded from the proper theme
        $processor->setDesignParams($this->getDesignParams());

        if (isset($variables['subscriber'])) {
            $storeId = $variables['subscriber']->getStoreId();
        } else {
            $storeId = $this->getDesignConfig()->getStore();
        }
        $processor->setStoreId($storeId);

        // Populate the variables array with store, store info, logo, etc. variables
        $variables = $this->addEmailVariables($variables, $storeId);
        $processor->setVariables($variables);

        try {
            $result = $processor->filter($placeholder);
        } catch (\Exception $e) {
            $this->cancelDesignConfig();
            throw new \LogicException(__($e->getMessage()), $e);
        }
        if ($isDesignApplied) {
            $this->cancelDesignConfig();
        }

        return $result;
    }

    /**
     * @return \Magento\Sales\Model\Order|null
     */
    public function checkOrder()
    {
        $variables = $this->_getVars();
        $variables['this'] = $this;
        if (isset($variables['order'])) {
            return $variables['order'];
        } else {
            return null;
        }
    }

    /**
     * @return \Magento\Sales\Model\Order\Shipment|null
     */
    public function checkShipment()
    {
        $variables = $this->_getVars();
        if (isset($variables['shipment'])) {
            return $variables['shipment'];
        } else {
            return null;
        }
    }

    /**
     * @return \Magento\Sales\Model\Order\Invoice|null
     */
    public function checkInvoice()
    {
        $variables = $this->_getVars();
        if (isset($variables['invoice'])) {
            return $variables['invoice'];
        } else {
            return null;
        }
    }

    /**
     * @return \Magento\Rma\Model\Rma|null
     */
    public function checkRma()
    {
        $variables = $this->_getVars();
        if (isset($variables['rma'])) {
            return $variables['rma'];
        } else {
            return null;
        }
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getEmailStoreId()
    {
        if ($this->_appState->getAreaCode() === \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE) {
            $storeId = $this->getDesignConfig()->getStore();
        } else {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $variables = $this->_getVars();
        if (isset($variables['subscriber']) && is_object($variables['subscriber'])) {
            $storeId = $variables['subscriber']->getStoreId();
        } elseif (isset($variables['customer']) && is_object($variables['customer'])) {
            $storeId = $variables['customer']->getStoreId();
        } elseif (isset($variables['order']) && is_object($variables['order'])) {
            $storeId = $variables['order']->getStoreId();
        }

        return $storeId;
    }
}
