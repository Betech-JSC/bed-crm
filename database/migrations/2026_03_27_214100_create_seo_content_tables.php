<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SEO Keywords tracking
        Schema::create('seo_keywords', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');

            $table->string('keyword');
            $table->string('url')->nullable();           // Target page URL
            $table->unsignedSmallInteger('current_rank')->nullable();
            $table->unsignedSmallInteger('previous_rank')->nullable();
            $table->unsignedSmallInteger('best_rank')->nullable();
            $table->string('search_engine')->default('google');
            $table->unsignedInteger('search_volume')->nullable();
            $table->string('difficulty')->nullable();    // easy, medium, hard
            $table->string('status')->default('tracking'); // tracking, paused, achieved

            $table->date('last_checked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->index(['account_id', 'keyword']);
        });

        // SEO Rank history
        Schema::create('seo_rank_history', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('seo_keyword_id');
            $table->unsignedSmallInteger('rank');
            $table->date('checked_date');
            $table->timestamps();

            $table->foreign('seo_keyword_id')->references('id')->on('seo_keywords')->onDelete('cascade');
            $table->index(['seo_keyword_id', 'checked_date']);
        });

        // SEO Site Audit issues
        Schema::create('seo_audit_issues', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');

            $table->string('page_url');
            $table->string('issue_type');               // missing_title, slow_page, broken_link, no_h1, missing_alt, duplicate_meta
            $table->string('severity')->default('warning'); // critical, warning, info
            $table->text('description')->nullable();
            $table->string('status')->default('open');  // open, fixed, ignored
            $table->text('recommendation')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->index(['account_id', 'status']);
        });

        // Content Calendar
        Schema::create('content_calendar', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('assigned_to')->nullable();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('content_type');             // blog, social, email, video, infographic
            $table->string('channel');                  // website, facebook, instagram, tiktok, linkedin, youtube, zalo
            $table->string('status')->default('idea');  // idea, planned, in_progress, review, published, archived
            $table->string('priority')->default('medium'); // low, medium, high, urgent

            $table->date('planned_date')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->text('content_body')->nullable();
            $table->json('media')->nullable();          // Images, videos
            $table->json('tags')->nullable();
            $table->json('seo_meta')->nullable();       // target keyword, meta title, desc

            // Performance
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->unsignedInteger('leads_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->index(['account_id', 'planned_date']);
            $table->index(['account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_calendar');
        Schema::dropIfExists('seo_audit_issues');
        Schema::dropIfExists('seo_rank_history');
        Schema::dropIfExists('seo_keywords');
    }
};
