<?php

require_once(_PS_MODULE_DIR_.'/edipost/lib/php-rest-client/init.php');

//use Order;
//use Country;
//use Configuration;
//use Address;
//use Customer;
//use OrderCarrier;

class AdminEdipostHelper {
    private $_apiData;
    private $_api;
    static function getApiConfig(){
        return array(
            'EDIPOST_PRODUCTION_MODE' => Configuration::get('EDIPOST_PRODUCTION_MODE', true),
            'EDIPOST_API_KEY' => Configuration::get('EDIPOST_API_KEY', ''),
            'EDIPOST_USERNAME' => Configuration::get('EDIPOST_USERNAME', null),
            'EDIPOST_PASSWORD' => Configuration::get('EDIPOST_PASSWORD', null),
            'EDIPOST_API_ENDPOINT' => Configuration::get('EDIPOST_PRODUCTION_MODE', null) ? 'https://api.pbshipment.com' : 'https://api.pbshipment.com',
        );
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

//        $toCountryID = Country::getIdByName($customerAddr['id_lang'], $customerAddr['country_id']);
        return [
            'toCountryCode' =>$customerAddr['country_id'],
            'toZipCode' => $customerAddr['postcode'] ? $customerAddr['postcode'] : '1337',
            'fromCountryCode' => $fromCountry->iso_code,
            'fromZipCode' => Tools::getValue('postcode') ? Tools::getValue('postcode') : '1337',
        ];
    }

}