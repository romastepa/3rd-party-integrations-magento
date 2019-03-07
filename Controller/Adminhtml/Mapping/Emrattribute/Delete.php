<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Emrattribute;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Delete
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Emrattribute
 */
class Delete extends Action
{
    /**
     * @var \Emarsys\Emarsys\Model\Emrattribute
     */
    protected $emrattribute;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Delete constructor.
     * @param Context $context
     * @param \Emarsys\Emarsys\Model\Emrattribute $emrattribute
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        \Emarsys\Emarsys\Model\Emrattribute $emrattribute,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    )
    {
        parent::__construct($context);
        $this->emrattribute = $emrattribute;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     */
    public function execute()
    {
        $requestParams = $this->getRequest()->getParams();
        $productId = $requestParams['Id'];
        $productAttribute = $this->emrattribute->load($productId);
        if ($productAttribute->getId()) {
            $productAttribute->delete();
        }
        $data['status'] = 'SUCCESS';
        $resultJson = $this->resultJsonFactory->create();
        $data['status'] = 'SUCCESS';
        return $resultJson->setData($data);
    }
}
