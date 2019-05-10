<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Schedular
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml;

/**
 * Class CronData
 * @package Emarsys\Emarsys\Block\Adminhtml
 */
class CronData extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Cron\Model\ConfigInterface
     */
    protected $_config;

    /**
     * CronData constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Cron\Model\ConfigInterface $config
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Cron\Model\ConfigInterface $config,
        $data = []
    ) {
        parent::__construct($context, $data);
        $this->_config = $config;
        $jobGroupsRoot = $this->_config->getJobs();
        $data = $this->_request->getParam('group');
    }

    /**
     * @return array
     */
    function getCronData()
    {
        return $this->_config->getJobs();
    }
}
