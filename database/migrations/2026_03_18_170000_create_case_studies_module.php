<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Case Study Management Module
     *
     * Architecture:
     * 1. case_studies — Core case study data
     * 2. case_study_media — Images, videos, documents
     * 3. case_study_tags — Tagging system (industry, client type)
     * 4. case_study_tag_pivot — Many-to-many: case study ↔ tag
     * 5. case_study_links — Many-to-many links to leads, deals, campaigns
     */
    public function up(): void
    {
        // ═══════════════════════════════════════════
        // 1. CASE STUDIES
        // ═══════════════════════════════════════════
        if (!Schema::hasTable('case_studies')) {
        Schema::create('case_studies', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id');
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();

            // Core info
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable(); // Short description
            $table->enum('service_category', [
                'website', 'marketing', 'seo', 'branding', 'landing_page', 'ai_agent',
            ]);

            // Client info
            $table->string('client_name');
            $table->string('client_industry')->nullable(); // e.g., "E-commerce", "Fintech"
            $table->string('client_company_size')->nullable(); // "startup", "smb", "enterprise"
            $table->string('client_logo_url')->nullable();
            $table->string('client_website')->nullable();

            // Case study content
            $table->text('problem')->nullable();     // The challenge
            $table->text('solution')->nullable();     // What we did
            $table->text('approach')->nullable();     // How we did it
            $table->text('result')->nullable();       // Outcomes narrative
            $table->json('result_metrics')->nullable(); // Structured metrics
            /*
             * result_metrics: [
             *   { "label": "Revenue Increase", "value": "250%", "icon": "pi pi-trending-up" },
             *   { "label": "Conversion Rate", "value": "3.5x", "icon": "pi pi-chart-line" },
             *   { "label": "ROI", "value": "400%", "icon": "pi pi-dollar" }
             * ]
             */

            // Testimonial
            $table->text('testimonial_quote')->nullable();
            $table->string('testimonial_author')->nullable();
            $table->string('testimonial_role')->nullable();
            $table->string('testimonial_avatar_url')->nullable();

            // Meta
            $table->string('featured_image_url')->nullable();
            $table->string('project_url')->nullable(); // Live project URL
            $table->date('project_start_date')->nullable();
            $table->date('project_end_date')->nullable();
            $table->integer('project_duration_days')->nullable();

            // Visibility & status
            $table->enum('visibility', ['public', 'private', 'unlisted'])->default('private');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->integer('view_count')->default(0);

            // Ownership
            $table->unsignedInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->unsignedInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'service_category', 'status']);
            $table->index(['account_id', 'visibility', 'is_featured']);
        });
        }

        // ═══════════════════════════════════════════
        // 2. CASE STUDY MEDIA
        // ═══════════════════════════════════════════
        if (!Schema::hasTable('case_study_media')) {
        Schema::create('case_study_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_study_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['image', 'video', 'document', 'link']);
            $table->string('title')->nullable();
            $table->string('url');
            $table->string('thumbnail_url')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('file_size')->nullable(); // bytes
            $table->text('caption')->nullable();
            $table->string('section')->default('gallery'); // gallery, before_after, process, result
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['case_study_id', 'section', 'sort_order']);
        });
        }

        // ═══════════════════════════════════════════
        // 3. TAGS (for case studies)
        // ═══════════════════════════════════════════
        if (!Schema::hasTable('case_study_tags')) {
        Schema::create('case_study_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id');
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->enum('type', ['industry', 'client_type', 'technology', 'custom'])->default('custom');
            $table->string('color', 7)->default('#6366F1');
            $table->timestamps();

            $table->unique(['account_id', 'slug']);
        });
        }

        // ═══════════════════════════════════════════
        // 4. TAG PIVOT
        // ═══════════════════════════════════════════
        if (!Schema::hasTable('case_study_tag_pivot')) {
        Schema::create('case_study_tag_pivot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_study_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_study_tag_id')->constrained('case_study_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['case_study_id', 'case_study_tag_id'], 'cs_tag_unique');
        });
        }

        // ═══════════════════════════════════════════
        // 5. LINKS TO CRM ENTITIES (leads, deals, campaigns)
        // ═══════════════════════════════════════════
        if (!Schema::hasTable('case_study_links')) {
        Schema::create('case_study_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_study_id')->constrained()->cascadeOnDelete();
            $table->string('linkable_type'); // 'lead', 'deal', 'email_campaign'
            $table->unsignedInteger('linkable_id'); // INT to match legacy tables
            $table->string('context')->nullable(); // e.g., "referenced_in_proposal", "sent_to_lead"
            $table->timestamps();

            $table->index(['linkable_type', 'linkable_id']);
            $table->unique(['case_study_id', 'linkable_type', 'linkable_id'], 'cs_link_unique');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('case_study_links');
        Schema::dropIfExists('case_study_tag_pivot');
        Schema::dropIfExists('case_study_tags');
        Schema::dropIfExists('case_study_media');
        Schema::dropIfExists('case_studies');
    }
};
