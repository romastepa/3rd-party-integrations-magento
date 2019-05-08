<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CountryCollectionFactory;

/**
 * Class Country
 * @package Emarsys\Emarsys\Helper
 */
class Country extends AbstractHelper
{
    protected $_mapping = [];

    protected $_overrides = [
        'Hong Kong'         => 'HK',
        'Macau'             => 'MO',
        'Brunei Darussalam' => 'BN',
        'Cote d\'Ivoire'    => 'CI',
        'Gambia, The'       => 'GM',
        'Congo'             => 'CG',
        'Congo, Democratic Republic of the' => 'CD',
        'Korea, South'      => 'KR',
        'Korea, North'      => 'KP',
        'Myanmar'           => 'MM',
        'The Netherlands'   => 'NL',
        'St. Kitts and Nevis' => 'KN',
        'St. Lucia'         => 'LC',
        'St. Vincent and The Grenadines' => 'VC',
        'United States of America' => 'US',
    ];

    /**
     * Country constructor.
     * @param Context $context
     * @param Data $helper
     * @param CountryCollectionFactory $countryFactory
     */
    public function __construct(
        Context $context,
        Data $helper,
        CountryCollectionFactory $countryFactory
    ) {
        $this->context = $context;
        $this->helper = $helper;
        $this->_countryFactory = $countryFactory;

        parent::__construct($context);
    }

    /**
     * @param $storeId
     * @return array
     * @throws \Exception
     * @throws \Zend_Json_Exception
     */
    protected function _getMapping($storeId)
    {
        $countries = [];
        $mappedCountries = [];
        $magentoCountries = $this->_countryFactory->create();

        foreach ($magentoCountries as $country) {
            $countries[$country->getName()] = $country->getId();
        }

        $this->helper->getEmarsysAPIDetails($storeId);
        $data = $this->helper->send('GET', 'field/14/choice/translate/en');
        $data = \Zend_Json::decode($data);

        if (isset($data['data'])) {
            foreach ($data['data'] as $item) {
                $name = $item['choice'];
                $id = $item['id'];
                if (isset($countries[$name])) {
                    $_magentoCountryId = $countries[$name];
                    $mappedCountries[$_magentoCountryId] = $id;
                } elseif (isset($this->_overrides[$item['choice']])) {
                    $_magentoCountryId = $this->_overrides[$item['choice']];
                    $mappedCountries[$_magentoCountryId] = $id;
                }
            }
        }

        return $mappedCountries;
    }

    /**
     * @param $store
     * @return array
     */
    public function getMapping($store)
    {
        if (empty($this->_mapping)) {
            $mapping = $this->_getMapping($store);
            $this->_mapping = $mapping;
        }

        return $this->_mapping;
    }
}
