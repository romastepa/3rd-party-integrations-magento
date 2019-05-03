<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Schedular
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml;

use Magento\Cron\Model\ConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class CronData
 *
 * @package Emarsys\Emarsys\Block\Adminhtml
 */
class CronData extends Template
{
    /**
     * @var ConfigInterface
     */
    protected $_config;

    /**
     * CronData constructor.
     * @param Context $context
     * @param ConfigInterface $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigInterface $config,
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
