<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

use App\Clients\BreathepayClient;
use App\Models\BreathepayCharge;
use App\Models\ThreeDSCheck;

use App\Clients\Breathepay\Classes\Checkout as BreathepayCheckout;
use App\Clients\Breathepay\Breathepay;

class GatewayController extends Controller
{
  // ----------------------- PAYMENT PAGE CHECKOUT PROCESS ----------------------------------
  public function checkoutPage(Request $request) {
    $payment = BreathepayCharge::findOrFail($request->payment);
    $error = $request->error;

    return view('checkout', compact('payment', 'error'));
  }

  public function checkout(Request $request) {
    $payment = BreathepayCharge::findOrFail($request->payment);

    $breathepay = new BreathepayCheckout($request, $payment);
    $result = $breathepay->checkout();

    return view('checkout', compact('payment', 'result'));
  }

  public function refund(Request $request) {
    $payment = BreathepayCharge::findOrFail($request->payment);

    $breathepay = new Breathepay($payment, config('breathepay.gateway_3ds'), config('breathepay.gateway_secret'));
    $breathepay->refund();

    return view('refunded', compact('payment'));
  }

  public function logFromClient(Request $request) {
    Log::info('---------------------- LOG FROM CLIENT ---------------------');
    Log::info('Payment: ' . $request->payment);
    Log::info($request->title);
    Log::info($request->data);
  }
}
