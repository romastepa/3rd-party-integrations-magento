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
use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
use Emarsys\Emarsys\Model\Api\Api as EmarsysModelApiApi;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

/**
 * Class Country
 * @package Emarsys\Emarsys\Helper
 */
class Country extends AbstractHelper
{
    protected $_mapping = [];

    /**
     * @var Data
     */
    protected $emarsysHelper;

    /**
     * @var EmarsysModelApiApi
     */
    protected $api;

    /**
     * @var StoreManager
     */
    protected $storeManager;

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
     *
     * @param Context $context
     * @param CountryCollectionFactory $countryFactory
     * @param Data $emarsysHelper
     * @param EmarsysModelApiApi $api
     * @param StoreManager $storeManager
     */
    public function __construct(
        Context $context,
        CountryCollectionFactory $countryFactory,
        EmarsysHelper $emarsysHelper,
        EmarsysModelApiApi $api,
        StoreManager $storeManager
    ) {
        $this->context = $context;
        $this->_countryFactory = $countryFactory;
        $this->emarsysHelper = $emarsysHelper;
        $this->api = $api;
        $this->storeManager = $storeManager;

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

        $store = $this->storeManager->getStore($storeId);
        $this->api->setWebsiteId($store->getWebsiteId());
        $response = $this->api->sendRequest('GET', 'field/14/choice/translate/en');

        if (isset($response['body']['data'])) {
            foreach ($response['body']['data'] as $item) {
                $name = $item['choice'];
                $id = $item['id'];
                if (isset($countries[$name])) {
                    $_magentoCountryId = $countries[$name];
                    $mappedCountries[$_magentoCountryId] = $id;
                } elseif (isset($this->_overrides[$name])) {
                    $_magentoCountryId = $this->_overrides[$name];
                    $mappedCountries[$_magentoCountryId] = $id;
                }
            }
        }

        return $mappedCountries;
    }

    /**
     * @param $store
     * @return array
     * @throws \Zend_Json_Exception
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
