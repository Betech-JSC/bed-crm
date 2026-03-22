<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dropship_suppliers', function (Blueprint $table) {
            $table->id();
                        $table->unsignedInteger('account_id');
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('website')->nullable();
            $table->string('platform', 50)->nullable(); // aliexpress, cjdropshipping, etc
            $table->string('country', 80)->nullable();
            $table->text('address')->nullable();
            $table->json('shipping_methods')->nullable();
            $table->unsignedSmallInteger('avg_processing_days')->default(3);
            $table->unsignedSmallInteger('avg_shipping_days')->default(14);
            $table->text('return_policy')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('rating', 2, 1)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('dropship_products', function (Blueprint $table) {
            $table->id();
                        $table->unsignedInteger('account_id');
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('dropship_suppliers')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('supplier_sku')->nullable();
            $table->string('supplier_product_name')->nullable();
            $table->string('supplier_url')->nullable();
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->unsignedInteger('moq')->default(1);
            $table->unsignedSmallInteger('lead_time_days')->default(7);
            $table->json('variants_map')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('dropship_orders', function (Blueprint $table) {
            $table->id();
                        $table->unsignedInteger('account_id');
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('dropship_suppliers')->cascadeOnDelete();
            $table->string('order_number', 30)->unique();
            $table->string('shopify_order_id')->nullable();
            $table->string('shopify_order_number')->nullable();

            // Customer info
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 50)->nullable();

            // Shipping
            $table->string('shipping_name')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('shipping_city', 100)->nullable();
            $table->string('shipping_country', 80)->nullable();
            $table->string('shipping_zip', 20)->nullable();
            $table->string('shipping_method', 100)->nullable();

            // Items & Pricing
            $table->json('items')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('profit', 15, 2)->default(0);
            $table->string('currency', 10)->default('USD');

            // Status
            $table->string('order_status', 30)->default('pending');
            $table->string('fulfillment_status', 30)->default('unfulfilled');
            $table->string('payment_status', 30)->default('unpaid');

            // Tracking
            $table->string('supplier_order_id')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->string('carrier', 100)->nullable();

            // Dates
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'order_status']);
            $table->index(['account_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dropship_orders');
        Schema::dropIfExists('dropship_products');
        Schema::dropIfExists('dropship_suppliers');
    }
};
