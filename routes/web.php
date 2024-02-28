<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\IndexController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [IndexController::class, 'index']);

Route::get('/pay', [GatewayController::class, 'checkoutPage']);
Route::post('/pay', [GatewayController::class, 'checkout']);
Route::post('/pay/create', [IndexController::class, 'create']);

Route::post('/gateway/payment', [GatewayController::class, 'transactionCallback']);
Route::post('/gateway/3ds', [GatewayController::class, 'threeDSSecure']);
Route::post('/gateway/iframe', [GatewayController::class, 'threeDSIframe']);

Route::get('/refund', [GatewayController::class, 'refund']);
