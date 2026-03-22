<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Video projects — each represents a video ad campaign
        Schema::create('video_projects', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('created_by');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('draft');          // draft, scripting, producing, review, published, archived
            $table->string('video_type')->default('product');    // product, testimonial, tutorial, promo, story, ugc
            $table->json('target_platforms')->nullable();        // ["tiktok","facebook","instagram","youtube"]
            $table->string('aspect_ratio')->default('9:16');     // 9:16, 16:9, 1:1, 4:5
            $table->integer('duration_seconds')->nullable();     // Target duration
            $table->string('language')->default('vi');

            // AI-generated content
            $table->text('ai_script')->nullable();               // Full video script
            $table->json('ai_scenes')->nullable();               // Storyboard scenes [{scene, text, visual, duration}]
            $table->text('ai_voiceover_text')->nullable();       // Voiceover/narration text
            $table->text('ai_music_suggestion')->nullable();     // Music mood/style suggestion
            $table->json('ai_hashtags')->nullable();             // Suggested hashtags
            $table->text('ai_caption')->nullable();              // Social media caption

            // Product reference
            $table->unsignedInteger('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->text('product_highlights')->nullable();      // Key selling points
            $table->decimal('product_price', 15, 2)->nullable();
            $table->string('product_url')->nullable();
            $table->string('promo_code')->nullable();

            // Assets
            $table->json('media_assets')->nullable();            // [{type, path, label}]
            $table->string('thumbnail_path')->nullable();
            $table->string('output_video_path')->nullable();

            // CTA & branding
            $table->string('cta_text')->nullable();              // "Mua ngay", "Đăng ký"
            $table->string('cta_url')->nullable();
            $table->string('brand_logo_path')->nullable();
            $table->string('brand_color')->nullable();

            // Publishing
            $table->json('publish_schedule')->nullable();        // [{platform, scheduled_at, status}]
            $table->timestamp('published_at')->nullable();

            $table->json('settings')->nullable();                // Extra settings
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        // Video templates — reusable templates
        Schema::create('video_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id')->nullable();   // null = system template
            $table->string('name');
            $table->string('category');                          // product, promo, story, tutorial, ugc
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->json('scene_structure');                     // Template scenes structure
            $table->string('aspect_ratio')->default('9:16');
            $table->integer('duration_seconds')->default(30);
            $table->json('style_config')->nullable();            // Colors, fonts, transitions
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_templates');
        Schema::dropIfExists('video_projects');
    }
};
