<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_guides', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id')->index();
            $table->string('title');
            $table->string('category')->nullable(); // VD: Bắt đầu, Leads, Deals, Báo giá, Hợp đồng...
            $table->string('icon')->nullable(); // PrimeIcon class
            $table->text('summary')->nullable();
            $table->longText('content')->nullable(); // Rich-text content
            $table->boolean('is_published')->default(true);
            $table->integer('sort_order')->default(0);
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_guides');
    }
};
