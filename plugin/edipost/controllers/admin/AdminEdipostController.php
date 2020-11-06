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

require_once(_PS_MODULE_DIR_ . '/edipost/edipost.php');
require_once(_PS_MODULE_DIR_ . '/edipost/helper.php');
require_once(_PS_MODULE_DIR_ . '/edipost/lib/php-rest-client/EdipostService.php');

use EdipostService\Client\Builder\ConsigneeBuilder;
use EdipostService\Client\Builder\ConsignmentBuilder;
use EdipostService\Client\Item;
use EdipostService\EdipostService;

class AdminEdipostController extends ModuleAdminController
{
    private $apiData;
    private $api;
    public $path;

    public function __construct()
    {
        $this->apiData = AdminEdipostHelper::getApiConfig();
        $this->apiData['web_app_url'] = 'https://no.pbshipment.com';
        $this->api = new EdipostService($this->apiData['EDIPOST_API_KEY'], $this->apiData['EDIPOST_API_ENDPOINT']);
        $this->path = _PS_IMG_DIR_;

        parent::__construct();
    }

    public function displayAjaxOpenUrl()
    {
        if (!Tools::getIsset('id_order')) {
            throw new Exception($this->module->l('Wrong params'));
        }

        $error = '';
        $full_error = '';
        $url = '%s/login?Username=%s&Password=%s#id=%s';

        $order_id = Tools::getValue('id_order', 0);
        $order = new Order($order_id);
        $shippingAddressArray = AdminEdipostHelper::loadCustomerAddress($order);

        $builder = new ConsigneeBuilder();

        $consignee = $builder
            ->setCompanyName($shippingAddressArray['company'])
            ->setCustomerNumber('0')
            ->setPostAddress($shippingAddressArray['street'])
            ->setPostZip($shippingAddressArray['postcode'])
            ->setPostCity($shippingAddressArray['city'])
            ->setStreetAddress($shippingAddressArray['street'])
            ->setStreetZip($shippingAddressArray['postcode'])
            ->setStreetCity($shippingAddressArray['city'])
            ->setContactName($shippingAddressArray['firstname'] . ' ' . $shippingAddressArray['lastname'])
            ->setContactEmail($shippingAddressArray['email'])
            ->setContactPhone($shippingAddressArray['telephone'])
            ->setContactCellPhone($shippingAddressArray['telephone'])
            ->setContactTelefax($shippingAddressArray['fax'])
            ->setCountry($shippingAddressArray['country_id'])
            ->build();
        $consigneeId = 0;
        try {
            $newConsignee = $this->api->createConsignee($consignee);
            $consigneeId = $newConsignee->ID;
        } catch (WebException $exception) {
            $error = $this->module->l('Error when open consignment');
            $full_error = $exception->getMessage();
        }
        $url = sprintf(
            $url,
            $this->apiData['web_app_url'],
            $this->apiData['EDIPOST_USERNAME'],
            $this->apiData['EDIPOST_PASSWORD'],
            $consigneeId
        );

        echo(json_encode([
            'is_error' => $error,
            'full_error' => $full_error,
            'url' => $url,
        ]));
    }

    public function displayAjaxCreateShipment()
    {
        if (!Tools::getIsset('id_order')) {
            throw new Exception($this->module->l('Wrong params'));
        }

        $error = '';
        $full_error = '';
        $return = [];

        $order_id = Tools::getValue('id_order', 0);
        $product_id = Tools::getValue('product_id', 0);
        $service_id = Tools::getValue('service_id', 0);
        $e_alert = Tools::getValue('e_alert', 0);
        $reference = Tools::getValue('reference', '');

        $order = new Order($order_id);
        $shippingAddressArray = AdminEdipostHelper::loadCustomerAddress($order);

        Configuration::updateValue('EDIPOST_LAST_SHIPPING_METHOD', $service_id . '_' . $product_id);
        //
        // Create consignee
        //

        $builder = new ConsigneeBuilder();

        $consignee = $builder
            ->setCompanyName($shippingAddressArray['company'])
            ->setCustomerNumber('0')
            ->setPostAddress($shippingAddressArray['street'])
            ->setPostZip($shippingAddressArray['postcode'])
            ->setPostCity($shippingAddressArray['city'])
            ->setStreetAddress($shippingAddressArray['street'])
            ->setStreetZip($shippingAddressArray['postcode'])
            ->setStreetCity($shippingAddressArray['city'])
            ->setContactName($shippingAddressArray['firstname'] . ' ' . $shippingAddressArray['lastname'])
            ->setContactEmail($shippingAddressArray['email'])
            ->setContactPhone($shippingAddressArray['telephone'])
            ->setContactCellPhone($shippingAddressArray['telephone'])
            ->setContactTelefax($shippingAddressArray['fax'])
            ->setCountry($shippingAddressArray['country_id'])
            ->build();

        $pdf = '';
        try {
            $newConsignee = $this->api->createConsignee($consignee);
            $consigneeId = $newConsignee->ID;

            $builder = new ConsignmentBuilder();

            $consignor = $this->api->getDefaultConsignor();

            $consignment = $builder
                ->setConsignorID($consignor->ID)
                ->setConsigneeID($consigneeId)
                ->setProductID($product_id)
                ->setTransportInstructions('')
                ->setContentReference($reference)
                ->setInternalReference('');

            foreach ($order->getProducts() as $product) {
                $weight = 1;
                $length = 0;
                $width = 0;
                $height = 0;

                if (!($weight = (float)$product['weight'])) {
                    $weight = 1;
                }

                $consignment->addItem(new Item($weight, $length, $width, $height));
            }

            if ($e_alert && in_array((int)$product_id, [8, 457, 16])) {
                // Add SMS warning
                if ($shippingAddressArray['telephone']) {
                    $consignment->addService(5, array('EMSG_SMS_NUMBER' => $shippingAddressArray['telephone']));
                }

                // Add e-mail warning
                if ($shippingAddressArray['email']) {
                    $consignment->addService(6, array('EMSG_EMAIL' => $shippingAddressArray['email']));
                }
            }

            // Add correct service if product is REK
            if (($product_id == 454 || $product_id == 456
                    || $product_id == 747 || $product_id == 757) && $service_id > 0) {
                $consignment->addService((int)$service_id);
            }

            $newConsignment = $this->api->createConsignment($consignment->build());

            $orderCarrier = new OrderCarrier($order->getIdOrderCarrier());


            // Set tracking number
            $orderCarrier->tracking_number = $newConsignment->shipmentNumber;
            $orderCarrier->save();

            //
            // Print label
            //
            if ($product_id == 727) {
                $pdf_content = $this->api->printConsignmentZpl($newConsignment->id);
            } else {
                $pdf_content = $this->api->printConsignment($newConsignment->id);
            }
            $pdf_name = $newConsignment->id . '_consignment.pdf';
            file_put_contents($this->path . DIRECTORY_SEPARATOR . $pdf_name, $pdf_content);
            $pdf = (Tools::usingSecureMode() ? Tools::getShopDomainSsl(true) : Tools::getShopDomain(true))
                . '/img/' . $pdf_name;
        } catch (WebException $exception) {    // Errors from edipost client library
            $error = $this->module->l('Error when creating consignment');
            $full_error = $exception->getMessage();
            $return = [
                'is_error' => $error,
                'full_error' => $full_error
            ];
        } catch (\Exception $exception) {    // Other errors
            $error = $this->module->l('Error when creating consignment');
            $full_error = $exception->getMessage();
            $return = [
                'is_error' => $error,
                'full_error' => $full_error
            ];
        }
        if (!$return) {
            $return = [
                'is_error' => $error,
                'full_error' => $full_error,
                'product_id' => $product_id,
                'pdf' => $pdf,
            ];
        }
        echo(json_encode($return));
    }
}
