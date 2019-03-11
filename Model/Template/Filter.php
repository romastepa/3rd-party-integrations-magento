<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Emarsys\Emarsys\Model\Template;

use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class Filter
 * @package Emarsys\Emarsys\Model\Template
 */
class Filter extends \Magento\Email\Model\Template\Filter
{
    /**
     * Filter constructor.
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Variable\Model\VariableFactory $coreVariableFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\UrlInterface $urlModel
     * @param \Pelago\Emogrifier $emogrifier
     * @param \Magento\Email\Model\Source\Variables $configVariables
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Emarsys\Emarsys\Model\Logs $emarsysLogs
     */
    public function __construct(
        \Magento\Framework\Stdlib\StringUtils $string,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Variable\Model\VariableFactory $coreVariableFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\UrlInterface $urlModel,
        \Pelago\Emogrifier $emogrifier,
        \Magento\Email\Model\Source\Variables $configVariables,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Emarsys\Emarsys\Model\Logs $emarsysLogs
    ) {
        parent::__construct(
            $string,
            $logger,
            $escaper,
            $assetRepo,
            $scopeConfig,
            $coreVariableFactory,
            $storeManager,
            $layout,
            $layoutFactory,
            $appState,
            $urlModel,
            $emogrifier,
            $configVariables
        );
        $this->_modifiers['currency'] = [$this, 'modifierCurrency'];
        $this->priceHelper = $priceHelper;
        $this->emarsysLogs = $emarsysLogs;
    }

    /**
     * @param $value
     * @param string $type
     * @return float|string
     */
    public function modifierCurrency($value, $type = 'html')
    {
        try {
            return $this->priceHelper->currency($value);
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->_storeManager->getStore()->getId(),
                'Filter::modifierCurrency()'
            );
        }

        return $value;
    }
}
