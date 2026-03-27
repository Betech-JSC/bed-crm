<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('created_by');

            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('draft');     // draft, published, archived

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('favicon')->nullable();

            // Design
            $table->json('blocks')->nullable();             // Page blocks/sections
            $table->json('style_settings')->nullable();     // Theme colors, fonts

            // Form integration
            $table->unsignedInteger('web_form_id')->nullable();

            // Analytics
            $table->unsignedInteger('visits_count')->default(0);
            $table->unsignedInteger('conversions_count')->default(0);

            // Domain
            $table->string('custom_domain')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('web_form_id')->references('id')->on('web_forms')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_pages');
    }
};
