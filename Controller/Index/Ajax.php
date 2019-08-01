<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Controller\Index;

use Magento\Framework\App\Action\Context;
use Emarsys\Emarsys\Block\JavascriptTracking;

class Ajax extends \Magento\Framework\App\Action\Action
{
    /**
     * @var JavascriptTracking
     */
    protected $javascriptTracking;

    /**
     * AjaxUpdate constructor.
     * @param Context $context
     * @param JavascriptTracking $javascriptTracking
     */
    public function __construct(
        Context $context,
        JavascriptTracking $javascriptTracking
    ) {
        $this->javascriptTracking = $javascriptTracking;
        parent::__construct($context);
    }

    /**
     * Ajax Action
     */
    public function execute()
    {
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($this->javascriptTracking->getTrackingData());
    }
}

