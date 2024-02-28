<?php

namespace App\Clients\Breathepay\Classes;

use Illuminate\Support\Facades\Log;
use App\Models\BreathepayCharge;
use App\Models\ThreeDSCheck;

use App\Clients\Breathepay\Classes\HttpClient;

class RequestBuilder
{
    protected $merchantId;
    protected $merchantSecret;
    protected $payment;

    protected $browserInfo;

    public function __construct(BreathepayCharge $payment, $merchantId, $merchantSecret) {
      $this->merchantId = $merchantId;
      $this->merchantSecret = $merchantSecret;

      $this->payment = $payment;

      $this->client = new HttpClient();
    }

    public function charge($paymentToken, $browserInfo) {
      $this->browserInfo = $browserInfo;

      $data = [
         'merchantID' => $this->merchantId,
         'action' => 'SALE',
         'type' => 1,
         'countryCode' => 826,
         'currencyCode' => 826,
         'amount' => $this->payment->amount,
         'orderRef' => $this->payment->id,
         'paymentToken' => $paymentToken,
         'duplicateDelay' => 30,
         'remoteAddress' => $_SERVER['REMOTE_ADDR'],
         'threeDSRedirectURL' => config('app.url') . '/pay?payment=' . $this->payment->id . '&acs=1'
      ];

      $data = array_merge((array) $data, $this->getBrowserInfo());
      $data['signature'] = BreathepayCharge::createSignature($data, $this->merchantSecret);

      return $this->client->sendRequest($data);
    }

    public function threeDs($reference, $threeDSResponse) {
      $data = [
        'threeDSRef' => $reference,
        'threeDSResponse' => $threeDSResponse
      ];

      return $this->client->sendRequest($data);
    }

    public function refund() {
      $data = [
         'merchantID' => $this->merchantId,
         'action' => 'REFUND_SALE',
         'amount' => $this->payment->amount,
         'xref' => $this->payment->xref
      ];

      $data['signature'] = BreathepayCharge::createSignature($data, $this->merchantSecret);

      return $this->client->sendRequest($data);
    }

    public function cancel() {
      $data = [
         'merchantID' => $this->merchantId,
         'action' => 'CANCEL',
         'xref' => $this->payment->xref
      ];

      $data['signature'] = BreathepayCharge::createSignature($data, $this->merchantSecret);

      return $this->client->sendRequest($data);
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
}
