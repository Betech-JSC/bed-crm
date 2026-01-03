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
        Schema::create('leads', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->index();
            $table->string('name', 100);
            $table->string('phone', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('company', 100)->nullable();
            $table->string('source', 50)->nullable();
            $table->string('status', 50)->default('new');
            $table->string('priority', 50)->nullable();
            $table->integer('score')->nullable();
            $table->integer('icp_id')->nullable()->index();
            $table->json('scoring_details')->nullable();
            $table->json('enrichment_data')->nullable();
            $table->timestamp('last_scored_at')->nullable();
            $table->integer('email_opens')->default(0);
            $table->integer('email_clicks')->default(0);
            $table->timestamp('last_email_open_at')->nullable();
            $table->timestamp('last_email_click_at')->nullable();
            $table->integer('website_visits')->default(0);
            $table->integer('page_views')->default(0);
            $table->json('visited_pages')->nullable();
            $table->timestamp('last_website_visit_at')->nullable();
            $table->integer('time_on_site_seconds')->default(0);
            $table->integer('sla_setting_id')->nullable()->index();
            $table->timestamp('sla_started_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->integer('response_time_minutes')->nullable();
            $table->string('sla_status', 50)->nullable();
            $table->timestamp('sla_warning_sent_at')->nullable();
            $table->timestamp('sla_breach_sent_at')->nullable();
            $table->timestamp('sla_resolved_at')->nullable();
            $table->integer('assigned_to')->nullable()->index();
            $table->string('industry', 100)->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Note: Unique constraints on nullable columns handled in application logic
            // MySQL allows multiple NULLs in unique columns, so we check in model/controller
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
