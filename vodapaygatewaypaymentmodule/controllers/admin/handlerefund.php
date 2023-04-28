<?php
require 'ReponseCodeConstants.php';
class VodapaygatewaypaymentmodulehandlerefundModuleFrontController extends  ModuleAdminController
{
    public function postProcess(){
        $data = $_GET;
        $params = $_GET['params'];
        $gatewayParameters = getGatewayParameters();
        $APIKey = $gatewayParameters[0];
        $gatewayURL = $gatewayParameters[1];

        try {

            $context  = stream_context_create($this->prepareOptions($APIKey,$params));
            
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
                    /*
                    * update order status
                    */
                    print_r('Succesful refund');
                }
            } elseif (in_array($responseCode, ResponseCodeConstants::getBadResponseCodeList())) {
                //FAILURE
                 /*
                 * report failure, reset order status
                 */
                print_r('Unsuccessful refund');
                }
            }
        } catch (Exception $e) {
            /*
            *alert error, reset order status
            */
            print_r('Error processing refund');
        }
    }

    public function prepareOptions($APIKey,$params){
        $args =json_encode($this->prepareArgs($params));
        $options =[
            'http' => [
                'method'  => "POST",
                'content' => $args,
                'header' =>  "Content-Type: application/json\r\n".
                    "Content-Length: " . strlen($args) . "\r\n".
                    "Accept: application/json\r\n".
                    "api-key: ".$APIKey
                ]
            ];
        return $options;
    }

    public function prepareArgs($params){
        $args=[
        'EchoData'=> 'Prestashop payment',
        'TraceId'=> $this->getTraceId($params['cart']['id']),
        'originalTransactionId' => $transactionID,
        'Amount'=>  number_format((float)$this->context->cart->getOrderTotal(true)*100., 0, '.', ''),
        'Notifications' => $this->getNotifications(),
        ];
        return $args;
    }

    public function getNotifications(){
        if (empty( Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_NOTIFICATION_URL'))) 
        {
          $notifications = [
                            'CallbackUrl' => Tools::redirect($this->context->link->getModuleLink($_GET['name'],'handlerefund',$data, true)),
          ];
        } else 
        {
          $notifications = [
                            'CallbackUrl' => Tools::redirect($this->context->link->getModuleLink($_GET['name'],'handlerefund',$data, true)),
                            'NotificationUrl' => Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_NOTIFICATION_URL'),
          ];
        }
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

    public function getGatewayParameters(){
        if(Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_CONFIG_ENV') == '3'){
            $APIKey = Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_PROD_API_KEY');
            $gatewayURL = 'https://api.vodapaygateway.vodacom.co.za/V2/Pay/Refund';
        }else{
            $APIKey = Configuration::get('VODAPAYGATEWAYPAYMENTMODULE_UAT_API_KEY');
            $gatewayURL = 'https://api.vodapaygatewayuat.vodacom.co.za/V2/Pay/Refund';
        }
        return [$APIKey,$gatewayURL];
    }
}