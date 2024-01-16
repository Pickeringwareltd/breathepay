import axios from 'axios';

var form;
var cardelem;
var paymentForm;

var browserInfo;
var token;

let isLoading = false;

var script = document.currentScript;
var result;
var order;
var csrf;

window.onerror = function (msg, url, lineNo, columnNo, error) {
            sendLog({
              order: order,
              title: 'Error on client',
              data: msg
            });
            return false;
        };

document.addEventListener('DOMContentLoaded', function() {
    result = script.getAttribute('result');
    order = script.getAttribute('order');
    csrf = script.getAttribute('csrf');

    if(result) {
      result = JSON.parse(result);

      if(result) {
        switch(result.status) {
          case '3ds':
            submit3DSForm();
            break;
          case 'acs':
            submitAcsForm();
            break;
          case 'succeeded':
            showSuccess();
            break;
          case 'error':
          case 'failed':
            showError();
            break;
        }
      }
    } else {
      setupCardForm();
    }

    listenForButtonClicks();
});

function listenForButtonClicks() {
  document.addEventListener('click', function (event) {

  	// If the clicked element doesn't have the right selector, bail
  	if (event.target.matches('#tryAgain')) {
    	event.preventDefault();
      window.location.href = '/pay?order=' + order;
    }

    return;
  }, false);
}

function setupCardForm() {
  cardelem = document.getElementById('card-element');
  paymentForm = document.getElementById('paymentForm');

  var submit = document.getElementById('submitButton');

  browserInfo = document.getElementById('browserInfo');
  token = document.getElementById('token');

  if(cardelem && submit) {
    form = new window.hostedFields.classes.Form(cardelem, {
        "autoSetup":true,
        "autoSubmit":true,
        "tokenise":".add-to-token",
        "stylesheets":"#hostedfield-stylesheet",
        "fields":{
          "any":{
            "nativeEvents":true
          },
          "cardDetails":{
            "selector":"#cardDetails",
            "style":"font: 400 16px Helvetica, sans-serif;",
            "placeholder": "0000 0000 0000 0000|00/00|000"
          }
        },
        "classes":{"invalid":"error"},
        "merchantID": '113812'
    });
    submit.addEventListener('click', getCardDetails);
  }
}

function getCardDetails() {
  form.validate().then(function(valid) {

    if(valid) {
      form.getPaymentDetails().then(function(details) {
        if(details.success) {
          setLoading(true);

          token.value = details.paymentToken;
          browserInfo.value = JSON.stringify(getBrowserInfo());
          paymentForm.submit();
        } else {
          setLoading(false);
          showCardError(details.defaultErrorMessage);
        }
      });
    } else {
      setLoading(false);
      showCardError("Please provide a valid value for this field.");
    }
  });
}

function submit3DSForm() {
  showLoading();

  var threeDSContainer = document.createElement('div');
  threeDSContainer.setAttribute("id", "threeDSContainer");
  threeDSContainer.classList.add('flexcenter');
  document.body.appendChild(threeDSContainer);

  var threeDSIframe = document.createElement("iframe");
  threeDSIframe.setAttribute("id", "threeDSIframe");
  threeDSIframe.setAttribute("height", "1");
  threeDSIframe.setAttribute("width", "1");
  threeDSIframe.setAttribute("sandbox", "allow-scripts allow-forms allow-top-navigation allow-same-origin allow-popups allow-modals allow-popups allow-popups-to-escape-sandbox allow-top-navigation-by-user-activation");
  threeDSIframe.setAttribute("name", "threeds_iframe");
  threeDSContainer.appendChild(threeDSIframe);

  var threeDSForm = document.createElement("form");
  threeDSForm.setAttribute("method", "post");
  threeDSForm.setAttribute("target", "threeds_iframe");
  threeDSForm.setAttribute("action", result.threeDSUrl);
  threeDSForm.classList.add('hidden');
  threeDSContainer.appendChild(threeDSForm);

  if(!result.display) {
    threeDSIframe.classList.add('hidden');
  } else {
    var threeDSBackground = document.createElement('div');
    threeDSBackground.setAttribute("id", "iframeBackground");
    threeDSContainer.appendChild(threeDSBackground);
    threeDSForm.setAttribute("target", "_parent");

    threeDSIframe.classList.remove('hidden');
  }

  let response = JSON.parse(result.threeDSMethodData);
  Object.keys(response).forEach(key => {
    var input = document.createElement("input");
    input.setAttribute("type", "text");
    input.setAttribute("name", key);
    input.value = response[key];

    threeDSForm.appendChild(input);
  });

  var input = document.createElement("input");
  input.setAttribute("type", "text");
  input.setAttribute("name", "_token");
  input.value = csrf;
  threeDSForm.appendChild(input);

  var button = document.createElement("button");
  button.setAttribute("type", "submit");
  threeDSForm.appendChild(button);

  button.click();

  sendLog({
    order: order,
    title: 'Submitting 3DS form',
    data: ''
  });

  setTimeout(() => {
    sendLog({
      order: order,
      title: 'Timed out...',
      data: ''
    });

    result = {
      reason: 'Could not connect to payment provider, please try again',
      callback: window.location.href
    };
    hideLoading();
    showError();
  }, 10000);
}

function submitAcsForm() {
  showLoading();

  var acsContainer = document.createElement('div');
  document.body.appendChild(acsContainer);

  var acsIframe = document.createElement("iframe");
  acsIframe.setAttribute("name", "acs_iframe");
  acsIframe.setAttribute("height", "1");
  acsIframe.setAttribute("width", "1");
  acsIframe.setAttribute("sandbox", "allow-scripts allow-forms allow-top-navigation");
  acsContainer.appendChild(acsIframe);

  var acsForm = document.createElement("form");
  acsForm.setAttribute("method", "post");
  acsForm.setAttribute("target", "_parent");
  acsForm.setAttribute("action", result.url);
  acsForm.classList.add('hidden');
  acsContainer.appendChild(acsForm);

  var input = document.createElement("input");
  input.setAttribute("type", "text");
  input.setAttribute("name", "threeDSResponse");
  input.value = JSON.stringify(result.post);
  acsForm.appendChild(input);

  var input = document.createElement("input");
  input.setAttribute("type", "text");
  input.setAttribute("name", "_token");
  input.value = csrf;
  acsForm.appendChild(input);

  var button = document.createElement("button");
  button.setAttribute("type", "submit");
  acsForm.appendChild(button);

  button.click();

  sendLog({
    order: order,
    title: 'Submitting ACS form',
    data: ''
  });

  setTimeout(() => {
    sendLog({
      order: order,
      title: 'Timed out...',
      data: ''
    });

    result = {
      reason: 'Could not connect to payment provider, please try again',
      callback: window.location.href
    };
    hideLoading();
    showError();
  }, 10000);
}

function showSuccess() {
  var successContainer = document.getElementById('successContainer');
  successContainer.classList.remove('hidden');

  if(result.callback) {
    setTimeout(() => {
      window.location.replace(result.callback);
    }, 3000);
  } else {
    var successMessage = document.getElementById('successMessage');
    successMessage.innerHTML = result.message;
  }
}

function showLoading() {
  var loadingContainer = document.getElementById('loadingContainer');
  loadingContainer.classList.remove('hidden');
}

function hideLoading() {
  var loadingContainer = document.getElementById('loadingContainer');
  loadingContainer.classList.add('hidden');
}

function showError() {
  var errorContainer = document.getElementById('errorContainer');
  var errorMessage = document.getElementById('errorMessage');

  errorMessage.innerHTML = result.reason ?? 'Oops, somethings gone wrong';
  errorContainer.classList.remove('hidden');

  sendLog({
    order: order,
    title: 'Showing error',
    data: result.reason ?? 'Oops, somethings gone wrong'
  });

  setTimeout(() => {
    window.location.replace(result.callback);
  }, 1000);
}

function showCardError(reason) {
  cardErrorMessage = document.getElementById('cardErrorMessage');

  cardErrorMessage.innerHTML = reason ?? 'Oops, somethings gone wrong';
  cardErrorMessage.classList.remove('hidden');
}

function setLoading(loading) {
    isLoading = loading;
    const button = document.getElementById('submitButton');
    const loadingIcon = document.getElementById('loadingIcon');
    const buttonText = document.getElementById('buttonText');

    if (isLoading) {
        loadingIcon.style.borderTopColor = "white"; // Set the color as needed
        loadingIcon.classList.remove('hidden');
        buttonText.classList.add('hidden');
    } else {
        loadingIcon.classList.add('hidden');
        buttonText.classList.remove('hidden');
    }
}

function getBrowserInfo() {
  let browserInfo = {};

  var screen_width = (window && window.screen ? window.screen.width : '0');
  var screen_height = (window && window.screen ? window.screen.height : '0');
  var screen_depth = (window && window.screen ? window.screen.colorDepth : '0');
  var identity = (window && window.navigator ? window.navigator.userAgent : '');
  var language = (window && window.navigator ? (window.navigator.language ? window.navigator.language : window.navigator.browserLanguage) : '');
  var timezone = (new Date()).getTimezoneOffset();
  var java = (window && window.navigator ? navigator.javaEnabled() : false);

  browserInfo.deviceIdentity = identity;
  browserInfo.deviceTimeZone = timezone;
  browserInfo.deviceCapabilities = 'javascript' + (java ? ',java' : '');
  browserInfo.deviceAcceptLanguage = language;
  browserInfo.deviceScreenResolution = screen_width + 'x' + screen_height + 'x' + screen_depth;

  return browserInfo;
}

function sendLog(object) {
  let formData = new FormData();
  Object.keys(object).forEach(function(key) {
      if(typeof object[key] === 'object') {
          formData.append(key, JSON.stringify(object[key]));
      } else {
          formData.append(key, object[key]);
      }
  });

  axios.post('/api/gateway/3ds/log',
      formData,
      {
          headers: {
              'Content-Type': 'multipart/form-data'
          }
      }
  );
}
