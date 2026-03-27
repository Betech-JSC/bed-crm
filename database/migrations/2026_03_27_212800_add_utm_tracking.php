<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add UTM fields to leads table
        Schema::table('leads', function (Blueprint $table) {
            $table->string('utm_source')->nullable()->after('source');
            $table->string('utm_medium')->nullable()->after('utm_source');
            $table->string('utm_campaign')->nullable()->after('utm_medium');
            $table->string('utm_term')->nullable()->after('utm_campaign');
            $table->string('utm_content')->nullable()->after('utm_term');
            $table->string('landing_page')->nullable()->after('utm_content');
            $table->string('referrer_url')->nullable()->after('landing_page');
        });

        // UTM Links table – saved UTM links for quick reuse
        Schema::create('utm_links', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('created_by');

            $table->string('name');                    // Descriptive name
            $table->text('base_url');                   // Target URL
            $table->string('utm_source');               // google, facebook, zalo, newsletter
            $table->string('utm_medium');               // cpc, social, email, organic, referral
            $table->string('utm_campaign');              // spring_sale, product_launch
            $table->string('utm_term')->nullable();     // keyword
            $table->string('utm_content')->nullable();  // banner_v1, cta_button
            $table->text('full_url');                   // Generated full URL

            // Stats
            $table->unsignedInteger('clicks_count')->default(0);
            $table->unsignedInteger('leads_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['account_id', 'utm_source']);
            $table->index(['account_id', 'utm_campaign']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utm_links');

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'utm_source', 'utm_medium', 'utm_campaign',
                'utm_term', 'utm_content', 'landing_page', 'referrer_url',
            ]);
        });
    }
};
