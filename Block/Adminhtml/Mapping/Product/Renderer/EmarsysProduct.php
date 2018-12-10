<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Product\Renderer;

use Emarsys\{Emarsys\Helper\Data as EmarsysDataHelper,
    Emarsys\Model\ResourceModel\Product\CollectionFactory,
    Emarsys\Model\ResourceModel\Sync};

use Magento\{
    Backend\Model\Session,
    Framework\DataObject
};

/**
 * Class EmarsysProduct
 *
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping\Product\Renderer
 */
class EmarsysProduct extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Sync
     */
    protected $syncResourceModel;

    /**
     * @var EmarsysDataHelper
     */
    protected $emarsysHelper;

    /**
     * EmarsysProduct constructor.
     *
     * @param Session $session
     * @param CollectionFactory $collectionFactory
     * @param Sync $syncResourceModel
     * @param EmarsysDataHelper $emarsysHelper
     */
    public function __construct(
        Session $session,
        CollectionFactory $collectionFactory,
        Sync $syncResourceModel,
        EmarsysDataHelper $emarsysHelper
    ) {
        $this->session = $session;
        $this->collectionFactory = $collectionFactory;
        $this->syncResourceModel = $syncResourceModel;
        $this->emarsysHelper = $emarsysHelper;
    }

    /**
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        global $colValue;
        $colValue = '';
        $attributeCode = $row->getData('attribute_code');
        $url = $this->getUrl('*/*/saveRow');
        $coulmnAttr = 'emarsys_attr_code';
        $collection = $this->collectionFactory->create()->addFieldToFilter('magento_attr_code', $row->getAttributeCode());
        $session = $this->session->getData();

        $gridSessionWebsiteId = $this->emarsysHelper->getFirstWebsiteId();
        if (isset($session['websiteId'])) {
            $gridSessionWebsiteId = $session['websiteId'];
            if ($gridSessionWebsiteId == 0) {
                $gridSessionWebsiteId = $this->emarsysHelper->getFirstWebsiteId();
            }
            $collection->addFieldToFilter('website_id', $gridSessionWebsiteId);
        }

        $data = $collection->getData();
        foreach ($data as $col) {
            $colValue = $col['emarsys_attr_code'];
        }

        $emarsysProductAttributes = $this->syncResourceModel->getAttributes('product', $gridSessionWebsiteId);
        $emarsysCustomProductAttributes = $this->syncResourceModel->getAttributes('customproductattributes', $gridSessionWebsiteId);

        $html = '<select name="directions" class="admin__control-select"  style="width:200px;" onchange="changeValue(\'' . $url . '\', \'' . $attributeCode . '\', \'' . $coulmnAttr . '\', this.value);">
           <option value="0">Please Select</option>';
        foreach ($emarsysProductAttributes as $prodValue) {
            $sel = '';
            if ($colValue == $prodValue['id']) {
                $sel = 'selected == selected';
            }
            $html .= '<option ' . $sel . ' value="' . $prodValue['id'] . '">' . $prodValue['label'] . '</option>';
        }
        if ((count($emarsysProductAttributes) > 0) && (count($emarsysCustomProductAttributes) > 0)) {
            $html .= "<option disabled>----Custom Attributes----</option>'";
            foreach ($emarsysCustomProductAttributes as $customAttribute) {
                $sel = '';
                $customId = $customAttribute['id'] . "_CUSTOM";
                if ($colValue == $customId) {
                    $sel = 'selected == selected';
                }
                $html .= '<option ' . $sel . ' value="' . $customId . '">' . $customAttribute['description'] . '</option>';
            }
        }
        $html .= '</select>';

        return $html;
    }
}
