<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml\Mapping;

/**
 * Class MappingButtons
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping
 */
class MappingButtons extends \Magento\Backend\Block\Widget\Container
{
    /**
     * @var string
     */
    protected $_template = 'mapping/customer/view.phtml';

    /**
     * MappingButtons constructor.
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Email\Model\Template\Config $edit
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Email\Model\Template\Config $edit,
        $data = []
    ) {
        $this->edit = $edit;
        parent::__construct($context, $data);
    }

    public function getStoreId()
    {
        $storeId = $this->getRequest()->getParam('store');
        if (!isset($storeId)) {
            $storeId = 1;
        }
        return $storeId;
    }

    public function getMagentoDefaultTemplate($configPath)
    {
        $templateName = '';
        $Templatevalue = $this->_scopeConfig->getvalue($configPath);
        $availableTemplates = $this->edit->getAvailableTemplates();
        if ($availableTemplates) {
            foreach ($availableTemplates as $template) {
                if ($template['value'] == $Templatevalue) {
                    $templateName = $template['label'];
                }
            }
        }
        return $templateName . " (Default Template from Locale)";
    }
}
