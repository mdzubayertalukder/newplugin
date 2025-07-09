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
        Schema::create('tenant_balances', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->unique();
            $table->decimal('total_earnings', 12, 2)->default(0.00);
            $table->decimal('available_balance', 12, 2)->default(0.00);
            $table->decimal('pending_balance', 12, 2)->default(0.00);
            $table->decimal('withdrawn_amount', 12, 2)->default(0.00);
            $table->integer('total_orders')->default(0);
            $table->integer('pending_orders')->default(0);
            $table->integer('approved_orders')->default(0);
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_balances');
    }
};
