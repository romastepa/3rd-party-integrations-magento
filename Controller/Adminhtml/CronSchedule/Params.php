<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\CronSchedule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Emarsys\Emarsys\Model\EmarsysCronDetailsFactory;
use Magento\Framework\Json\Helper\Data;

/**
 * Class Params
 * @package Emarsys\Emarsys\Controller\Adminhtml\CronSchedule
 */
class Params extends Action
{
    /**
     * @var EmarsysCronDetailsFactory
     */
    private $cronDetailsFactory;

    /**
     * @var Data
     */
    private $jsonHelper;

    /**
     * Params constructor.
     * @param Context $context
     * @param EmarsysCronDetailsFactory $cronDetailsFactory
     * @param Data $jsonHelper
     */
    public function __construct(
        Context $context,
        EmarsysCronDetailsFactory $cronDetailsFactory,
        Data $jsonHelper
    ) {
        $this->cronDetailsFactory = $cronDetailsFactory;
        $this->jsonHelper = $jsonHelper;
        parent::__construct($context);
    }

    /**
     * @return void|$this
     * @throws \RuntimeException
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            $cronDettails = $this->cronDetailsFactory->create()->load($id);
            if ($cronDettails) {
                $params = $this->jsonHelper->jsonDecode($cronDettails->getParams());
                printf("<pre>" . json_encode($params, JSON_PRETTY_PRINT) . "</pre>");
            } else {
                printf("No Data Available");
            }
        } else {
            printf("No Data Available");
        }
    }
}
