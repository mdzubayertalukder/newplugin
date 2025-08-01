<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddMultipurcpayPaymentMethod extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Insert Multipurcpay payment method into tl_saas_payment_methods table
        DB::table('tl_saas_payment_methods')->insert([
            'id' => 18,
            'name' => 'multipurcpay',
            'status' => 1, // Active status
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove Multipurcpay payment method from tl_saas_payment_methods table
        DB::table('tl_saas_payment_methods')->where('id', 18)->delete();
    }
}