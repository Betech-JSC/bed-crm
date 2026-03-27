<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Link-in-Bio pages
        Schema::create('link_bio_pages', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('created_by');

            $table->string('username')->unique();       // /bio/username
            $table->string('display_name');
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->string('theme')->default('default'); // default, dark, gradient, minimal

            $table->json('links')->nullable();           // [{title, url, icon, clicks}]
            $table->json('social_links')->nullable();    // [{platform, url}]
            $table->json('style_settings')->nullable();  // colors, fonts

            $table->unsignedInteger('total_views')->default(0);
            $table->unsignedInteger('total_clicks')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        // QR Codes
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('created_by');

            $table->string('name');
            $table->string('target_url');
            $table->string('short_code')->unique();     // tracking short link
            $table->string('qr_type')->default('url');  // url, vcard, wifi, text

            $table->json('design')->nullable();          // foreground, background, logo
            $table->json('content_data')->nullable();    // vcard/wifi details

            $table->unsignedInteger('scans_count')->default(0);
            $table->unsignedInteger('unique_scans')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
        Schema::dropIfExists('link_bio_pages');
    }
};
