<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\System\Config\Button;

/**
 * Class CatalogApiTestConnection
 * @package Emarsys\Emarsys\Block\System\Config\Button
 */
class CatalogApiTestConnection extends AbstractButton
{
    /**
     * Path to block template
     */
    const TEST_CONNECTION_TEMPLATE = 'system/config/button/catalog_api_test_connection.phtml';

    /**
     * Set template to itself
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::TEST_CONNECTION_TEMPLATE);
        }
        return $this;
    }

    /**
     * @param $websiteId
     * @return string
     */
    protected function getAjaxActionUrl($websiteId)
    {
        return $this->getUrl("emarsys_emarsys/testconnection/catalogapitestconnection", ["website" => $websiteId]);
    }
}
