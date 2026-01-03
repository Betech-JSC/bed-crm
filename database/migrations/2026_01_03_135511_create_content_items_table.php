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
        Schema::create('content_items', function (Blueprint $table) {
            $table->id();
            $table->integer('account_id')->index();
            $table->integer('created_by')->index(); // User who created
            $table->integer('template_id')->nullable()->index(); // Content template used
            $table->string('type', 50); // text, image, video, carousel, etc.
            $table->string('title', 255)->nullable();
            $table->text('content'); // Main content (text, image URL, etc.)
            $table->json('metadata')->nullable(); // Additional data (images, hashtags, mentions, etc.)
            $table->string('ai_model', 100)->nullable(); // AI model used (gpt-4, claude, etc.)
            $table->json('ai_metadata')->nullable(); // AI generation metadata (tokens, cost, etc.)
            $table->string('status', 50)->default('draft'); // draft, approved, published, archived
            $table->json('tags')->nullable();
            $table->integer('usage_count')->default(0); // Times used in posts
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['account_id', 'type', 'status']);
            $table->index(['account_id', 'created_by']);
            $table->index(['account_id', 'template_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_items');
    }
};
