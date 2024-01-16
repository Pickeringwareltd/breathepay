<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThreeDSCheck extends Model
{
    protected $table = 'three_ds_check';

    protected $fillable = [
      'payment_id',
      'round',
      'show_iframe',
      'threeDSRef',
      'threeDSURL',
      'threeDSData'
    ];
}
