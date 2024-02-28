<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('breathepay_charges', function(Blueprint $table) {
          $table->dropColumn('signature');
          $table->dropColumn('payment_token');
          $table->string('xref', 100)->nullable();
          $table->timestamp('completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('breathepay_charges', function(Blueprint $table) {
          $table->string('signature')->nullable();
          $table->string('payment_token')->nullable();
          $table->dropColumn('xref');
          $table->dropColumn('completed_at');
        });
    }
};
