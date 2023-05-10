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

require_once _PS_MODULE_DIR_ . 'vodapaygatewaypaymentmodule/classes/ReponseCodeConstants.php';

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
            $this->registerHook('displayPayment') &&
            $this->registerHook('actionOrderStatusPostUpdate') &&
            $this->registerHook('actionOrderSlipAdd') &&
            $this->registerHook('actionOrderStatusUpdate' )&&
            $this->registerHook('ActionProductCancel') &&
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
        $responseDetails = array();

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')){
            $responseDetails['status'] = 'ok';
        }

        $responseDetails['id_order'] = $order->id;
        $responseDetails['reference'] = $_GET['referenceId'];
        $responseDetails['params'] = $params;
        $responseDetails['shopName'] =[$this->context->shop->name];
        $responseDetails['responseMsg'] = $_GET['responseMsg'];
        $responseDetails['total'] = Tools::displayPrice($order->total_paid);

        $this->context->smarty->assign($responseDetails);

       return $this->context->smarty->fetch('module:vodapaygatewaypaymentmodule/views/templates/hook/confirmation.tpl');
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

        if(Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_CONFIG_ENV') == '3'){
            $APIKey = Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_PROD_API_KEY');
            $gatewayURL = 'https://api.vodapaygateway.vodacom.co.za/V2/Pay/OnceOff';
        }else{
            $APIKey = Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_UAT_API_KEY');
            $gatewayURL = 'https://api.vodapaygatewayuat.vodacom.co.za/V2/Pay/OnceOff';
            $testHeader = (Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_CONFIG_ENV') == '2')?false:true;
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

    public function hookActionOrderSlipAdd($params)
    {
        $module = 'vodapaygatewaypaymentmodule';
        
        if($params['order']->module == $module){
            $amount = 0;
            foreach ($params['productList'] as $product) {
                $amount += (float) $product['amount'];
            }
            $amount += (float) $this->getRefundShipping(end($params['productList'])['id_order_detail']);
            $amount = (float) number_format((float)$amount, 2, '.', '');
            $refundParams = [
                        'order' => $params['order'],
                        'amount' => $amount,
                    ];
                    
            $refundStatus = $this->refundOrder($refundParams);
            // $this->get('session')->getFlashBag()->add("error", json_encode(  $amount));
            $messageLog = 'VodaPay Gateway - refund response: ' . json_encode($refundStatus);
            PrestaShopLogger::addLog($messageLog, 1, null, 'Order', $params['order']->id, true);
    
            print_r($refundStatus);

            if ($refundStatus == '00') {
                $history = new OrderHistory();
                $history->id_order = (int)$params['order']->id;
                $history->changeIdOrderState(7, $params['order']->id); 
            }
            else{
                $this->resetRefund($params);
                $this->redirectOrderDetail($params['order']->id); 
            }
        }
    }

    protected function redirectOrderDetail($orderId)
    {
        $getAdminLink = $this->context->link->getAdminLink('AdminOrders');
        if (str_contains($getAdminLink, '?')) {
            $eGetAdminLink = explode('?', $getAdminLink);
            $getViewOrder = $eGetAdminLink[0] . $orderId . '/view?' . $eGetAdminLink[1];
        } else {
            $getViewOrder = $getAdminLink . '/&vieworder&id_order=' . $orderId;
        }

        if (Tools::getValue('detailOrderUrl')) {
            Tools::redirectAdmin(Tools::getValue('detailOrderUrl'));
        } else {
            Tools::redirectAdmin($getViewOrder);
        }
    }

    public function hookDisplayPayment()
    {
        return $this->context->smarty->fetch('module:vodapaygatewaypaymentmodule/views/templates/hook/displayPayment.tpl');
    }

    public function hookActionOrderStatusUpdate($params)
    {
        $order = new Order((int) $params['id_order']);
        if (! $this->active || ($order->module != $this->name)) {
            return;
        }

        // echo "<script type='text/javascript'>alert('this works');</script>";
        // print_r('this works');
        //return $this->context->link->getModuleLink($this->name, 'handlerefund', $data, true);
    }

    public function hookActionOrderStatusPostUpdate(){
        // echo "<script type='text/javascript'>alert('this other one works');</script>";
        // print_r('this other one works');
    }

    public function refundOrder($params){
        $gatewayParameters = $this->getGatewayParameters();
        $APIKey = $gatewayParameters[0];
        $gatewayURL = $gatewayParameters[1];
        $testHeader = $gatewayParameters[2];

        try {

            $curl = curl_init();

            $options = $this->prepareOptions($params);

            $messageLog = 'VodaPay Gateway - refund request: ' . json_encode($options);
            PrestaShopLogger::addLog($messageLog, 1, null, 'Order', $params['order']->id, true);

            curl_setopt_array($curl, $options);

            $worked = false;
            $errorMessage = "";

            
            for ( $i=0; $i<3 ; $i++) 
            {
                $result = curl_exec($curl);
               if( $result !== FALSE ) 
               {
                  $worked = TRUE;
                  break;
               }
            }
            
            if($worked == false)
            {
                $errorMessage = error_get_last();
                throw new Exception(implode($errorMessage));
            }
            else{
                
                $response = json_decode($result);
                $responseCode = $response->data->responseCode;
                $responseMessage = $response->data->responseMessage;

                if (in_array($responseCode, ResponseCodeConstants::getGoodResponseCodeList())) {
                    //SUCCESS
                    if ($responseCode == "00") {
                        /*
                        * update order status
                        */ 
                        $type="success";
                        $responseMessage = sprintf("VodaPay refund completed with amount R %s",number_format((float)$params['amount'], 2, '.', ''));
                        $this->get('session')->getFlashBag()->add($type, $responseMessage);
                        return $responseCode;
                    }
                } elseif (in_array($responseCode, ResponseCodeConstants::getBadResponseCodeList())) {
                    //FAILURE
                     /*
                     * report failure, reset order status
                     */
                    $type="error";
                    $this->get('session')->getFlashBag()->add($type, $responseMessage);
                    return $responseCode;
                }else {
                    $type="error";
                    $this->get('session')->getFlashBag()->add($type, $responseMessage);
                    return $responseCode;
                }
            }
        } catch (Exception $e) {
            /*
            *alert error
            */
            $type="error";
            $errorMessage = "An error occurred while processing your refund request: " + $e->getMessage();
            $this->get('session')->getFlashBag()->add($type, $errorMessage);
            return $result;
        }
        finally {
            curl_close($curl);
        }
    }

    public function prepareOptions($params){
        $args =json_encode($this->prepareArgs($params));
        $gatewayParameters = $this->getGatewayParameters();
        $gatewayURL = $gatewayParameters[1];
        $options = [
            CURLOPT_URL => $gatewayURL,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->prepareHeader($gatewayParameters, $args),
            CURLOPT_POSTFIELDS => $args
        ];
        return $options;
    }

    public function prepareHeader($params, $args){
        return ["Content-Type: application/json",
                // "Content-Length: " . strlen($args), 
                "Accept: application/json",
                "api-key: ".$params[0],
                "test: ".$params[2]];
    }

    public function prepareArgs($params){
        $args=[
        'echoData'=> 'Prestashop payment',
        'traceId'=> $this->getTraceId($params['order']->id),
        'originalTransactionId' => $this->getOrderTransId($params['order']),
        'amount'=>  number_format((float)$params['amount']*100, 0, '.', ''),
        'notifications' => $this->getNotifications(),
        ];
        return $args;
    }

    public function getNotifications(){
        
        $notifications = ['notificationUrl' => Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_NOTIFICATION_URL')];
        return $notifications;
    }

    public function getTraceId($oid){
        $rlength = 10;
        $traceId = substr(
            str_shuffle(str_repeat(
                $x = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
                ceil($rlength / strlen($x))
            )),
            1,
            32
        );
        $traceId = str_pad($oid, 12, $traceId, STR_PAD_LEFT);
        return $traceId;
    }

    public function getOrderTransId($order){
        return $order->getOrderPayments()[0]->transaction_id;
    }

    public function getGatewayParameters(){
        if(Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_CONFIG_ENV') == '3'){
            $APIKey = Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_PROD_API_KEY');
            $gatewayURL = 'https://api.vodapaygateway.vodacom.co.za/v2/Pay/Refund';
        }else{
            $APIKey = Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_UAT_API_KEY');
            $gatewayURL = 'https://api.vodapaygatewayuat.vodacom.co.za/v2/Pay/Refund';
            $testHeader = (Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_CONFIG_ENV') == '2')?false:true;
        }
        return [$APIKey, $gatewayURL, $testHeader];
    }

    public function resetRefund($params){
        foreach ($params['productList'] as $orderDetailLists) {
            $productQtyRefunded = 0;

            $idOrderDetail = $orderDetailLists['id_order_detail'];

            $countOfOrderSlipDetail = Db::getInstance()->getRow('SELECT COUNT(id_order_slip) as '
                . 'count_of_order_slip_detail from `'
                . _DB_PREFIX_ . 'order_slip_detail` where id_order_detail = '
                . (int) $idOrderDetail);

            if ((int) $countOfOrderSlipDetail['count_of_order_slip_detail'] !== 1) {
                $idOrderSlipDetail = Db::getInstance()->getRow('SELECT max(id_order_slip) as '
                . ' id_order_slip from `'
                . _DB_PREFIX_ . 'order_slip_detail` where id_order_detail = '
                . (int) $idOrderDetail);
            } else {
                $idOrderSlipDetail['id_order_slip'] = 0;
            }

            Db::getInstance()->execute('DELETE from `'
                . _DB_PREFIX_ . 'order_slip_detail` where id_order_slip = '
                . (int) $idOrderSlipDetail['id_order_slip']);
            Db::getInstance()->execute('DELETE from `' 
                . _DB_PREFIX_ . 'order_slip` where id_order_slip = '
                . (int) $idOrderSlipDetail['id_order_slip']);

            $orderDetail = $this->getOrderDetail($idOrderDetail);

            $this->resetStock($orderDetail, $orderDetailLists);

            $productQtyRefunded = (int) $orderDetail['product_quantity_refunded'] -
            (int) $orderDetailLists['quantity'];

            $messageLog = 'VodaPay Gateway - product qty refunded (' . $idOrderDetail . ') : ' . $productQtyRefunded;
            PrestaShopLogger::addLog($messageLog, 3, null, 'Order', $params['id_order'], true);

            Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'order_detail` '
                . ' set product_quantity_refunded = '
                . (int) $productQtyRefunded . ' where id_order_detail = '
                . (int) $idOrderDetail);
        }
        $messageLog = 'VodaPay Gateway - order has not been successfully partial refunded';
        PrestaShopLogger::addLog($messageLog, 3, null, 'Order', $params['id_order'], true);

        
    }

    public function resetStock($orderDetail, $orderDetailLists){
        $productStock = Db::getInstance()->getRow('SELECT quantity FROM `'
        . _DB_PREFIX_ . 'stock_available` where `id_product` = '
        . (int) $orderDetail['product_id']. ' and `id_product_attribute` = '
        . (int)$orderDetail['product_attribute_id'] );

        $productQtyRefunded = (int) $productStock['quantity'] -
            (int) $orderDetailLists['quantity'];
        
        Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'stock_available` '
                . ' set quantity = '
                . (int) $productQtyRefunded . ' where `id_product` = '
                . (int) $orderDetail['product_id']. ' and `id_product_attribute` = '
                . (int)$orderDetail['product_attribute_id']);
    }

    public function getOrderDetail($idOrderDetail){
        return Db::getInstance()->getRow('SELECT * from `'
        . _DB_PREFIX_ . 'order_detail` where id_order_detail = '
        . (int) $idOrderDetail);
    }

    public function getRefundShipping( $idOrderDetail){
        $slipId = Db::getInstance()->getRow('SELECT id_order_slip from `'
        . _DB_PREFIX_ . 'order_slip_detail` where id_order_detail = '
        . (int) $idOrderDetail);

        return $refundShipping = Db::getInstance()->getRow('SELECT total_shipping_tax_incl from `'
        . _DB_PREFIX_ . 'order_slip` where id_order_slip = '
        . (int) $slipId['id_order_slip'])['total_shipping_tax_incl'];
    }
}
