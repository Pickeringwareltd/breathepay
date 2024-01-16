<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBreathepayChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('breathepay_charges', function (Blueprint $table) {
          $table->bigIncrements('id');

          $table->string('transaction_id')->nullable();
          $table->integer('amount');
          $table->string('country_code')->default('gbp');
          $table->string('currency_code')->default('gbp');
          $table->string('status')->default('pending');
          $table->boolean('successful')->nullable();
          $table->tinyInteger('captured')->nullable();

          $table->longText('payment_token')->nullable();

          $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('breathepay_charges');
    }
}
