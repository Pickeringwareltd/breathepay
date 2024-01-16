<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BreathepayCharge;

class IndexController extends Controller
{
    public function index() {
      return view('welcome');
    }

    public function create(Request $request) {
      $charge = BreathepayCharge::create([
        'amount' => $request->amount,
        'country_code' => 'gbp',
        'currency_code' => 'gbp',
        'status' => 'pending'
      ]);

      return redirect('/pay?payment=' . $charge->id);
    }
}
