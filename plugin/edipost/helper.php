<?php

require_once(_PS_MODULE_DIR_.'/edipost/lib/php-rest-client/init.php');


class AdminEdipostHelper {
    private $_apiData;
    private $_api;
    static function getApiConfig(){
        $config = array(
            'EDIPOST_PRODUCTION_MODE' => Configuration::get('EDIPOST_PRODUCTION_MODE', true),
            'EDIPOST_API_KEY' => Configuration::get('EDIPOST_API_KEY', ''),
            'EDIPOST_USERNAME' => Configuration::get('EDIPOST_USERNAME', null),
            'EDIPOST_PASSWORD' => Configuration::get('EDIPOST_PASSWORD', null),
            'EDIPOST_API_ENDPOINT' => Configuration::get('EDIPOST_PRODUCTION_MODE', null) ? 'https://api.pbshipment.com' : 'https://api.pbshipment.com',
            'EDIPOST_LAST_SHIPPING_METHOD' => Configuration::get('EDIPOST_LAST_SHIPPING_METHOD', 0),
        );
        if(!$config['EDIPOST_API_KEY']){
            throw new Exception('API key cannot be empty. Please contact support.');
        }
        return $config;
    }

    static function loadCustomerAddress($order)
    {
        $address = new Address($order->id_address_delivery);
        $customer = new Customer($order->id_customer);

        $countryID = Country::getIdByName($customer->id_lang, $address->country);
        $counnry_iso = Country::getIsoById($countryID);

        return array(
            'street' => $address->address1,
            'postcode' => $address->postcode,
            'city' => $address->city,
            'firstname' => $address->firstname,
            'lastname' => $address->lastname,
            'email' => $customer->email,
            'telephone' => $address->phone ? $address->phone : $address->phone_mobile,
            'fax' => '',
            'id_lang' => $customer->id_lang,
            'country_id' => $counnry_iso,
            'company' => $address->company ? $address->company : $address->firstname.' '. $address->lastname
        );
    }

    static function getShippingAdress($order)
    {

        $customerAddr = self::loadCustomerAddress($order);
        $fromCountry = new Country(Configuration::get('PS_COUNTRY_DEFAULT'), Configuration::get('PS_LANG_DEFAULT'));

        return [
            'toCountryCode' => $customerAddr['country_id'],
            'toZipCode' => $customerAddr['postcode'] ? $customerAddr['postcode'] : '',
            'fromCountryCode' => $fromCountry->iso_code,
            'fromZipCode' => Configuration::get('PS_SHOP_CODE'),
        ];
    }

}