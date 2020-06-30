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

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(_PS_MODULE_DIR_.'/edipost/helper.php');
require_once(_PS_MODULE_DIR_.'/edipost/lib/php-rest-client/EdipostService.php');

use EdipostService\EdipostService;


class Edipost extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'edipost';
        $this->tab = 'others';
        $this->version = '1.0.0';
        $this->author = 'Edipost';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Edipost');
        $this->description = $this->l('Edipost IntegrationUser able to print shipping labels from an order)');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->controllers = array('ajax');
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('EDIPOST_PRODUCTION_MODE', true);
        Configuration::updateValue('EDIPOST_API_KEY', '');
        Configuration::updateValue('EDIPOST_USERNAME', null);
        Configuration::updateValue('EDIPOST_PASSWORD', null);
        Configuration::updateValue('EDIPOST_LAST_SHIPPING_METHOD', 0);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayAdminOrderRight');

    }


    public function uninstall()
    {
        Configuration::deleteByName('EDIPOST_PRODUCTION_MODE');
        Configuration::deleteByName('EDIPOST_API_KEY');
        Configuration::deleteByName('EDIPOST_USERNAME');
        Configuration::deleteByName('EDIPOST_PASSWORD');
        Configuration::deleteByName('EDIPOST_LAST_SHIPPING_METHOD');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitEdipostModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        return $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEdipostModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Production mode'),
                        'name' => 'EDIPOST_PRODUCTION_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Development or Production environment'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Production')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Development')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon "></i>',
                        'desc' => $this->l('Enter a valid api key'),
                        'name' => 'EDIPOST_API_KEY',
                        'label' => $this->l('API key'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'EDIPOST_USERNAME',
                        'label' => $this->l('Username'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'EDIPOST_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'EDIPOST_PRODUCTION_MODE' => Configuration::get('EDIPOST_PRODUCTION_MODE', true),
            'EDIPOST_API_KEY' => Configuration::get('EDIPOST_API_KEY', ''),
            'EDIPOST_USERNAME' => Configuration::get('EDIPOST_USERNAME', null),
            'EDIPOST_PASSWORD' => Configuration::get('EDIPOST_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
            $this->context->controller->addJquery();
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');

        Media::addJsDef(array(
            'psr_controller_edipost_url' => $this->context->link->getAdminLink('AdminEdipost')

        ));
    }

    public function hookdisplayAdminOrderRight($param)
    {

        $order = new Order($param['id_order']);
        $customer_id = $order->id_customer;

        $error_text = '';
        $shipping_methods = $this->getShippingMethods($order);

        if($shipping_methods['error']){
            $error_text = $shipping_methods['error'];
        }

        $this->context->smarty->assign([
            'module_dir' => $this->_path,
            'order_id' => $order->id,
            'customer_id' => $customer_id,
            'shipping_methods' => $shipping_methods,
            'error_text' => $error_text,
            'prev_product' => Configuration::get('EDIPOST_LAST_SHIPPING_METHOD', 0),
        ]);


        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/shipment.tpl');

    }

    /**
     * return shipping methods from Edipost Api.
     *
     * @return object
     */
    private function getShippingMethods($order)
    {
        $items = [];
        $error = '';
        $shippingData = AdminEdipostHelper::getShippingAdress($order);
        $options = [ // first disabled element
            [
                'id' => 0,
                'name' => $this->l('-- Select an option --'),
                'status' => $this->l('Available'),
                'service' => ''
                ]
        ];

        foreach ($order->getProducts() as $product) {
            if (!($weight = floatval($product['weight']))) {
                $weight = 1;
            }
            $items[] = [
                'weight' => $weight,
                'length' => '0',
                'width' => '0',
                'height' => '100'
            ];
        }

        try {
            $_apiData = AdminEdipostHelper::getApiConfig();

            $_api = new EdipostService( $_apiData['EDIPOST_API_KEY'], $_apiData['EDIPOST_API_ENDPOINT'] );
            $products = $_api->getAvailableProducts($shippingData['fromZipCode'], $shippingData['fromCountryCode'],
                $shippingData['toZipCode'], $shippingData['toCountryCode'], $items);
            foreach ($products as $product) {
                $services = [];
                foreach ($product->getServices() as $service) {
                    $services[] = $service->getId();
                }

                $options[] = [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'status' => $product->getStatus(),
                    'service' => count($services) > 0 ? $services[0] : ''
                ];
            }

        } catch (WebException $exception) {
            $error = $exception->getMessage();
        } catch (\Exception $exception) {    // Other errors
            $error = $exception->getMessage();
        }

        if(count($options) == 1){
            $error = $this->l('There are no available shipping methods.') . '<br>' . $error;
        }

        return array(
            'options' => $options,
            'error' => $error,
        );
    }
}
