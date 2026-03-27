<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_guidelines', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id');
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();

            // ── Brand Foundation ──
            $table->text('brand_purpose')->nullable();
            $table->text('brand_vision')->nullable();
            $table->text('brand_mission')->nullable();
            $table->text('brand_promise')->nullable();
            $table->json('brand_values')->nullable();       // [{name, description, icon}]
            $table->json('brand_personality')->nullable();   // [{trait, description, score}]
            $table->json('brand_positioning')->nullable();   // {target_audience, differentiation, pillars[], statement}
            $table->string('tagline', 500)->nullable();
            $table->json('value_propositions')->nullable();  // [{title, description}]

            // ── Visual Identity ──
            $table->json('primary_colors')->nullable();      // [{name, hex, rgb, usage}]
            $table->json('secondary_colors')->nullable();
            $table->json('neutral_colors')->nullable();
            $table->string('font_primary')->nullable();      // Google Font name
            $table->string('font_secondary')->nullable();
            $table->json('font_config')->nullable();         // {headings, body, accent, sizes{}}
            $table->string('logo_primary')->nullable();
            $table->string('logo_horizontal')->nullable();
            $table->string('logo_icon')->nullable();
            $table->string('logo_white')->nullable();
            $table->json('logo_guidelines')->nullable();     // {clear_space, min_size, dont_list[]}

            // ── Brand Voice ──
            $table->json('voice_traits')->nullable();        // [{trait, description, do_example, dont_example}]
            $table->json('tone_variations')->nullable();     // [{context, tone, example}]
            $table->json('writing_guidelines')->nullable();  // {vocabulary[], avoid_words[], grammar_rules[]}

            // ── Meta ──
            $table->string('status')->default('draft');      // draft/active/archived
            $table->unsignedInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->unsignedInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('account_id');
        });

        Schema::create('brand_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_guideline_id')->constrained()->cascadeOnDelete();
            $table->string('category');          // logo/icon/pattern/template/photo/illustration/document
            $table->string('name');
            $table->string('file_path');
            $table->string('file_type', 20);     // png/svg/pdf/jpg
            $table->unsignedInteger('file_size')->default(0);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable(); // {width, height, usage_notes}
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('uploaded_by')->nullable();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->index('brand_guideline_id');
            $table->index('category');
        });

        Schema::create('brand_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_guideline_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('action');            // created/updated/asset_uploaded/published
            $table->string('section')->nullable(); // foundation/colors/typography/voice/logo/asset
            $table->json('changes')->nullable();   // {field: {old, new}}
            $table->timestamps();

            $table->index(['brand_guideline_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_audit_logs');
        Schema::dropIfExists('brand_assets');
        Schema::dropIfExists('brand_guidelines');
    }
};
