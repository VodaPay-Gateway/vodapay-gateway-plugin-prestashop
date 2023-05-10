<?php
require_once _PS_MODULE_DIR_ . 'vodapaygatewaypaymentmodule/classes/ReponseCodeConstants.php';
class VodapaygatewaypaymentmodulehandlepaymentModuleFrontController extends ModuleFrontController
{
    public function postProcess(){
       
        $data = $_GET;
        $params = $_GET['params'];
        $APIKey = $_GET['APIKey'];
        $testHeader = (int) $_GET['test'];
        $gatewayURL = $_GET['gatewayURL'];

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

            // $context  = stream_context_create($this->prepareOptions($APIKey,$testHeader,$params));
            $curl = curl_init();

            $options = $this->prepareOptions($params, [$APIKey, $gatewayURL, $testHeader]);

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
                 $error = error_get_last();
                 throw new Exception(implode($error));
            }
            else{
                
            $response = json_decode($result);
            $responseCode = $response->data->responseCode;
            $responseMessage = $response->data->responseMessage;
            
            
            if (in_array($responseCode, ResponseCodeConstants::getGoodResponseCodeList())) {
                //SUCCESS
                if ($responseCode == "00") {
                    $initiationUrl = $response->data->initiationUrl;
                    header("Location: $initiationUrl");
                }
            } elseif (in_array($responseCode, ResponseCodeConstants::getBadResponseCodeList())) {
                //FAILURE
                $data = base64_encode(json_encode(['responseCode'=>$responseCode,'responseMessage'=>$responseMessage]));
                $params = ['data' => $data];
                return Tools::redirect($this->context->link->getModuleLink($_GET['name'],'validation',$params, true));
                }
            }
        } catch (Exception $e) {
            $data = base64_encode(json_encode(['responseCode'=>500,'responseMessage'=>$e]));
            $params = ['data' => $data];
           return Tools::redirect($this->context->link->getModuleLink($_GET['name'],'validation',$params, true));
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
                'amountExVAT'=> number_format((float)$item['price_with_reduction_without_tax']*100., 0, '.', ''),
                'amountVAT'=> number_format((float)$item['total_wt']*100., 0, '.', ''),
            ];
            array_push($basketitems,$product);
        }
        return $basketitems;
    }

    public function prepareOptions($params, $gatewayParameters){
        $args =json_encode($this->prepareArgs($params));
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
                "test: ".($params[2] == 0)?false:true];
    }

    public function prepareArgs($params){
        $args=[
        'delaySettlement' => false,
        'echoData'=> 'Prestashop payment',
        'traceId'=> $this->getTraceId($params['cart']['id']),
        'amount'=>  number_format((float)$this->context->cart->getOrderTotal(true)*100., 0, '.', ''),
        'basket'=> $this->getBasketItems(),
        'notifications' => $this->getNotifications(),
        'styling'=>['logoUrl'=>Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_MERCHANT_LOGO_URL'),'bannerUrl'=>Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_MERCHANT_MESSAGE_URL')],
        ];
        return $args;
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