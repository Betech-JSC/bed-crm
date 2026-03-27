<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Google My Business Locations
        Schema::create('gmb_locations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');

            $table->string('location_name');
            $table->string('google_location_id')->nullable();
            $table->string('place_id')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('category')->nullable();
            $table->json('business_hours')->nullable();
            $table->json('attributes')->nullable();

            // Insights
            $table->unsignedInteger('total_views')->default(0);
            $table->unsignedInteger('total_searches')->default(0);
            $table->unsignedInteger('total_actions')->default(0);
            $table->decimal('avg_rating', 2, 1)->default(0);
            $table->unsignedInteger('review_count')->default(0);

            $table->string('status')->default('active');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        // GMB Reviews
        Schema::create('gmb_reviews', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('gmb_location_id');

            $table->string('google_review_id')->nullable();
            $table->string('reviewer_name');
            $table->string('reviewer_photo_url')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('review_time')->nullable();

            $table->timestamps();

            $table->foreign('gmb_location_id')->references('id')->on('gmb_locations')->onDelete('cascade');
        });

        // GMB Posts
        Schema::create('gmb_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('gmb_location_id');

            $table->string('post_type')->default('update'); // update, event, offer
            $table->text('content');
            $table->string('image_url')->nullable();
            $table->string('cta_type')->nullable();         // learn_more, book, order, call
            $table->string('cta_url')->nullable();
            $table->date('event_start')->nullable();
            $table->date('event_end')->nullable();

            $table->string('status')->default('draft');     // draft, published, expired
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->foreign('gmb_location_id')->references('id')->on('gmb_locations')->onDelete('cascade');
        });

        // AI Content Templates
        Schema::create('ai_content_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');

            $table->string('name');
            $table->string('category');                      // blog_seo, social_caption, email_sequence, ad_copy
            $table->text('description')->nullable();
            $table->text('system_prompt');
            $table->text('user_prompt_template');
            $table->json('variables')->nullable();           // [{name, label, type, default}]
            $table->string('ai_model')->default('gpt-4');
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('usage_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        // AI Generated Content
        Schema::create('ai_generated_contents', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('template_id')->nullable();

            $table->string('title');
            $table->string('content_type');                  // blog, social, email, ad
            $table->json('input_data')->nullable();          // user inputs
            $table->longText('generated_content');
            $table->json('seo_suggestions')->nullable();     // title, meta, headings
            $table->string('status')->default('draft');      // draft, edited, published

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        // Chatbot Flows
        Schema::create('chatbot_flows', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('created_by');

            $table->string('name');
            $table->text('description')->nullable();
            $table->json('nodes')->nullable();               // visual flow nodes
            $table->json('edges')->nullable();               // connections between nodes
            $table->json('settings')->nullable();            // trigger conditions, appearance
            $table->string('trigger_type')->default('page_load'); // page_load, button_click, time_delay, exit_intent
            $table->string('trigger_value')->nullable();

            $table->string('status')->default('draft');      // draft, active, paused
            $table->unsignedInteger('conversations_count')->default(0);
            $table->unsignedInteger('leads_captured')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        // Chatbot Messages (conversation log)
        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chatbot_flow_id');
            $table->string('session_id');

            $table->string('sender')->default('bot');        // bot, visitor
            $table->string('node_id')->nullable();
            $table->text('message');
            $table->json('options_shown')->nullable();
            $table->string('option_selected')->nullable();
            $table->json('data_collected')->nullable();      // name, email, phone

            $table->timestamps();

            $table->foreign('chatbot_flow_id')->references('id')->on('chatbot_flows')->onDelete('cascade');
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_messages');
        Schema::dropIfExists('chatbot_flows');
        Schema::dropIfExists('ai_generated_contents');
        Schema::dropIfExists('ai_content_templates');
        Schema::dropIfExists('gmb_posts');
        Schema::dropIfExists('gmb_reviews');
        Schema::dropIfExists('gmb_locations');
    }
};
