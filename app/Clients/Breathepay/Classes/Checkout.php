<?php

namespace App\Clients\Breathepay\Classes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\BreathepayCharge;
use App\Models\ThreeDSCheck;

use Carbon\Carbon;

use App\Clients\Breathepay\Breathepay;

class Checkout
{
    protected $request;
    protected $payment;
    protected $client;

    public function __construct(Request $request, BreathepayCharge $payment)
    {
        $this->client = new Breathepay($payment, config('breathepay.gateway_3ds'), config('breathepay.gateway_secret'));

        $this->request = $request;
        $this->payment = $payment;
    }

    public function checkout() {
      try {
        if($this->payment->completed_at) {
          return [
            'status' => 'succeeded'
          ];
        } else if($this->payment->status == 'failed') {
          return [
            'status' => 'error'
          ];
        }

        //Initial request where we want to make a payment
        if(!$this->request->has('acs') && !$this->request->has('threeDSResponse')) {
          $this->request->validate([
              'paymentToken' => ['required', 'string']
          ]);

          $response = $this->client->charge($this->request->paymentToken, json_decode($this->request->browserInfo));
          return $this->handleCheckoutResponse($this->payment, $response);
        } else if($this->request->has('acs')) {
          return [
            'status' => 'acs',
            'url' => config('app.url') . '/pay?payment=' . $this->payment->id,
            'post' => $this->request->except(['acs'])
          ];

        //The callback from the above step which sends from the iframe
        //Send the threeDSResponse to the gateway and see if we need to rerun the cycle or if payment has been accepted
        //Return to the checkout view which will redirect accordingly or render a new iframe
      } else if($this->request->has('threeDSResponse')) {
          $response = $this->client->threeDs($this->request->threeDSResponse);
          return $this->handleCheckoutResponse($this->payment, $response);
        }
      } catch(\Exception $err) {
        Log::error($err);
        return [
          'status' => 'error',
          'reason' => 'Oops, somethings gone wrong',
          'callback' => config('app.url') . '/pay?payment=' . $this->payment->id
        ];
      }
    }

    protected function handleCheckoutResponse($payment, $response) {
        try {
          if(isset($response['txId']) && $response['txId']) {
            $payment->transaction_id = $response['txId'];
            $payment->xref = $response['xref'];
            $payment->save();
          }

          if($response['success']) {
            if($response['3ds']) {
              $rounds = ThreeDSCheck::where('payment_id', $payment->id)->get()->count();

              $url = $response['response']['threeDSURL'];
              $baseUrl = parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST);
              $baseUrl = trim($url, '/');
              $ref = $response['response']['threeDSRef'];
              $show = !isset($response['response']['threeDSRequest']['threeDSMethodData']);
              $data = json_encode($response['response']['threeDSRequest']);

              $check = ThreeDSCheck::create([
                'payment_id' => $payment->id,
                'round' => ($rounds + 1),
                'show_iframe' => $show,
                'threeDSURL' => $url,
                'threeDSRef' => $ref,
                'threeDSData' => $data ?? '--'
              ]);

              return [
                'status' => '3ds',
                'display' => $show,
                'threeDSUrl' => $url,
                'baseUrl' => $baseUrl,
                'threeDSMethodData' => $data
              ];
            } else {
              $payment->handleSuccessfulTransaction();
              return [
                'status' => 'succeeded'
              ];
            }
          } else {
            Log::info($response);
            BreathepayCharge::handleFailedInstantTransaction($response['code'], $response['error']['reason'], $payment);
            return [
              'status' => 'failed',
              'reason' => $response['error']['friendly']
            ];
          }
      } catch(\Exception $err) {
        Log::error($err);
        return [
          'status' => 'failed',
          'reason' => 'Oops, somethings gone wrong'
        ];
      }
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
