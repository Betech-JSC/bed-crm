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
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->integer('account_id')->index();
            $table->integer('content_item_id')->index(); // Content being posted
            $table->integer('social_account_id')->index(); // Target social account
            $table->integer('created_by')->index(); // User who created the post
            $table->string('platform', 50); // facebook, linkedin, twitter, etc.
            $table->text('content'); // Final content (may differ from content_item)
            $table->json('media_urls')->nullable(); // Images, videos, etc.
            $table->timestamp('scheduled_at')->nullable(); // When to post
            $table->timestamp('posted_at')->nullable(); // When actually posted
            $table->string('status', 50)->default('draft'); // draft, scheduled, posting, published, failed
            $table->string('platform_post_id', 255)->nullable(); // External platform post ID
            $table->json('platform_metadata')->nullable(); // Platform response data
            $table->text('error_message')->nullable(); // Error if posting failed
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();
            $table->json('analytics')->nullable(); // Engagement metrics
            $table->timestamp('analytics_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['account_id', 'status', 'scheduled_at']);
            $table->index(['account_id', 'social_account_id', 'status']);
            $table->index(['account_id', 'content_item_id']);
            $table->index(['platform', 'status', 'scheduled_at']); // For scheduled job processing
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
