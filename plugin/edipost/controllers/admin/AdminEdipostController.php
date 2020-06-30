<?php

require_once(_PS_MODULE_DIR_.'/edipost/edipost.php');
require_once(_PS_MODULE_DIR_.'/edipost/helper.php');
require_once(_PS_MODULE_DIR_.'/edipost/lib/php-rest-client/EdipostService.php');

//use Order;
//use Configuration;
//use Address;
//use Customer;
//use OrderCarrier;

//use AdminEdipostHelper;

use EdipostService\EdipostService;
use EdipostService\Client\Builder\ConsigneeBuilder;
use EdipostService\Client\Builder\ConsignmentBuilder;
use EdipostService\Client\Item;

class AdminEdipostController extends ModuleAdminController
{
    private $_apiData;
    private $_api;
    private $_path;

    public function __construct()
    {
        $this->_apiData = AdminEdipostHelper::getApiConfig();
        $this->_apiData['web_app_url'] = 'https://no.pbshipment.com';

        $this->_api = new EdipostService( $this->_apiData['EDIPOST_API_KEY'], $this->_apiData['EDIPOST_API_ENDPOINT'] );
        $this->_path = _PS_IMG_DIR_;

        parent::__construct();
    }

    public function displayAjaxOpenUrl()
    {
        if (!isset($_POST['id_order'])) {
            throw new Exception($this->module->l('Wrong params'));
        }

        $error = '';
        $url = '%s/login?Username=%s&Password=%s#id=%s';

        $order_id = $_POST['id_order'];
        $order = new Order($order_id);
        $shippingAddressArray = AdminEdipostHelper::loadCustomerAddress($order);

        $builder = new ConsigneeBuilder();

        $consignee = $builder
            ->setCompanyName( $shippingAddressArray['company'] )
            ->setCustomerNumber( '0' )
            ->setPostAddress( $shippingAddressArray['street'] )
            ->setPostZip( $shippingAddressArray['postcode'] )
            ->setPostCity( $shippingAddressArray['city'] )
            ->setStreetAddress( $shippingAddressArray['street'] )
            ->setStreetZip( $shippingAddressArray['postcode'] )
            ->setStreetCity( $shippingAddressArray['city'] )
            ->setContactName($shippingAddressArray['firstname'].' '. $shippingAddressArray['lastname'] )
            ->setContactEmail( $shippingAddressArray['email'] )
            ->setContactPhone( $shippingAddressArray['telephone'] )
            ->setContactCellPhone( $shippingAddressArray['telephone'] )
            ->setContactTelefax( $shippingAddressArray['fax'] )
            ->setCountry( $shippingAddressArray['country_id'] )
            ->build();
        $consigneeId = 0;
        try {
            $newConsignee = $this->_api->createConsignee( $consignee );
            $consigneeId =  $newConsignee->ID;
        } catch (WebException $exception){
            $error = $exception->getMessage();
        }
        $url = sprintf($url, $this->_apiData['web_app_url'],  $this->_apiData['EDIPOST_USERNAME'], $this->_apiData['EDIPOST_PASSWORD'], $consigneeId);

        echo(json_encode([
            'error' => $error,
            'url' => $url,
        ]));
    }


    public function displayAjaxCreateShipment()
    {
        if (!isset($_POST['id_order'])) {
            throw new Exception($this->module->l('Wrong params'));
        }

        $error = '';

        $order_id = isset($_POST['id_order']) ? $_POST['id_order'] :  0;
        $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : 0;
        $service_id = isset($_POST['service_id']) ? $_POST['service_id'] : 0;
        $e_alert = isset($_POST['e_alert']) ? $_POST['e_alert'] : 0;
        $reference =  isset($_POST['reference']) ? $_POST['reference'] : '';

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
            $newConsignee = $this->_api->createConsignee($consignee);
            $consigneeId = $newConsignee->ID;

            $builder = new ConsignmentBuilder();

            $consignor = $this->_api->getDefaultConsignor();

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

                if (!($weight = floatval($product['weight']))) {
                    $weight = 1;
                }

                $consignment->addItem(new Item($weight, $length, $width, $height));
            }

            if ($e_alert && in_array(intval($product_id), [8, 457, 16])) {
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
            if( ($product_id == 454 || $product_id == 747) && $service_id > 0 ) {
                $consignment->addService( intval($service_id) );
            }

            $newConsignment = $this->_api->createConsignment($consignment->build());

            $orderCarrier = new OrderCarrier($order->getIdOrderCarrier());


            // Set tracking number
            $orderCarrier->tracking_number = $newConsignment->shipmentNumber;
            $orderCarrier->save();

            //
            // Print label
            //
            if ($product_id == 727) {
                $pdf_content = $this->_api->printConsignmentZpl($newConsignment->id);
            } else {
                $pdf_content = $this->_api->printConsignment($newConsignment->id);
            }

            $pdf_name = $newConsignment->id . '_consignment.pdf';

            file_put_contents($this->_path. DIRECTORY_SEPARATOR . $pdf_name, $pdf_content);

            $pdf = (Tools::usingSecureMode() ? Tools::getShopDomainSsl(true) : Tools::getShopDomain(true)) .  '/img/'. $pdf_name;

        } catch (WebException $exception) {    // Errors from edipost client library
            $error = $exception->getMessage();
            echo(json_encode([
                'error' => $error
            ]));

        } catch (\Exception $exception) {    // Other errors
            $error = $exception->getMessage();
            echo(json_encode([
                'error' => $error
            ]));
        }

        echo(json_encode([
            'error' => $error,
            'product_id' => $product_id,
            'pdf' => $pdf,
        ]));
    }
}