<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

use App\Clients\BreathepayClient;
use App\Models\BreathepayCharge;
use App\Models\ThreeDSCheck;

class GatewayController extends Controller
{
  // ----------------------- PAYMENT PAGE CHECKOUT PROCESS ----------------------------------
  public function checkoutPage(Request $request) {
    $payment = BreathepayCharge::findOrFail($request->get('payment'));
    $error = $request->error;
    return view('checkout', compact('payment', 'error'));
  }

  public function checkout(Request $request) {
    try {
      $payment = BreathepayCharge::findOrFail($request->get('payment'));

      //Initial request where we want to make a payment
      //This will not include any 3DS information, only the basic payment information
      if(!$request->has('acs') && !$request->has('threeDSResponse')) {
        $request->validate([
            'paymentToken' => ['required', 'string']
        ]);

        $payment->payment_token = $request->paymentToken;
        $payment->save();

        $client = new BreathepayClient();
        $response = $client->sendPaymentRequest(
          config('breathepay.gateway_3ds'),
          config('breathepay.gateway_secret'),
          $payment,
          json_decode($request->browserInfo)
        );

        $result = $this->handleCheckoutResponse($payment, $response);

        return view('checkout', compact('payment', 'result'));
      } else if($request->has('acs')) {
        $result = [
          'status' => 'acs',
          'url' => config('app.url') . '/pay?payment=' . $payment->id,
          'post' => $request->except(['acs'])
        ];

        return view('checkout', compact('payment', 'result'));

      //The callback from the above step which sends from the iframe
      //Send the threeDSResponse to the gateway and see if we need to rerun the cycle or if payment has been accepted
      //Return to the checkout view which will redirect accordingly or render a new iframe
      } else if($request->has('threeDSResponse')) {
        $client = new BreathepayClient();
        $response = $client->sendThreeDSRequest(
          config('breathepay.gateway_3ds'),
          config('breathepay.gateway_secret'),
          $request
        );
        $result = $this->handleCheckoutResponse($payment, $response);

        return view('checkout', compact('payment', 'result'));
      }
    } catch(\Exception $err) {
      Log::error($err);
      $result = [
        'status' => 'error',
        'reason' => 'Oops, somethings gone wrong',
        'callback' => config('app.url') . '/pay?payment=' . $payment->id
      ];

      return view('checkout', compact('payment', 'result'));
    }
  }

  protected function handleCheckoutResponse($payment, $response) {
      try {
        if(isset($response['txId']) && $response['txId']) {
          $payment->transaction_id = $response['txId'];
          $payment->save();
        }

        if($response['success']) {
          if($response['3ds']) {
            $rounds = ThreeDSCheck::where('payment_id', $payment->id)->get()->count();

            //Get all info needed for 3DS check
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
              'status' => 'succeeded',
              'message' => 'Payment Successful, Redirecting...',
              'callback' => '/'
            ];
          }
        } else {
          Log::error(json_encode($response));
          BreathepayCharge::handleFailedInstantTransaction($response['code'], $response['error']['reason'], $payment);
          return [
            'status' => 'failed',
            'reason' => $response['error']['friendly'],
            'callback' => '/'
          ];
        }
    } catch(\Exception $err) {
      Log::error($err);

      return [
        'status' => 'failed',
        'reason' => 'Oops, somethings gone wrong',
        'callback' => '/'
      ];
    }
  }

  public function logFromClient(Request $request) {
    Log::info('---------------------- LOG FROM CLIENT ---------------------');
    Log::info('Payment: ' . $request->payment);
    Log::info($request->title);
    Log::info($request->data);
  }
}
