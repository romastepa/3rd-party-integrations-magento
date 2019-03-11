<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Config\Backend;

class Custom extends \Magento\Framework\App\Config\Value
{
    public function beforeSave()
    {
        $password = $this->getData()['groups']['emarsys_setting']['fields']['emarsys_api_password']['value'];
        $cpassword = $this->getData()['groups']['emarsys_setting']['fields']['confirm_password']['value'];
        $newValue = $this->getValue();
        if (empty($newValue)) {
            $this->setValue($this->getOldValue());
        }
        if ($password != $cpassword) {
            $this->setData('groups/emarsys_setting/fields/confirm_password/value', $newValue);
        }
        parent::beforeSave();
    }
}
