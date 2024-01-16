<?php

namespace App\Clients;

use Illuminate\Support\Facades\Log;
use App\Models\BreathepayCharge;
use App\Models\ThreeDSCheck;

class BreathepayClient
{
    protected $gatewayId;
    protected $gatewaySecret;
    protected $charge;
    protected $browserInfo;

    public function __construct()
    {
        $this->gateway = 'https://gateway.breathepay.co.uk/direct/';
    }

    public function sendPaymentRequest($gatewayId, $gatewaySecret, $charge, $browserInfo)
    {
        try {
            $this->gatewayId = $gatewayId;
            $this->gatewaySecret = $gatewaySecret;
            $this->charge = $charge;
            $this->browserInfo = $browserInfo;

            $data = $this->getData();
            $response = $this->sendRequest($data);
            $result = $this->parseResponse($response);
            return $result;
        } catch (\Exception $err) {
            Log::error('Error in BreathepayClient@sendPaymentRequest on line ' . $err->getLine() . ' - ' . $err->getMessage());
            return $this->createError('Server Error', 'BreathepayClient throwing error in sendPaymentRequest', true);
        }
    }

    public function sendRefundRequest($gatewayId, $gatewaySecret, $charge)
    {
        try {
            $this->gatewayId = $gatewayId;
            $this->gatewaySecret = $gatewaySecret;
            $this->charge = $charge;
            $data = $this->getRefundData();
            $response = $this->sendRequest($data);
            $result = $this->parseRefundResponse($response);
            return $result;
        } catch (\Exception $err) {
            Log::error('Error in BreathepayClient@sendRefundRequest on line ' . $err->getLine() . ' - ' . $err->getMessage());
            return $this->createError('Server Error', 'BreathepayClient throwing error in sendRefundRequest', true);
        }
    }

    //Continuation request
    //Get the latest 3DSReference for the order
    //Send the updated 3DSCheck continuation check to the Gateway
    public function sendThreeDSRequest($gatewayId, $gatewaySecret, $request) {
      try {
          $this->gatewayId = $gatewayId;
          $this->gatewaySecret = $gatewaySecret;
          
          $this->charge = BreathepayCharge::findOrFail($request->payment);
          $check = ThreeDSCheck::where('payment_id', $request->payment)->orderByDesc('round')->first();

          if($check && $request->has('threeDSResponse')) {
            $data = [
              'threeDSRef' => $check->threeDSRef,
              'threeDSResponse' => json_decode($request->threeDSResponse)
            ];

            $response = $this->sendRequest($data);
            $result = $this->parseResponse($response);
            return $result;
          }

          return $this->createError('No Reference Found', 'No Reference Found in 3DS Check', true);
      } catch (\Exception $err) {
          Log::error('Error in BreathepayClient@sendPaymentRequest on line ' . $err->getLine() . ' - ' . $err->getMessage());
          return $this->createError('Server Error', 'BreathepayClient throwing error in sendThreeDSRequest', true);
      }
    }

    protected function getData() {
      $data = [
         'merchantID' => $this->gatewayId,
         'action' => 'SALE', //charge customer
         'type' => 1, //ecommerce
         'countryCode' => 826, //gbp
         'currencyCode' => 826, //gbp
         'amount' => $this->charge->amount,
         'orderRef' => $this->charge->id,
         'paymentToken' => $this->charge->payment_token,
         'duplicateDelay' => 30,
         'remoteAddress' => $_SERVER['REMOTE_ADDR'],
         'threeDSRedirectURL' => config('app.url') . '/pay?payment=' . $this->charge->id . '&acs=1'
      ];

      $data = array_merge((array) $data, $this->getBrowserInfo());
      $data['signature'] = BreathepayCharge::createSignature($data, $this->gatewaySecret);

      return $data;
    }

    protected function getRefundData() {
      $data = [
         'merchantID' => $this->gatewayId,
         'action' => 'REFUND', //refund
         'type' => 1, //ecommerce
         'countryCode' => 826, //gbp
         'currencyCode' => 826, //gbp
         'amount' => $this->charge->amount,
         'orderRef' => $this->charge->id,
         'paymentToken' => $this->charge->payment_token,
         'xref' => $this->charge->transaction_id
      ];

      $data['signature'] = BreathepayCharge::createSignature($data, $this->gatewaySecret);

      return $data;
    }

    public function sendRequest($data){
      // Initiate and set curl options to post to the gateway
      $ch = curl_init($this->gateway);

      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      // Send the request and parse the response
      parse_str(curl_exec($ch), $response);

      // Close the connection to the gateway
      curl_close($ch);

      return $response;
    }

    protected function parseResponse($response) {
      $signature = null;
      if(isset($response['signature'])) {
        $signature = $response['signature'];
        unset($response['signature']);

        if($signature !== BreathepayCharge::createSignature($response, $this->gatewaySecret)) {
          return [
            'success' => false,
            'error' => [
              'friendly' => 'Invalid signature',
              'notify' => false,
              'code' => 999
            ]
          ];
        }
      }

      //Payment requires another round of 3DS
      if($response['responseCode'] == 65802) {
        return [
          'txId' => $response['transactionID'],
          '3ds' => true,
          'response' => $response,
          'success' => true
        ];
      }

      if($response['responseCode'] != 0 || is_null($signature)) {
        $error = BreathepayCharge::findError($response['responseCode']);
        return [
          'txId' => $response['transactionID'],
          'success' => false,
          'error' => $error,
          'code' => $response['responseCode']
        ];
      }

      return [
        'txId' => $response['transactionID'],
        'success' => true,
        '3ds' => false
      ];
    }

    protected function parseRefundResponse($response) {
      $signature = null;
      if(isset($response['signature'])) {
        $signature = $response['signature'];
        unset($response['signature']);

        if($signature !== BreathepayCharge::createSignature($response, $this->gatewaySecret)) {
          return [
            'success' => false,
            'error' => [
              'friendly' => 'Invalid signature',
              'notify' => false,
              'code' => 999
            ]
          ];
        }
      }

      if($response['responseCode'] != 0 || is_null($signature)) {
        $error = BreathepayCharge::findError($response['responseCode']);
        return [
          'success' => false,
          'error' => $error,
          'code' => $response['responseCode']
        ];
      }

      return [
        'success' => true
      ];
    }

    protected function getBrowserInfo() {
      return array_merge(array(
         'deviceChannel' => 'browser',
         'deviceIdentity' => (isset($_SERVER['HTTP_USER_AGENT']) ? htmlentities($_SERVER['HTTP_USER_AGENT']) : null),
         'deviceTimeZone' => '0',
         'deviceCapabilities' => '',
         'deviceScreenResolution' => '1x1x1',
         'deviceAcceptContent' => (isset($_SERVER['HTTP_ACCEPT']) ? htmlentities($_SERVER['HTTP_ACCEPT']) : null),
         'deviceAcceptEncoding' => (isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? htmlentities($_SERVER['HTTP_ACCEPT_ENCODING']) : null),
         'deviceAcceptLanguage' => (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? htmlentities($_SERVER['HTTP_ACCEPT_LANGUAGE']) : null),
         'deviceAcceptCharset' => (isset($_SERVER['HTTP_ACCEPT_CHARSET']) ? htmlentities($_SERVER['HTTP_ACCEPT_CHARSET']) : null)
      ), (array) $this->browserInfo);
    }

    protected function createError($friendly, $reason, $notify) {
      return [
        'success' => false,
        'error' => [
          'friendly' => $friendly,
          'reason' => $reason,
          'notify' => $notify,
          'code' => 999
        ]
      ];
    }
}
