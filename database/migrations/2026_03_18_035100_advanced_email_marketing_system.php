<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Advanced Email Marketing System - Replaces basic email module
     * 
     * Architecture:
     * 1. Segments (replaces EmailLists) - Dynamic + static audience targeting
     * 2. Templates (enhanced) - Personalization engine with blocks
     * 3. Campaigns (enhanced) - A/B testing, scheduling, revenue attribution
     * 4. Automations (enhanced) - Visual workflow with branching logic
     * 5. Behavior Tracking - Page views, email engagement, CRM events
     * 6. Revenue Attribution - First/last touch, multi-touch models
     */
    public function up(): void
    {
        // ═══════════════════════════════════════════
        // 1. SEGMENTS (replaces email_lists)
        // ═══════════════════════════════════════════
        Schema::create('email_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['static', 'dynamic'])->default('dynamic');
            $table->json('rules')->nullable(); // Dynamic rules engine
            $table->integer('contacts_count')->default(0);
            $table->timestamp('last_computed_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['account_id', 'type']);
        });

        // Static segment memberships
        Schema::create('email_segment_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_segment_id')->constrained()->cascadeOnDelete();
            $table->string('contact_type'); // lead, contact, customer
            $table->unsignedBigInteger('contact_id');
            $table->string('email');
            $table->string('source')->default('manual'); // manual, import, automation, api
            $table->json('tags')->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
            $table->index(['email_segment_id', 'contact_type', 'contact_id']);
            $table->index('email');
        });

        // ═══════════════════════════════════════════
        // 2. ENHANCED TEMPLATES
        // ═══════════════════════════════════════════
        Schema::table('email_templates', function (Blueprint $table) {
            $table->string('category')->nullable()->after('type'); // welcome, promo, newsletter, transactional
            $table->json('personalization_tokens')->nullable()->after('variables'); // {{first_name}}, {{company}}
            $table->json('content_blocks')->nullable()->after('body_text'); // Modular content blocks
            $table->string('preview_text')->nullable()->after('subject'); // Preview text for inbox
            $table->json('metadata')->nullable()->after('is_active'); // thumbnail, tags, etc.
            $table->integer('usage_count')->default(0)->after('is_active');
            $table->decimal('avg_open_rate', 5, 2)->nullable()->after('usage_count');
            $table->decimal('avg_click_rate', 5, 2)->nullable()->after('avg_open_rate');
        });

        // ═══════════════════════════════════════════
        // 3. ENHANCED CAMPAIGNS WITH A/B TESTING
        // ═══════════════════════════════════════════
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->foreignId('email_segment_id')->nullable()->after('email_list_id')
                ->constrained('email_segments')->nullOnDelete();
            $table->string('campaign_type')->default('regular')->after('status'); // regular, ab_test, rss, automated
            $table->json('ab_test_config')->nullable()->after('campaign_type');
            /*
             * ab_test_config: {
             *   "variable": "subject|content|send_time",
             *   "variants": [
             *     {"id": "A", "subject": "...", "body_html": "...", "weight": 50},
             *     {"id": "B", "subject": "...", "body_html": "...", "weight": 50}
             *   ],
             *   "winner_criteria": "open_rate|click_rate|revenue",
             *   "test_duration_hours": 4,
             *   "test_sample_percent": 20,
             *   "winner_id": null
             * }
             */
            $table->string('winning_variant_id')->nullable()->after('campaign_type');
            $table->json('send_config')->nullable()->after('scheduled_at');
            /*
             * send_config: {
             *   "timezone": "Asia/Ho_Chi_Minh",
             *   "send_in_recipient_timezone": false,
             *   "throttle_per_hour": null,
             *   "exclude_segments": [1, 2]
             * }
             */
            $table->string('from_name')->nullable()->after('subject');
            $table->string('from_email')->nullable()->after('from_name');
            $table->string('reply_to')->nullable()->after('from_email');
            $table->string('preview_text')->nullable()->after('reply_to');
            $table->decimal('revenue_generated', 15, 2)->default(0)->after('click_rate');
            $table->integer('conversions_count')->default(0)->after('revenue_generated');
            $table->decimal('revenue_per_email', 10, 2)->nullable()->after('conversions_count');
            $table->integer('spam_reports')->default(0)->after('unsubscribed_count');
            $table->json('tags')->nullable();
        });

        // A/B Test Variant Results
        Schema::create('email_campaign_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_campaign_id')->constrained()->cascadeOnDelete();
            $table->string('variant_id'); // A, B, C...
            $table->string('subject')->nullable();
            $table->text('body_html')->nullable();
            $table->string('from_name')->nullable();
            $table->string('preview_text')->nullable();
            $table->integer('recipients_count')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('opened_count')->default(0);
            $table->integer('clicked_count')->default(0);
            $table->integer('bounced_count')->default(0);
            $table->decimal('open_rate', 5, 2)->nullable();
            $table->decimal('click_rate', 5, 2)->nullable();
            $table->decimal('revenue', 15, 2)->default(0);
            $table->boolean('is_winner')->default(false);
            $table->timestamps();
            $table->index(['email_campaign_id', 'variant_id']);
        });

        // ═══════════════════════════════════════════
        // 4. ADVANCED AUTOMATIONS (Visual Workflow)
        // ═══════════════════════════════════════════
        Schema::table('email_automations', function (Blueprint $table) {
            $table->json('entry_conditions')->nullable()->after('trigger_config');
            /*
             * entry_conditions: {
             *   "segment_ids": [1, 2],
             *   "filters": [
             *     {"field": "deal_value", "operator": ">", "value": 1000000}
             *   ],
             *   "frequency": "once|multiple",
             *   "re_entry_delay_days": 30
             * }
             */
            $table->json('exit_conditions')->nullable()->after('entry_conditions');
            $table->json('goal_config')->nullable()->after('exit_conditions');
            /*
             * goal_config: {
             *   "type": "deal_won|page_visit|email_reply",
             *   "value": "...",
             *   "remove_on_goal": true
             * }
             */
            $table->integer('active_contacts')->default(0)->after('emails_sent');
            $table->integer('completed_contacts')->default(0)->after('active_contacts');
            $table->integer('goal_conversions')->default(0)->after('completed_contacts');
            $table->decimal('revenue_generated', 15, 2)->default(0)->after('goal_conversions');
        });

        // Enhanced Automation Steps (supports branching)
        Schema::table('email_automation_steps', function (Blueprint $table) {
            $table->string('step_name')->nullable()->after('step_order');
            // New step types: split_test, webhook, update_field, move_to_segment, score_contact
            $table->unsignedBigInteger('yes_next_step_id')->nullable()->after('conditions');
            $table->unsignedBigInteger('no_next_step_id')->nullable()->after('yes_next_step_id');
            $table->integer('wait_hours')->nullable()->after('wait_days');
            $table->string('wait_until_time')->nullable()->after('wait_hours'); // e.g., "09:00"
            $table->json('performance')->nullable(); // {sent, opened, clicked} per step
        });

        // Contacts currently inside an automation
        Schema::create('email_automation_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_automation_id')->constrained()->cascadeOnDelete();
            $table->string('contact_type');
            $table->unsignedBigInteger('contact_id');
            $table->string('email');
            $table->unsignedBigInteger('current_step_id')->nullable();
            $table->enum('status', ['active', 'paused', 'completed', 'exited', 'goal_met'])->default('active');
            $table->timestamp('entered_at');
            $table->timestamp('next_action_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('step_history')->nullable(); // [{step_id, action, timestamp}]
            $table->timestamps();
            $table->index(['email_automation_id', 'status']);
            $table->index(['contact_type', 'contact_id']);
            $table->index('next_action_at');
        });

        // ═══════════════════════════════════════════
        // 5. BEHAVIOR TRACKING
        // ═══════════════════════════════════════════
        Schema::create('email_contact_behaviors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('contact_type');
            $table->unsignedBigInteger('contact_id');
            $table->string('email');
            $table->string('event_type'); // page_view, email_open, email_click, form_submit, deal_created, deal_won
            $table->string('event_source')->nullable(); // campaign_123, automation_456, website
            $table->json('event_data')->nullable(); // {url, campaign_id, link_url, etc}
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_type')->nullable(); // desktop, mobile, tablet
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['account_id', 'contact_type', 'contact_id']);
            $table->index(['event_type', 'occurred_at']);
            $table->index('email');
        });

        // Contact engagement scoring
        Schema::create('email_contact_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('contact_type');
            $table->unsignedBigInteger('contact_id');
            $table->string('email');
            $table->integer('engagement_score')->default(0); // 0-100
            $table->integer('emails_received')->default(0);
            $table->integer('emails_opened')->default(0);
            $table->integer('emails_clicked')->default(0);
            $table->integer('pages_viewed')->default(0);
            $table->timestamp('last_engaged_at')->nullable();
            $table->timestamp('last_emailed_at')->nullable();
            $table->string('engagement_level')->default('cold'); // hot, warm, cold, inactive
            $table->json('interests')->nullable(); // inferred from click/view patterns
            $table->json('preferred_send_time')->nullable(); // {day: "tuesday", hour: 10}
            $table->timestamps();
            $table->unique(['account_id', 'contact_type', 'contact_id']);
            $table->index('engagement_level');
        });

        // ═══════════════════════════════════════════
        // 6. ENHANCED EMAIL SENDS (add variant tracking)
        // ═══════════════════════════════════════════
        Schema::table('email_sends', function (Blueprint $table) {
            $table->string('variant_id')->nullable()->after('sendable_id'); // A/B test variant
            $table->unsignedBigInteger('automation_step_id')->nullable()->after('variant_id');
            $table->timestamp('unsubscribed_at')->nullable()->after('clicked_at');
            $table->integer('revenue_attributed')->default(0)->after('click_count');
        });

        // ═══════════════════════════════════════════
        // 7. REVENUE ATTRIBUTION
        // ═══════════════════════════════════════════
        Schema::create('email_revenue_attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('email_automation_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('email_send_id')->nullable();
            $table->string('contact_type');
            $table->unsignedBigInteger('contact_id');
            $table->string('deal_type')->nullable(); // deal
            $table->unsignedBigInteger('deal_id')->nullable();
            $table->decimal('deal_value', 15, 2)->default(0);
            $table->string('attribution_model')->default('last_touch'); // first_touch, last_touch, linear, time_decay
            $table->decimal('attributed_value', 15, 2)->default(0);
            $table->decimal('attribution_weight', 5, 4)->default(1.0); // 0.0000 to 1.0000
            $table->integer('touchpoints_count')->default(1);
            $table->integer('days_to_conversion')->nullable();
            $table->timestamp('conversion_date');
            $table->timestamps();
            $table->index(['account_id', 'email_campaign_id']);
            $table->index(['account_id', 'email_automation_id']);
            $table->index(['contact_type', 'contact_id']);
        });

        // ═══════════════════════════════════════════
        // 8. SUPPRESSION LIST (compliance)
        // ═══════════════════════════════════════════
        Schema::create('email_suppressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('email')->index();
            $table->string('reason'); // unsubscribed, bounced, spam_report, manual
            $table->string('source')->nullable(); // campaign_123, automation_456
            $table->timestamps();
            $table->unique(['account_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_suppressions');
        Schema::dropIfExists('email_revenue_attributions');
        Schema::dropIfExists('email_contact_scores');
        Schema::dropIfExists('email_contact_behaviors');
        Schema::dropIfExists('email_automation_enrollments');
        Schema::dropIfExists('email_campaign_variants');
        Schema::dropIfExists('email_segment_contacts');
        Schema::dropIfExists('email_segments');

        // Revert column additions
        Schema::table('email_sends', function (Blueprint $table) {
            $table->dropColumn(['variant_id', 'automation_step_id', 'unsubscribed_at', 'revenue_attributed']);
        });
        Schema::table('email_automation_steps', function (Blueprint $table) {
            $table->dropColumn(['step_name', 'yes_next_step_id', 'no_next_step_id', 'wait_hours', 'wait_until_time', 'performance']);
        });
        Schema::table('email_automations', function (Blueprint $table) {
            $table->dropColumn(['entry_conditions', 'exit_conditions', 'goal_config', 'active_contacts', 'completed_contacts', 'goal_conversions', 'revenue_generated']);
        });
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->dropColumn(['email_segment_id', 'campaign_type', 'ab_test_config', 'winning_variant_id', 'send_config', 'from_name', 'from_email', 'reply_to', 'preview_text', 'revenue_generated', 'conversions_count', 'revenue_per_email', 'spam_reports', 'tags']);
        });
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn(['category', 'personalization_tokens', 'content_blocks', 'preview_text', 'metadata', 'usage_count', 'avg_open_rate', 'avg_click_rate']);
        });
    }
};
