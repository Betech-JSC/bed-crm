<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id')->index();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->enum('type', ['product', 'service'])->default('product');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('unit')->default('cái'); // cái, giờ, tháng, gói...
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->string('currency', 3)->default('VND');
            $table->decimal('tax_rate', 5, 2)->default(10); // VAT %
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['account_id', 'type']);
            $table->index(['account_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
