<?php
/**
 * 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

require_once(_PS_MODULE_DIR_ . '/edipost/lib/php-rest-client/init.php');

use PrestaShop\PrestaShop\Adapter\Module\Module;

class AdminEdipostHelper
{
    public static function getApiConfig()
    {
        $config = array(
            'EDIPOST_PRODUCTION_MODE' => Configuration::get('EDIPOST_PRODUCTION_MODE', true),
            'EDIPOST_API_KEY' => Configuration::get('EDIPOST_API_KEY', ''),
            'EDIPOST_USERNAME' => Configuration::get('EDIPOST_USERNAME', null),
            'EDIPOST_PASSWORD' => Configuration::get('EDIPOST_PASSWORD', null),
            'EDIPOST_API_ENDPOINT' => Configuration::get('EDIPOST_PRODUCTION_MODE', null) ?
                'https://api.pbshipment.com' : 'https://api.pbshipment.com',
            'EDIPOST_LAST_SHIPPING_METHOD' => Configuration::get('EDIPOST_LAST_SHIPPING_METHOD', 0),
        );
        return $config;
    }

    public static function loadCustomerAddress($order)
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
            'company' => $address->company ? $address->company : $address->firstname . ' ' . $address->lastname
        );
    }

    public static function getShippingAdress($order)
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
