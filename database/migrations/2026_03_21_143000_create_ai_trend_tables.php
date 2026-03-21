<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Monitor schedules — each user/account can configure what sources to track and when
        Schema::create('ai_trend_monitors', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('created_by');

            $table->string('name');                         // e.g. "GitHub AI Trending"
            $table->string('source');                       // github, hackernews, producthunt, devto, custom_rss
            $table->json('source_config')->nullable();      // e.g. {"language":"python","since":"daily","spoken_language":"en"}
            $table->string('schedule_frequency');            // hourly, every_6h, every_12h, daily, weekly
            $table->string('schedule_time')->default('09:00'); // HH:mm for daily/weekly
            $table->string('schedule_day')->nullable();     // mon, tue, ... for weekly
            $table->boolean('notify_in_app')->default(true);
            $table->boolean('notify_email')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['account_id', 'is_active']);
            $table->index('next_run_at');
        });

        // Fetched trend items
        Schema::create('ai_trend_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id');
            $table->unsignedBigInteger('monitor_id');

            $table->string('source');                       // github, hackernews, producthunt, devto
            $table->string('external_id')->nullable();      // unique ID from source
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('url');
            $table->string('image_url')->nullable();
            $table->string('author')->nullable();
            $table->string('language')->nullable();         // programming language for github
            $table->integer('stars')->default(0);           // github stars
            $table->integer('stars_today')->default(0);     // stars gained today
            $table->integer('forks')->default(0);
            $table->integer('score')->default(0);           // HN score, PH votes, etc.
            $table->integer('comments_count')->default(0);
            $table->json('tags')->nullable();               // topics/tags
            $table->json('extra_data')->nullable();         // any additional source-specific data
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('monitor_id')->references('id')->on('ai_trend_monitors')->cascadeOnDelete();
            $table->index(['account_id', 'source']);
            $table->index(['monitor_id', 'created_at']);
            $table->unique(['monitor_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_trend_items');
        Schema::dropIfExists('ai_trend_monitors');
    }
};
