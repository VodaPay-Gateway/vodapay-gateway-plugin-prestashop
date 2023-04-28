<?php
/**
* 2007-2023 PrestaShop
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2023 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Vodapaygatewaypaymentmodule extends PaymentModule
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'vodapaygatewaypaymentmodule';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'VodaPay Development';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('VodaPay Gateway for Prestashop');
        $this->description = $this->l('This plugin allows ecommerce merchants to accept online payments from customers.');

        $this->limited_countries = array('ZA');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (extension_loaded('curl') == false)
        {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        $iso_code = Country::getIsoById(Configuration::get('PS_COUNTRY_DEFAULT'));

        Configuration::updateValue('VODAPAYGATEWAYPAYMENTMODULE_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('payment') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('displayPayment')&&
            $this->registerHook('actionOrderStatusPostUpdate')&&
            $this->registerHook('actionOrderStatusUpdate')&&
            $this->alterOrderTable();
    }

    public function uninstall()
    {
        Configuration::deleteByName('VODAPAYGATEWAYPAYMENTMODULE_LIVE_MODE');

        return parent::uninstall();
    }

    protected function alterOrderTable(){
        if (!Db::getInstance()->Execute('SELECT transaction_id from '._DB_PREFIX_.'orders'))
       { 
           if (!Db::getInstance()->Execute('ALTER TABLE '._DB_PREFIX_.'orders ADD `transaction_id` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL'))
           return false;
       }
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitVodapaygatewaypaymentmoduleModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
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
        $helper->submit_action = 'submitVodapaygatewaypaymentmoduleModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
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
                        'label' => $this->l('Live mode'),
                        'name' => 'VODAPAYGATEWAYPAYMENTMODULE_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    [
                        $options = [
                            [
                                'id_option' => 1,
                                'name' => 'Sandbox Testing'
                            ],
                            [
                                'id_option' => 2,
                                'name' => 'UAT Testing'
                            ],
                            [
                                'id_option' => 3,
                                'name' => 'PROD'
                            ],
                        ],
                        'type' => 'select',
                        'label' => $this->l('Environment'),
                        'name' => 'VODAPAYGATEWAYPAYMENTMODULE_CONFIG_ENV',
                        'options' => [
                            'query' => $options,
                            'id' => 'id_option',
                            'name' => 'name'
                        ], 
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('UAT API Key'),
                        'name' => 'VODAPAYGATEWAYPAYMENTMODULE_UAT_API_KEY',
                        'size' => 20,
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('PROD API Key'),
                        'name' => 'VODAPAYGATEWAYPAYMENTMODULE_PROD_API_KEY',
                        'size' => 20,
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Merchant Logo URL'),
                        'name' => 'VODAPAYGATEWAYPAYMENTMODULE_MERCHANT_LOGO_URL',
                        'size' => 20,
                        'required' => false,
                    ],[
                        'type'=>'text',
                        'label'=> $this->l('Merchant Message URL'),
                        'name'=> 'VODAPAYGATEWAYPAYMENTMODULE_MERCHANT_MESSAGE_URL',
                        'size'=>20,
                        'required'=>false,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Notification URL'),
                        'name' => 'VODAPAYGATEWAYPAYMENTMODULE_NOTIFICATION_URL',
                        'size' => 20,
                        'required' => false,
                    ],
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
            'VODAPAYGATEWAYPAYMENTMODULE_LIVE_MODE' => Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_LIVE_MODE', true),
            'VODAPAYGATEWAYPAYMENTMODULE_ACCOUNT_EMAIL' => Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_ACCOUNT_EMAIL', 'support@vodacom.co.za'),
            'VODAPAYGATEWAYPAYMENTMODULE_ACCOUNT_PASSWORD' => Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_ACCOUNT_PASSWORD', null),
            'VODAPAYGATEWAYPAYMENTMODULE_CONFIG_ENV'=>Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_CONFIG_ENV'),
            'VODAPAYGATEWAYPAYMENTMODULE_UAT_API_KEY'=>Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_UAT_API_KEY'),
            'VODAPAYGATEWAYPAYMENTMODULE_PROD_API_KEY'=>Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_PROD_API_KEY'),
            'VODAPAYGATEWAYPAYMENTMODULE_MERCHANT_LOGO_URL'=>Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_MERCHANT_LOGO_URL'),
            'VODAPAYGATEWAYPAYMENTMODULE_MERCHANT_MESSAGE_URL'=>Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_MERCHANT_MESSAGE_URL'),
            'VODAPAYGATEWAYPAYMENTMODULE_NOTIFICATION_URL'=>Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_NOTIFICATION_URL'),
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
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    /**
     * This method is used to render the payment button,
     * Take care if the button should be displayed or not.
     */
    public function hookPayment($params)
    {
        $currency_id = $params['cart']->id_currency;
        $currency = new Currency((int)$currency_id);

        $this->smarty->assign('module_dir', $this->_path);

        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    /**
     * This hook is used to display the order confirmation page.
     */
    public function hookPaymentReturn($params)
    {
        if ($this->active == false)
            return;

        
        $order = $params['order'];


        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')){
            $this->smarty->assign('status', 'ok');
            print_r($this->context);
        }
            
        $this->smarty->assign(array(
            'id_order' => $order->id,
            'reference' => $_GET['referenceId'],
            'params' => $params,
            'shopName' =>[$this->context->shop->name],
            'responseMsg'=> [$_GET['responseMsg']],
            'total' => Tools::displayPrice($order->total_paid, $order->id_currency, false),
        ));

        return $this->display(__FILE__, 'views/templates/hook/confirmation.tpl');
    }

    /**
     * Return payment options available for PS 1.7+
     *
     * @param array Hook parameters
     *
     * @return array|null
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        

        $paymentOptions =[
            $this->getExternalPaymentOption($params),
        ];

        return $paymentOptions;
    }

    public function getExternalPaymentOption($params)
    {   
        $externalOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $testHeader = 'false';
        if (Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_CONFIG_ENV') == '1'){
            $testHeader = 'true'; 
        }
        if(Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_CONFIG_ENV') == '3'){
            $APIKey = Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_PROD_API_KEY');
            $gatewayURL = 'https://api.vodapaygateway.vodacom.co.za/V2/Pay/OnceOff';
        }else{
            $APIKey = Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_UAT_API_KEY');
            $gatewayURL = 'https://api.vodapaygatewayuat.vodacom.co.za/V2/Pay/OnceOff';
        }

        
        $data = ['params'=>$params,'APIKey'=>$APIKey,'test'=>$testHeader,'gatewayURL'=>$gatewayURL,'name'=>$this->name];
        $externalOption->setCallToActionText($this->l('Pay with VodaPay'))
                        ->setAdditionalInformation($this->display(__FILE__, 'views/templates/front/paymentInfos.tpl'))
                       ->setAction($this->context->link->getModuleLink($this->name, 'handlepayment', $data, true));
                       
        return $externalOption;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }


    public function hookDisplayPayment()
    {
        return $this->display(__FILE__, _PS_MODULE_DIR_.$this->name.'views/templates/hook/displayPayment.tpl');
    }

    public function hookActionOrderStatusUpdate($params)
    {
        echo "<script type='text/javascript'>alert('this works');</script>";
        print_r('this works');
        //return $this->context->link->getModuleLink($this->name, 'handlerefund', $data, true);
    }

    public function hookactionOrderStatusPostUpdate(){
        echo "<script type='text/javascript'>alert('this other one works');</script>";
        print_r('this other one works');
    }

}
