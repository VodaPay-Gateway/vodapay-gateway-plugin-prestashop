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
class VodapaygatewaypaymentmoduleValidationModuleFrontController extends ModuleFrontController
{
    /**
     * This class should be use by your Instant Payment
     * Notification system to validate the order remotely
     */
    public function postProcess()
    {
        /*
         * If the module is not active anymore, no need to process anything.
         */
        if ($this->module->active == false) {
            die;
        }  

        $data = json_decode(base64_decode($_GET['data']));
        $cart = $this->context->cart;
        $orderTotal = $cart->getOrderTotal(true,Cart::BOTH);
        $cart_id = is_null($this->context->cart->id)? 1:$this->context->cart->id;
        $customer_id = $this->context->customer->id;
        
        Db::getInstance()->update(
            'first_data_ipg',
            array(
                'response' => pSQL(var_export($data->responseMessage, true)),
                'updated' => date('Y-m-d H:i:s'),
            ),
            "id_cart = $cart_id"
        );
    
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        /*
         * Restore the context from the $cart_id & the $customer_id to process the validation properly.
         */
        Context::getContext()->cart = new Cart((int) $cart_id);
        Context::getContext()->customer = new Customer((int) $customer_id);
        Context::getContext()->currency = new Currency((int) Context::getContext()->cart->id_currency);
        Context::getContext()->language = new Language((int) Context::getContext()->customer->id_lang);

        $secure_key = Context::getContext()->customer->secure_key;
        
        if ($this->isValidOrder() === true) {
            $payment_status = Configuration::get('PS_OS_PAYMENT');
            $message = null;
        } else {
            $payment_status = Configuration::get('PS_OS_ERROR');
            $message = $this->module->l($data->responseMessage);
        }
        
        $module_name = $this->module->displayName;
        $currency_id = (int) Context::getContext()->currency->id;
        $referenceID = $data->transactionId;

        $this->module->validateOrder($cart_id, $payment_status, $orderTotal, $module_name, $message, array('transaction_id'=>$referenceID), $currency_id, false, $secure_key);

        $order_id = Order::getOrderByCartId((int)$cart_id);
        
        if ($order_id && ($secure_key == $this->context->customer->secure_key)) {
            $module_id = $this->module->id;
            $data = json_decode(base64_decode($_GET['data']));
            $referenceID = $data->transactionId;
            $responseMsg = $data->responseMessage;
            Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart_id.'&id_module='.$module_id.'&id_order='.$order_id.'&key='.$secure_key.'&referenceId='.$referenceID.'&responseMsg='.$responseMsg);
        } else {
            $errors = ["An error occured. Please contact the merchant to have more informations:", $responseMsg];
            $this->context->smarty->assign(['errors' => $errors]);
            return $this->context->smarty->fetch('module:vodapaygatewaypaymentmodule/views/templates/front/error.tpl');
        }
    }

    public function returnSuccess($result)
    {
    echo json_encode(array('return_link' => $result));
    exit;
    }

    protected function isValidOrder()
    {
        $data = json_decode(base64_decode($_GET['data']));
        $isValid = false;
        
        if($data->responseCode == 'ER'){
            return false;
        }

        if(intval($data->responseCode) >= 500){
            return false;
        }

        if (intval($data->responseCode) == 00){
            $isValid = true;
            return true;
        }
        return $isValid;
    }
}
