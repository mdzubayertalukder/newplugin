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
        Schema::create('withdrawal_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('minimum_withdrawal_amount', 10, 2)->default(50.00);
            $table->decimal('maximum_withdrawal_amount', 10, 2)->nullable();
            $table->decimal('withdrawal_fee_percentage', 5, 2)->default(0);
            $table->decimal('withdrawal_fee_fixed', 10, 2)->default(0);
            $table->integer('withdrawal_processing_days')->default(3);
            $table->boolean('auto_approve_withdrawals')->default(false);
            $table->text('withdrawal_terms')->nullable();
            $table->text('bank_requirements')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_settings');
    }
};
