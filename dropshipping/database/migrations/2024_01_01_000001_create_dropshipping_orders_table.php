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
        Schema::create('dropshipping_orders', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('order_number')->unique();
            $table->unsignedBigInteger('original_order_id')->nullable();
            $table->string('order_code')->nullable();
            $table->unsignedBigInteger('local_product_id')->nullable();
            $table->unsignedBigInteger('dropshipping_product_id')->nullable();
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('commission_rate', 5, 2)->default(20.00);
            $table->decimal('commission_amount', 10, 2);
            $table->decimal('tenant_earning', 10, 2);
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('fulfillment_note')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedBigInteger('submitted_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dropshipping_orders');
    }
};
