<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

use App\Helper\Format;

class BreathepayCharge extends Model
{
    protected $table = 'breathepay_charges';

    protected $fillable = [
        'transaction_id',
        'xref',
        'amount',
        'country_code',
        'currency_code',
        'status',
        'successful',
        'captured',
        'reason_for_failure',
        'payment_token',
        'completed_at'
    ];

    /*
     * RELATIONS
     */
    public static function createSignature(array $data, $key) {
      // Sort by field name
      ksort($data);

      // Create the URL encoded signature string
      $ret = http_build_query($data, '', '&');

      // Normalise all line endings (CRNL|NLCR|NL|CR) to just NL (%0A)
      $ret = str_replace(array('%0D%0A', '%0A%0D', '%0D'), '%0A', $ret);

      // Hash the signature string and the key together
      return hash('SHA512', $ret . $key);
    }

    public function getTotalValue() {
      return Format::currencyAsNumber($this->amount);
    }

    public function handleSuccessfulTransaction() {
      //Log it into elastic
      $this->successful = 1;
      $this->captured = 1;
      $this->status = 'succeeded';
      $this->completed_at = now();
      $this->save();
    }

    // We dont send any analytics as the user can retry payment on the payment screen directly if failed
    protected static function handleFailedInstantTransaction($code, $message) {
      $response = '';
      if($code === '30') {
        $response = "Sorry, we're looking into this";
      } else {
        $error = self::findError($code);
        $response = $error['friendly'];
      }

      return $response;
    }

    protected static function findError($code) {
      //Find the reason and notify if necessary
      foreach(self::RESPONSES as $responseCode => $response) {
        if($code == $responseCode) {
          return $response;
        }
      }
    }

    const RESPONSES = [
      'ERR1' => [
        'reason' => 'Invalid signature given by breathepay',
        'notify' => true,
        'friendly' => 'Please try again'
      ],
      'ERR2' => [
        'reason' => 'Snapps server error',
        'notify' => true,
        'friendly' => "We're looking into this now, please try again shortly"
      ],
      '1' => [
        'reason' => 'Card referred – Refer to card issuer.',
        'notify' => false,
        'friendly' => 'Please double check your details or try another card'
      ],
      '2' => [
        'reason' => 'Card referred – Refer to card issuer, special condition.',
        'notify' => false,
        'friendly' => 'Please double check your details or try another card'
      ],
      '4' => [
        'reason' => 'Card declined – Keep card.',
        'notify' => false,
        'friendly' => 'Card declined. Please try another card'
      ],
      '5' => [
        'reason' => 'Card declined.',
        'notify' => false,
        'friendly' => 'Card declined. Please try another card'
      ],
      '65536' => [
        'reason' => 'Transaction in progress. Contact customer support if this error occurs',
        'notify' => true,
        'friendly' => 'Please try again or contact the venue'
      ],
      '65539' => [
        'reason' => 'Invalid Credentials: merchantID is unknown',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65540' => [
        'reason' => 'Permission denied: caused by sending a request from an unauthorised IP address',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65541' => [
        'reason' => 'Action not allowed: the transaction state or Acquirer doesn’t support this action',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65542' => [
        'reason' => 'Request Mismatch: fields sent while completing a request do not match initially requested values',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65543' => [
        'reason' => 'Request Ambiguous: request could be misinterpreted due to inclusion of mutually exclusive fields',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65544' => [
        'reason' => 'Request Malformed: couldn’t parse the request data',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65545' => [
        'reason' => 'Suspended Merchant account',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65546' => [
        'reason' => 'Currency not supported by Merchant',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65547' => [
        'reason' => 'Request Ambiguous, both taxValue and discountValue provided when should be one only',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65548' => [
        'reason' => 'Database error',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65549' => [
        'reason' => 'Payment processor communications error',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65550' => [
        'reason' => 'Payment processor error',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65551' => [
        'reason' => 'Internal Gateway communications error',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65552' => [
        'reason' => 'Internal Gateway error',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65553' => [
        'reason' => 'Encryption error.',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65554' => [
        'reason' => 'Duplicate request. Refer to Section 14.',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65555' => [
        'reason' => 'Settlement error.',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65556' => [
        'reason' => 'AVS/CV2 Checks are not supported for this card (or Acquirer)',
        'notify' => false,
        'friendly' => "Please try a different card"
      ],
      '65557' => [
        'reason' => 'IP Blocked: Request is from a banned IP address',
        'notify' => false,
        'friendly' => "Please try on another device"
      ],
      '65558' => [
        'reason' => 'Primary IP blocked: Request is not from one of the primary IP addresses configured for this Merchant Account',
        'notify' => true,
        'friendly' => "Please try on another device"
      ],
      '65559' => [
        'reason' => 'Secondary IP blocked: Request is not from one of the secondary IP addresses configured for this Merchant Account',
        'notify' => true,
        'friendly' => "Please try on another device"
      ],
      '65561' => [
        'reason' => 'Unsupported Card Type: Request is for a card type that is not supported on this Merchant Account',
        'notify' => false,
        'friendly' => "Please try a different card"
      ],
      '65562' => [
        'reason' => 'Unsupported Authorisation: External authorisation code authCode has been supplied and this is not supported for the transaction or by the Acquirer',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65563' => [
        'reason' => 'Request not supported: The Gateway or Acquirer does not support the request',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65564' => [
        'reason' => 'Request expired: The request cannot be completed as the information is too old',
        'notify' => false,
        'friendly' => "Expired - please try again"
      ],
      '65565' => [
        'reason' => 'Request retry: The request can be retried later',
        'notify' => false,
        'friendly' => "Please try again"
      ],
      '65566' => [
        'reason' => 'Test Card Used: A test card was used on a live Merchant Account',
        'notify' => true,
        'friendly' => "Please try a different card"
      ],
      '65567' => [
        'reason' => 'Unsupported card issuing country: Request is for a card issuing country that is not supported on this Merchant Account',
        'notify' => false,
        'friendly' => "Please try a different card"
      ],
      '65568' => [
        'reason' => 'Unsupported payment type: Request uses a payment type which is not supported on this Merchant Account',
        'notify' => false,
        'friendly' => "Please try a different card"
      ],
      '65792' => [
        'reason' => '3-D Secure transaction in progress. Contact customer support if this error occurs',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65793' => [
        'reason' => 'Unknown 3-D Secure Error',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65794' => [
        'reason' => '3-D Secure processing is unavailable. Merchant account doesn’t support 3-D Secure',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65795' => [
        'reason' => '3-D Secure processing is not required for the given card',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65796' => [
        'reason' => '3-D Secure processing is required for the given card',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65797' => [
        'reason' => 'Error occurred during 3-D Secure enrolment check',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '65800' => [
        'reason' => 'Error occurred during 3-D Secure authentication check',
        'notify' => false,
        'friendly' => "Please try a different card"
      ],
      '65802' => [
        'reason' => '3-D Secure authentication is required for this card',
        'notify' => false,
        'friendly' => "Please try a different card"
      ],
      '65803' => [
        'reason' => '3-D Secure enrolment or authentication failure and Merchant 3-D Secure preferences are to STOP processing',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66048' => [
        'reason' => 'Missing request. No data posted to integration URL',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66049' => [
        'reason' => 'Missing merchantID field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66051' => [
        'reason' => 'Reserved for internal use. Contact customer support if this error occurs',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66052' => [
        'reason' => 'Reserved for internal use. Contact customer support if this error occurs',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66053' => [
        'reason' => 'Reserved for internal use. Contact customer support if this error occurs',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66054' => [
        'reason' => 'Reserved for internal use. Contact customer support if this error occurs',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66055' => [
        'reason' => 'Missing action field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66056' => [
        'reason' => 'Missing amount field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66057' => [
        'reason' => 'Missing currencyCode field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66058' => [
        'reason' => 'Missing cardNumber field',
        'notify' => true,
        'friendly' => "Please fill out all of the details on the payment page"
      ],
      '66059' => [
        'reason' => 'Missing cardExpiryMonth field',
        'notify' => true,
        'friendly' => "Please fill out all of the details on the payment page"
      ],
      '66060' => [
        'reason' => 'Missing cardExpiryYear field',
        'notify' => true,
        'friendly' => "Please fill out all of the details on the payment page"
      ],
      '66061' => [
        'reason' => 'Missing cardStartMonth field (reserved for future use)',
        'notify' => true,
        'friendly' => "Please fill out all of the details on the payment page"
      ],
      '66062' => [
        'reason' => 'Missing cardStartYear field (reserved for future use)',
        'notify' => true,
        'friendly' => "Please fill out all of the details on the payment page"
      ],
      '66063' => [
        'reason' => 'Missing cardIssueNumber field (reserved for future use)',
        'notify' => true,
        'friendly' => "Please fill out all of the details on the payment page"
      ],
      '66064' => [
        'reason' => 'Missing cardCVV field',
        'notify' => true,
        'friendly' => "Please fill out all of the details on the payment page"
      ],
      '66065' => [
        'reason' => 'Missing customerName field',
        'notify' => true,
        'friendly' => "Please fill out all of the details on the payment page"
      ],
      '66066' => [
        'reason' => 'Missing customerAddress field',
        'notify' => true,
        'friendly' => "Please fill out all of the details on the payment page"
      ],
      '66067' => [
        'reason' => 'Missing customerPostCode field',
        'notify' => true,
        'friendly' => "Please fill out all of the details on the payment page"
      ],
      '66068' => [
        'reason' => 'Missing customerEmail field',
        'notify' => true,
        'friendly' => "Please fill out all of the details on the payment page"
      ],
      '66069' => [
        'reason' => 'Missing customerPhone field (reserved for future use)',
        'notify' => true,
        'friendly' => "Please fill out all of the details on the payment page"
      ],
      '66070' => [
        'reason' => 'Missing countyCode field',
        'notify' => true,
        'friendly' => "Please fill out all of the details on the payment page"
      ],
      '66071' => [
        'reason' => 'Missing transactionUnique field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66072' => [
        'reason' => 'Missing orderRef field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66073' => [
        'reason' => 'Missing remoteAddress field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66074' => [
        'reason' => 'Missing redirectURL field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66075' => [
        'reason' => 'Missing callbackURL field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66076' => [
        'reason' => 'Missing merchantData field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66077' => [
        'reason' => 'Missing origin field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66078' => [
        'reason' => 'Missing duplicateDelay field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66079' => [
        'reason' => 'Missing itemQuantity field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66080' => [
        'reason' => 'Missing itemDescription field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66081' => [
        'reason' => 'Missing itemGrossValue field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66082' => [
        'reason' => 'Missing taxValue field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66083' => [
        'reason' => 'Missing discountValue field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66084' => [
        'reason' => 'Missing taxDiscountDescription field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66085' => [
        'reason' => 'Missing xref field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66086' => [
        'reason' => 'Missing type field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66087' => [
        'reason' => 'Missing signature field (field is required if message signing is enabled)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66088' => [
        'reason' => 'Missing authorisationCode field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66089' => [
        'reason' => 'Missing transactionID field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66090' => [
        'reason' => 'Missing threeDSRequired field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66091' => [
        'reason' => 'Missing threeDSMD field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66092' => [
        'reason' => 'Missing threeDSPaRes field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66093' => [
        'reason' => 'Missing threeDSECI field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66094' => [
        'reason' => 'Missing threeDSCAVV field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66095' => [
        'reason' => 'Missing threeDSXID field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66096' => [
        'reason' => 'Missing threeDSEnrolled field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66097' => [
        'reason' => 'Missing threeDSAuthenticated field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66098' => [
        'reason' => 'Missing threeDSCheckPref field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66099' => [
        'reason' => 'Missing cv2CheckPref field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66100' => [
        'reason' => 'Missing addressCheckPref field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66101' => [
        'reason' => 'Missing postcodeCheckPref field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66102' => [
        'reason' => 'Missing captureDelay field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66103' => [
        'reason' => 'Missing orderDate field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66104' => [
        'reason' => 'Missing grossAmount field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66105' => [
        'reason' => 'Missing netAmount field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66016' => [
        'reason' => 'Missing taxRate field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66160' => [
        'reason' => 'Missing cardExpiryDate field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66161' => [
        'reason' => 'Missing cardStartDate field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66304' => [
        'reason' => 'Invalid request',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66305' => [
        'reason' => 'Invalid merchantID field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66306' => [
        'reason' => 'Reserved for future use. Contact customer support if this error occurs',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66307' => [
        'reason' => 'Reserved for internal use. Contact customer support if this error occurs',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66308' => [
        'reason' => 'Reserved for internal use. Contact customer support if this error occurs',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66309' => [
        'reason' => 'Reserved for internal use. Contact customer support if this error occurs',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66310' => [
        'reason' => 'Reserved for internal use. Contact customer support if this error occurs',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66311' => [
        'reason' => 'Invalid action field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66312' => [
        'reason' => 'Invalid amount field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66313' => [
        'reason' => 'Invalid currencyCode field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66314' => [
        'reason' => 'Invalid cardNumber field',
        'notify' => true,
        'friendly' => "Invalid card number, please try again"
      ],
      '66315' => [
        'reason' => 'Invalid cardExpiryMonth field',
        'notify' => true,
        'friendly' => "Invalid card expiry month, please try again"
      ],
      '66316' => [
        'reason' => 'Invalid cardExpiryYear field',
        'notify' => true,
        'friendly' => "Invalid card expiry year, please try again"
      ],
      '66317' => [
        'reason' => 'Invalid cardStartMonth field',
        'notify' => true,
        'friendly' => "Invalid card start month, please try again"
      ],
      '66318' => [
        'reason' => 'Invalid cardStartYear field',
        'notify' => true,
        'friendly' => "Invalid card start year, please try again"
      ],
      '66319' => [
        'reason' => 'Invalid cardIssueNumber field',
        'notify' => true,
        'friendly' => "Invalid card issue number, please try again"
      ],
      '66320' => [
        'reason' => 'Invalid cardCVV field',
        'notify' => true,
        'friendly' => "Invalid CVV, please try again"
      ],
      '66321' => [
        'reason' => 'Invalid customerName field',
        'notify' => true,
        'friendly' => "Invalid name, please try again"
      ],
      '66322' => [
        'reason' => 'Invalid customerAddress field',
        'notify' => true,
        'friendly' => "Invalid address, please try again"
      ],
      '66323' => [
        'reason' => 'Invalid customerPostCode field',
        'notify' => true,
        'friendly' => "Invalid postcode, please try again"
      ],
      '66324' => [
        'reason' => 'Invalid customerEmail field',
        'notify' => true,
        'friendly' => "Invalid email, please try again"
      ],
      '66325' => [
        'reason' => 'Invalid customerPhone field',
        'notify' => true,
        'friendly' => "Invalid phone number, please try again"
      ],
      '66326' => [
        'reason' => 'Invalid countyCode field',
        'notify' => true,
        'friendly' => "Please fill out all of the details on the payment page"
      ],
      '66327' => [
        'reason' => 'Invalid transactionUnique field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66328' => [
        'reason' => 'Invalid orderRef field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66329' => [
        'reason' => 'Invalid remoteAddress field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66330' => [
        'reason' => 'Invalid redirectURL field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66331' => [
        'reason' => 'Invalid callbackURL field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66332' => [
        'reason' => 'Invalid merchantData field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66333' => [
        'reason' => 'Invalid origin field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66334' => [
        'reason' => 'Invalid duplicateDelay field. Refer to Section 14.',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66335' => [
        'reason' => 'Invalid itemQuantity field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66336' => [
        'reason' => 'Invalid itemDescription field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66337' => [
        'reason' => 'Invalid itemGrossValue field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66338' => [
        'reason' => 'Invalid taxValue field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66339' => [
        'reason' => 'Invalid discountValue field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66340' => [
        'reason' => 'Invalid taxDiscountDescription field (reserved for future use)',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66341' => [
        'reason' => 'Invalid xref field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66342' => [
        'reason' => 'Invalid type field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66343' => [
        'reason' => 'Invalid signature field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66344' => [
        'reason' => 'Invalid authorisationCode field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66345' => [
        'reason' => 'Invalid transactionID field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66356' => [
        'reason' => 'Invalid threeDSRequired field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66347' => [
        'reason' => 'Invalid threeDSMD field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66348' => [
        'reason' => 'Invalid threeDSPaRes field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66349' => [
        'reason' => 'Invalid threeDSECI field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66350' => [
        'reason' => 'Invalid threeDSCAVV field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66351' => [
        'reason' => 'Invalid threeDSXID field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66352' => [
        'reason' => 'Invalid threeDSEnrolled field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66353' => [
        'reason' => 'Invalid threeDSAuthenticated field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66354' => [
        'reason' => 'Invalid threeDSCheckPref field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66355' => [
        'reason' => 'Invalid cv2CheckPref field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66356' => [
        'reason' => 'Invalid addressCheckPref field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66357' => [
        'reason' => 'Invalid postcodeCheckPref field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66358' => [
        'reason' => 'Invalid captureDelay field.',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66359' => [
        'reason' => 'Invalid orderDate field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66360' => [
        'reason' => 'Invalid grossAmount field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66361' => [
        'reason' => 'Invalid netAmount field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66362' => [
        'reason' => 'Invalid taxRate field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66363' => [
        'reason' => 'Invalid taxReason field',
        'notify' => true,
        'friendly' => "Sorry - we're looking into this"
      ],
      '66416' => [
        'reason' => 'Invalid card expiry date. Must be a date sometime in the next 10 years',
        'notify' => false,
        'friendly' => "Invalid card expiry date, please try again"
      ],
      '66417' => [
        'reason' => 'Invalid card start date. Must be a date sometime in the last 10 years',
        'notify' => false,
        'friendly' => "Invalid card start date, please try again"
      ]
    ];
}
