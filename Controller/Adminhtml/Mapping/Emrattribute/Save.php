<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Emrattribute;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Emarsys\Emarsys\Model\EmrattributeFactory;
use Emarsys\Emarsys\Model\ResourceModel\Emrattribute\CollectionFactory;

/**
 * Class Save
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Emrattribute
 */
class Save extends \Magento\Framework\App\Action\Action
{
    /**
     * @var EmrattributeFactory
     */
    protected $emrattributeFactory;

    /**
     * @var CollectionFactory
     */
    protected $emrattributeCollectionFactory;

    /**
     * Save constructor.
     * @param Context $context
     * @param EmrattributeFactory $EmrattributeFactory
     * @param CollectionFactory $EmrattributeCollectionFactory
     */
    public function __construct(
        Context $context,
        EmrattributeFactory $EmrattributeFactory,
        CollectionFactory $EmrattributeCollectionFactory
    ) {
        parent::__construct($context);
        $this->emrattributeFactory = $EmrattributeFactory;
        $this->emrattributeCollectionFactory = $EmrattributeCollectionFactory;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $requestParams = $this->getRequest()->getParams();
        $storeId = $this->getRequest()->getParam('store');
        for ($loop = 0; $loop < count($requestParams['field_name']); $loop++) {
            if ($requestParams['field_name'][$loop] != '') {
                $field_name = 'c_' . $requestParams['field_name'][$loop];
                $field_label = 'c_' . $requestParams['field_label'][$loop];
                $attributeCollection = $this->emrattributeCollectionFactory->create();
                $duplicateCode = $attributeCollection->addFieldToFilter('code', ['eq' => $field_name])
                    ->addFieldToFilter('store_id', ['eq' => $storeId])
                    ->getFirstItem()
                    ->getCode();
                $duplicateLabel = $attributeCollection->addFieldToFilter('label', ['eq' => $field_label])
                    ->addFieldToFilter('store_id', ['eq' => $storeId])
                    ->getFirstItem()
                    ->getLabel();

                if ($duplicateCode || $duplicateLabel) {
                    $this->messageManager->addErrorMessage('Attribute with Code ' . $duplicateCode . ' and Label ' . $duplicateLabel . ' has not been Created due to duplication');
                    continue;
                }
                $attribute_type = $requestParams['attribute_type'][$loop];
                $attribute_type = ucfirst($attribute_type);
                $model = $this->emrattributeFactory->create();
                $model->setCode($field_name);
                $model->setLabel($field_label);
                $model->setFieldType($attribute_type);
                $model->setStoreId($storeId);
                $model->save();
            }
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('emarsys_emarsys/mapping_emrattribute', ['store' => $storeId]);
        
        return $resultRedirect;
    }

}
