<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Customer Reviews / Testimonials
        Schema::create('customer_reviews', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');

            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_company')->nullable();
            $table->string('customer_role')->nullable();
            $table->string('customer_avatar')->nullable();

            $table->text('review_text');
            $table->unsignedTinyInteger('rating')->default(5);   // 1-5 stars
            $table->string('platform')->default('direct');       // direct, google, facebook, trustpilot
            $table->string('service_type')->nullable();          // web_design, seo, crm, marketing
            $table->string('status')->default('pending');        // pending, approved, featured, rejected

            $table->string('video_url')->nullable();
            $table->json('media')->nullable();                   // screenshots, images

            $table->date('review_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->index(['account_id', 'status']);
        });

        // Referral Program
        Schema::create('referral_codes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('referrer_id')->nullable();  // contact/lead who refers

            $table->string('code')->unique();
            $table->string('referrer_name');
            $table->string('referrer_email')->nullable();

            $table->string('reward_type')->default('discount');  // discount, credit, commission
            $table->decimal('reward_value', 10, 2)->default(0);
            $table->string('reward_unit')->default('percent');   // percent, fixed

            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('uses_count')->default(0);
            $table->string('status')->default('active');         // active, paused, expired

            $table->date('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        // Referral Tracking
        Schema::create('referral_conversions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('referral_code_id');

            $table->string('referred_name');
            $table->string('referred_email')->nullable();
            $table->string('referred_phone')->nullable();
            $table->string('status')->default('pending');        // pending, qualified, converted, paid
            $table->decimal('deal_value', 12, 2)->nullable();
            $table->decimal('commission_amount', 10, 2)->nullable();

            $table->timestamps();

            $table->foreign('referral_code_id')->references('id')->on('referral_codes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_conversions');
        Schema::dropIfExists('referral_codes');
        Schema::dropIfExists('customer_reviews');
    }
};
