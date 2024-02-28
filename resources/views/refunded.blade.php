<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="theme-color" content="#000000">

  <meta name="title" content="Checkout">
  <meta name="description" content="Checkout">

  <title>Checkout</title>

  <link rel="stylesheet" href="https://use.typekit.net/xoa1dqw.css?v=2">
  <link rel="stylesheet" href="{{ mix('css/checkout.css') }}">
</head>
<body>
  <div class="bg-white h-screen">
    <!-- Background color split screen for large screens -->
    <div class="fixed left-0 top-0 hidden h-full w-1/2 bg-black lg:block" aria-hidden="true"></div>
    <div class="fixed right-0 top-0 hidden h-full w-1/2 bg-white lg:block" aria-hidden="true"></div>

    <header class="fixed top-0 left-0 w-full lg:pt-16">
      <div class="bg-black py-6 mx-auto max-w-7xl lg:grid lg:grid-cols-2 lg:gap-x-16 lg:bg-transparent lg:px-8 lg:pb-10 ">
        <div class="mx-auto flex max-w-2xl px-4 lg:w-full lg:max-w-lg lg:px-0">
          <a href="#">
            <span class="sr-only">Checkout</span>
            <p class="font-ydr text-sm text-white uppercase font-bold lg:hidden">CHECKOUT</p>
            <p class="font-ydr text-3xl text-white uppercase font-bold hidden lg:block">CHECKOUT</p>
          </a>
        </div>
      </div>
    </header>

    <main class="relative mx-auto max-w-7xl gap-x-16 flex flex-col lg:flex-row lg:px-8 h-full navbarMargin lg:mt-12">
      <h1 class="sr-only">Checkout</h1>

      <section aria-labelledby="summary-heading" class="bg-black pb-8 pt-6 text-indigo-300 md:px-10 lg:mx-auto lg:w-full lg:max-w-lg lg:bg-transparent lg:px-0 lg:pb-24 lg:pt-0">
        <div class="mx-auto max-w-2xl px-4 lg:max-w-none lg:px-0">
          <h2 id="summary-heading" class="sr-only">Payment summary</h2>

          <dl>
            <dt class="text-sm font-medium text-white font-ydr uppercase font-bold">Amount due</dt>
            @if(isset($payment))
              <dd class="mt-1 text-3xl font-bold tracking-tight text-white font-ydr uppercase font-bold">£{{ $payment->getTotalValue() }}</dd>
            @endif
          </dl>
        </div>
      </section>

      <section aria-labelledby="payment-and-shipping-heading" class="pb-8 pt-4 order-2 h-full lg:mx-auto lg:w-full lg:max-w-lg lg:pb-24 lg:pt-0 flex flex-col">
        <div class="max-w-2xl px-4 lg:max-w-none lg:px-0 h-full flex flex-col mx-auto w-full">
          <div id="errorContainer" class="w-full p-4 border border-2 border-red-600 hidden">
            <p id="errorMessage" class="text-red-600 text-center text-sm text-bold font-ydr uppercase font-bold"></p>
          </div>

          <div id="successContainer" class="w-full p-4 border border-2 border-green-600">
            <p id="successMessage" class="text-green-600 text-center text-sm text-bold font-ydr uppercase font-bold">Refund Successful!</p>

            <div class="mt-4 flex justify-center items-center lg:items-start border-t border-gray-200 pt-6 w-full md:w-auto">
              <a href="/" class="checkoutButton min-w-sm w-full rounded-none border border-transparent bg-black px-4 py-2 text-sm font-medium text-white shadow-sm focus:ring-2 focus:ring-black focus:ring-offset-2 focus:ring-offset-black focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2 focus:ring-offset-black flex justify-center items-center">Try another transaction</a>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
