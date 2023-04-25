<?php
require 'ReponseCodeConstants.php';
class VodapaygatewaypaymentmodulehandlepaymentModuleFrontController extends ModuleFrontController
{
    public function postProcess(){
       
        $data = $_GET;
        $params = $_GET['params'];
        $APIKey = $_GET['APIKey'];
        $testHeader= $_GET['test'];
        $gatewayURL= $_GET['gatewayURL'];

        Db::getInstance()->insert('first_data_ipg', array(
            'id_cart' => (int) $params['cart']['id'],
            'txndatetime' => date('Y:m:d-H:i:s'),
            'request' => pSQL(var_export($this->prepareArgs($params), true)),
            'user_agent' => pSQL($_SERVER['HTTP_USER_AGENT']),
            'remote_addr' => pSQL($_SERVER['REMOTE_ADDR']),
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ));

        $order_id = Order::getOrderByCartId((int) $params['cart']['id']);
        $currency = (int) $params['cart']['id_currency'];
        

        try {

            $context  = stream_context_create($this->prepareOptions($APIKey,$testHeader,$params));
            
            $worked = false;
            $errorMessage = "";

            
            for ( $i=0; $i<3 ; $i++) 
            {
                $result = file_get_contents($gatewayURL, false, $context);
               if( $result !== FALSE ) 
               {
                  $worked = TRUE;
                  break;
               }
            }
            
            if($worked == false)
            {
                 $error = error_get_last();
                 throw new Exception(implode($error));
            }
            else{
                
            $response = json_decode($result);
            $responseCode = $response->data->responseCode;
            $chargeTotal=0;
                
            if (in_array($responseCode, ResponseCodeConstants::getGoodResponseCodeList())) {
                //SUCCESS
                if ($responseCode == "00") {
                    $initiationUrl = $response->data->initiationUrl;
                    header("Location: $initiationUrl");
                }
            } elseif (in_array($responseCode, ResponseCodeConstants::getBadResponseCodeList())) {
                //FAILURE
                 $responseMessages = ResponseCodeConstants::getResponseText();
                $failureMsg = $responseMessages[$responseCode];
                $data = ['responseCode'=>$responseCode,'responseMsg'=>$failureMsg];
                return Tools::redirect($this->context->link->getModuleLink($_GET['name'],'validation',$data, true));
                }
            }
        } catch (Exception $e) {
            $data = ['responseCode'=>500,'responseMsg'=>$e];
           return Tools::redirect($this->context->link->getModuleLink($_GET['name'],'validation',$data, true));
        }
    }

    public function getBasketItems(){
        $basket = $this->context->cart->getProducts();
        $basketitems= [];
        foreach($basket as $item){
            $product = [
                'lineNumber'=> strval($item['id_product_attribute']),
                'id'=> strval($item['id_product']),
                'barcode'=> $item['unique_id'],
                'quantity'=> $item['cart_quantity'],
                'description'=> trim($item['description_short'],'<\/p>'),
                'amountExVAT'=> (int)$item['price_with_reduction_without_tax']*100,
                'amountVAT'=> (int)$item['total_wt']*100,
            ];
            array_push($basketitems,$product);
        }
        return $basketitems;
    }

    public function prepareArgs($params){
        $args=[
        'DelaySettlement' => false,
        'EchoData'=> 'Prestashop payment',
        'TraceId'=> $this->getTraceId($params['cart']['id']),
        'Amount'=> $this->context->cart->getOrderTotal(true)*100,
        'Basket'=> $this->getBasketItems(),
        'Notifications' => $this->getNotifications(),
        'Styling'=>['LogoUrl'=>Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_MERCHANT_LOGO_URL'),'BannerUrl'=>Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_MERCHANT_MESSAGE_URL')],
        ];
        return $args;
    }

    public function prepareOptions($APIKey,$testHeader,$params){
        $args =json_encode($this->prepareArgs($params));
        print($args);
        $options =[
            'http' => [
                'method'  => "POST",
                'content' => $args,
                'header' =>  "Content-Type: application/json\r\n".
                    "Content-Length: " . strlen($args) . "\r\n".
                    "Accept: application/json\r\n".
                    "api-key: ".$APIKey."\r\n" .
                    "test: ".$testHeader
                ]
            ];
        return $options;
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

    public function getNotifications(){
        if (empty( Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_NOTIFICATION_URL'))) 
        {
          $notifications = [
                            'CallbackUrl' => $this->context->link->getModuleLink($_GET['name'], 'validation', array(), true),
          ];
        } else 
        {
          $notifications = [
                            'CallbackUrl' => $this->context->link->getModuleLink($_GET['name'], 'validation', array(), true),
                            'NotificationUrl' => Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_NOTIFICATION_URL'),
          ];
        }
        return $notifications;
    }
}