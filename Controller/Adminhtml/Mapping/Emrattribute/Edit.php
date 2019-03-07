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
 * Class Edit
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Emrattribute
 */
class Edit extends Action
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
     * Edit constructor.
     * @param Context $context
     * @param \Emarsys\Emarsys\Model\Emrattribute $emrattribute
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $requestParams = $this->getRequest()->getParams();
        $attributeId = $requestParams['Id'];
        $code = $requestParams['code'];
        $label = $requestParams['label'];
        $field_type = $requestParams['field_type'];
        if ($attributeId) {
            $productAttribute = $this->emrattribute->load($attributeId);
            $productId = $productAttribute->getId();
            if ($productId) {
                if (strpos($code, 'c_') === false) {
                    $code = 'c_' . $code;
                }
                if (strpos($label, 'c_') === false) {
                    $label = 'c_' . $label;
                }
                $productAttribute->setCode($code);
                $productAttribute->setLabel($label);
                $field_type = ucfirst($field_type);
                $productAttribute->setFieldType($field_type);
                $productAttribute->save();
                $resultJson = $this->resultJsonFactory->create();
                $data['status'] = 'SUCCESS';
                return $resultJson->setData($data);
            }
        }
    }
}
