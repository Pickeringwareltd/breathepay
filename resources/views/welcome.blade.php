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
            <p class="font-ydr text-sm text-white uppercase font-bold lg:hidden">CREATE PAYMENT</p>
            <p class="font-ydr text-3xl text-white uppercase font-bold hidden lg:block">CREATE PAYMENT</p>
          </a>
        </div>
      </div>
    </header>

    <main class="relative mx-auto max-w-7xl gap-x-16 flex flex-col lg:flex-row lg:px-8 h-full navbarMargin lg:mt-12">
      <h1 class="sr-only">Checkout</h1>

      <section aria-labelledby="summary-heading" class="bg-black pb-8 pt-6 text-indigo-300 md:px-10 lg:mx-auto lg:w-full lg:max-w-lg lg:bg-transparent lg:px-0 lg:pb-24 lg:pt-0">
        <div class="mx-auto max-w-2xl px-4 lg:max-w-none lg:px-0">

        </div>
      </section>

      <section aria-labelledby="payment-and-shipping-heading" class="pb-8 pt-4 order-2 h-full lg:mx-auto lg:w-full lg:max-w-lg lg:pb-24 lg:pt-0 flex flex-col">
        <h2 id="payment-and-shipping-heading" class="sr-only">Payment and shipping details</h2>

        <div class="max-w-2xl px-4 lg:max-w-none lg:px-0 h-full flex flex-col mx-auto w-full">
            <div class="mt-0">
              <div class="grid grid-cols-3 gap-x-4 gap-y-6 sm:grid-cols-4">
                <div class="col-span-3 sm:col-span-4">
                  <div class="mt-1">
                    <div class="form-row">
                        <form id="paymentForm" action="/pay/create" method="post" class="py-1 mt-3">
                          @csrf

                          <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 font-ydr uppercase font-bold">Amount in Pence</label>
                            <div class="mt-2">
                              <input type="numner" name="amount" id="amount" class="block w-full rounded-md border-0 py-1.5 px-4 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            </div>
                          </div>

                          <div class="mt-4 flex flex-grow lg:flex-grow-0 justify-end items-end lg:items-start border-t border-gray-200 pt-6 w-full md:w-auto">
                            <button type="submit" class="font-ydr uppercase font-bold checkoutButton min-w-sm w-full rounded-none border border-transparent bg-black px-4 py-2 text-sm font-medium text-white shadow-sm focus:ring-2 focus:ring-black focus:ring-offset-2 focus:ring-offset-black focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2 focus:ring-offset-black">
                                Create payment
                            </button>
                          </div>
                        </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
