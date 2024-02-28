<?php

namespace App\Clients\Breathepay;

use Illuminate\Support\Facades\Log;
use App\Models\BreathepayCharge;
use App\Models\ThreeDSCheck;

use Carbon\Carbon;

use App\Clients\Breathepay\Classes\RequestBuilder;

class Breathepay
{
    protected $merchantId;
    protected $merchantSecret;

    protected $payment;

    public function __construct($payment, $merchantId, $merchantSecret)
    {
        $this->requestBuilder = new RequestBuilder($payment, $merchantId, $merchantSecret);

        $this->merchantId = $merchantId;
        $this->merchantSecret = $merchantSecret;

        $this->payment = $payment;
    }

    public function charge($paymentToken, $browserInfo)
    {
        try {
            $response = $this->requestBuilder->charge($paymentToken, $browserInfo);
            $result = $this->parseResponse($response);
            return $result;
        } catch (\Exception $err) {
            Log::error('Error in BreathepayClient@sendPaymentRequest on line ' . $err->getLine() . ' - ' . $err->getMessage());
            return $this->createError('Server Error', 'BreathepayClient throwing error in sendPaymentRequest', true);
        }
    }

    public function refund()
    {
        try {
            if(Carbon::parse($this->payment->created_at)->isToday()) {
              $response = $this->requestBuilder->cancel();
            } else {
              $response = $this->requestBuilder->refund();
            }

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
    public function threeDs($threeDSResponse) {
      try {
          $check = ThreeDSCheck::where('payment_id', $this->payment->id)->orderByDesc('round')->first();

          if($check && $threeDSResponse) {
            $response = $this->requestBuilder->threeDs($check->threeDSRef, json_decode($threeDSResponse));
            $result = $this->parseResponse($response);
            return $result;
          }

          return $this->createError('No Reference Found', 'No Reference Found in 3DS Check', true);
      } catch (\Exception $err) {
          Log::error('Error in BreathepayClient@sendPaymentRequest on line ' . $err->getLine() . ' - ' . $err->getMessage());
          return $this->createError('Server Error', 'BreathepayClient throwing error in sendThreeDSRequest', true);
      }
    }

    protected function parseResponse($response) {
      $signature = null;
      if(isset($response['signature'])) {
        $signature = $response['signature'];
        unset($response['signature']);

        if($signature !== BreathepayCharge::createSignature($response, $this->merchantSecret)) {
          return [
            'success' => false,
            'error' => [
              'friendly' => 'Invalid signature',
              'notify' => false,
              'code' => 999,
              'callback' => config('app.url') . '/pay?payment=' . $this->payment->id
            ]
          ];
        }
      }

      if($response['responseCode'] == 65802) {
        return [
          'txId' => $response['transactionID'],
          'xref' => $response['xref'],
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
          'code' => $response['responseCode'],
          'callback' => config('app.url') . '/pay?payment=' . $this->payment->id
        ];
      }

      return [
        'txId' => $response['transactionID'],
        'xref' => $response['xref'],
        'success' => true,
        '3ds' => false,
        'callback' => '/success'
      ];
    }

    protected function parseRefundResponse($response) {
      Log::info(json_encode($response));

      $signature = null;
      if(isset($response['signature'])) {
        $signature = $response['signature'];
        unset($response['signature']);

        if($signature !== BreathepayCharge::createSignature($response, $this->merchantSecret)) {
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

      $this->payment->successful = 0;
      $this->payment->status = 'refunded';
      $this->payment->save();

      return [
        'success' => true
      ];
    }

    protected function createError($friendly, $reason, $notify) {
      return [
        'success' => false,
        'error' => [
          'friendly' => $friendly,
          'reason' => $reason,
          'notify' => $notify,
          'code' => 999,
          'callback' => config('app.url') . '/pay?payment=' . $this->payment->id
        ]
      ];
    }
}
