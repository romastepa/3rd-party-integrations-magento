<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml\Support;

use Magento\Backend\Block\Widget\Form\Container;

/**
 * Class Edit
 * @package Emarsys\Emarsys\Block\Adminhtml\Support
 */
class Edit extends Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_blockGroup = 'Emarsys_Emarsys';
        $this->_controller = 'adminhtml_support';
        $this->buttonList->update('save', 'label', __('Submit'));
        $this->buttonList->remove('delete');
        $this->buttonList->remove('back');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __('Support Information');
    }
}

