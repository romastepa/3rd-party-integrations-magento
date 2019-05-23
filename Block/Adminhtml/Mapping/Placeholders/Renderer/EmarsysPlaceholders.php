<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Placeholders\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

/**
 * Class EmarsysPlaceholders
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping\Placeholders\Renderer
 */
class EmarsysPlaceholders extends AbstractRenderer
{
    /**
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        static $i = 0;
        ?>
        <textarea name="emarsys_placeholder_name" id="<?php printf($row->getData("id")); ?>">
                <?php printf($row->getData("emarsys_placeholder_name")); ?>
            </textarea>
        <div class="placeholder-error validation-advice" id="<?php printf ("divErrPlaceholder_" . $i); ?>"
             style="display:none; color:red">Placeholders can only have
            Alphanumerics
            and Underscores.
        </div>
        <?php
        $i++;
    }
}
