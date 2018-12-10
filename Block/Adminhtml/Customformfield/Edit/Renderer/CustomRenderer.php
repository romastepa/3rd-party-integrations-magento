<?php
namespace Emarsys\Emarsys\Block\Adminhtml\Customformfield\Edit\Renderer;

/**
 * Class CustomRenderer
 * @package Emarsys\Emarsys\Block\Adminhtml\Customformfield\Edit\Renderer
 */
class CustomRenderer extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    /**
     * CustomRenderer constructor.
     * @param \Magento\Framework\Data\Form\Element\Factory $factoryElement
     * @param \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Framework\App\Request\Http $http
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Catalog\Model\Category $category
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Data\Form\Element\Factory $factoryElement,
        \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\App\Request\Http $http,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\Category $category,
        array $data = []
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->http = $http;
        $this->categoryFactory = $categoryFactory;
        $this->category = $category;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getElementHtml()
    {
        $rootCategoryId = 1;
        list($catTree, $selectedCats) = $this->getTreeCategories($rootCategoryId);
        $html = "
        <div class=\"emarsys-search\">
    <div class=\"category-multi-select\">
        <div class='admin__field'><div class='admin__field-control'>
     <div class='admin__action-multiselect-wrap action-select-wrap admin__action-multiselect-tree'>
<div class='admin__action-multiselect' id='selectedCategories'>$selectedCats</div></div></div>
</div>";
        $html .=
            " <ul><li>
        <div class= 'catg-sub-'><input type= 'checkbox' disabled = 'disabled'name= 'dummy-checkbox'/>Root Category</div>" . $catTree;

        return $html;
    }

    /**
     * @param $parentId
     * @param int $level
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getTreeCategories($parentId, $level = 1)
    {
        $website = $this->http->getParam('website');
        $website = $this->storeManagerInterface->getWebsite($website);
        $categoriesExcluded = $website->getConfig('emarsys_predict/feed_export/excludedcategories');
        $categoriesExcluded = explode(',', $categoriesExcluded);
        $allCategories = $this->categoryFactory->create()->getCollection()
            ->addAttributeToFilter('parent_id', ['eq' => $parentId]);
        $selectedCats = '';
        $html = '<ul class="category-' . $level . '">';

        foreach ($allCategories as $cat) {
            $checked = '';
            $category = $this->category->load($cat->getId());
            if (in_array($cat->getId(), $categoriesExcluded)) {
                $checked = 'checked=checked';
            }
            $level = $category->getLevel();
            $subcats = $category->getChildren();
            $html .= '<li class="catg-sub-$categoryLevel' . $category->getLevel() . '">';
            $html .= "<div class=\"catg-sub-$level \"><input type=\"checkbox\" $checked disabled=\"disabled\" name= \"checkbox\" onclick=\"categoryClick(this.value,'" . $category->getName() . "')\" id=\"catCheckBox_" . $category->getId() . "\" name=\"vehicle\" value=\"" . $category->getId() . "\">" . $category->getName() . "</div>";
            if ($checked != '') {
                $selectedCats .= '<span id="' . $category->getId() . '" onclick="Unchecked(' . $category->getId() . ')" class="admin__action-multiselect-crumb">' . $category->getName() . '</span>';
            }
            if (count(array_filter(explode(",", $subcats))) > 0) {
                list($catTree, $selectedSubCats) = $this->getTreeCategories($category->getId(), $level);
                $html .= $catTree;
                $selectedCats .= $selectedSubCats;
            }
            $html .= '</li>';
        }
        $html .= '</ul>';

        return [$html, $selectedCats];
    }
}
