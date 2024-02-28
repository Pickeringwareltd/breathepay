<?php

namespace App\Clients\Breathepay\Classes;

use Illuminate\Support\Facades\Log;

class HttpClient
{
    protected $gateway;

    public function __construct() {
      $this->gateway = 'https://gateway.breathepay.co.uk/direct/';
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
}
