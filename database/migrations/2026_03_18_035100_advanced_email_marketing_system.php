<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Advanced Email Marketing System - Replaces basic email module
     *
     * All Schema::create/table calls are guarded to make this migration
     * idempotent (safe to re-run if it previously failed partially).
     */
    public function up(): void
    {
        // ═══════════════════════════════════════════
        // 1. SEGMENTS (replaces email_lists)
        // ═══════════════════════════════════════════
        if (!Schema::hasTable('email_segments')) {
            Schema::create('email_segments', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('account_id');
                $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('type', ['static', 'dynamic'])->default('dynamic');
                $table->json('rules')->nullable();
                $table->integer('contacts_count')->default(0);
                $table->timestamp('last_computed_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['account_id', 'type']);
            });
        }

        if (!Schema::hasTable('email_segment_contacts')) {
            Schema::create('email_segment_contacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('email_segment_id')->constrained()->cascadeOnDelete();
                $table->string('contact_type');
                $table->unsignedBigInteger('contact_id');
                $table->string('email');
                $table->string('source')->default('manual');
                $table->json('tags')->nullable();
                $table->timestamp('subscribed_at')->nullable();
                $table->timestamp('unsubscribed_at')->nullable();
                $table->timestamps();
                $table->index(['email_segment_id', 'contact_type', 'contact_id'], 'seg_contact_type_id_idx');
                $table->index('email');
            });
        }

        // ═══════════════════════════════════════════
        // 2. ENHANCED TEMPLATES
        // ═══════════════════════════════════════════
        if (!Schema::hasColumn('email_templates', 'category')) {
            Schema::table('email_templates', function (Blueprint $table) {
                $table->string('category')->nullable()->after('type');
                $table->json('personalization_tokens')->nullable()->after('variables');
                $table->json('content_blocks')->nullable()->after('body_text');
                $table->string('preview_text')->nullable()->after('subject');
                $table->json('metadata')->nullable()->after('is_active');
                $table->integer('usage_count')->default(0)->after('is_active');
                $table->decimal('avg_open_rate', 5, 2)->nullable()->after('usage_count');
                $table->decimal('avg_click_rate', 5, 2)->nullable()->after('avg_open_rate');
            });
        }

        // ═══════════════════════════════════════════
        // 3. ENHANCED CAMPAIGNS WITH A/B TESTING
        // ═══════════════════════════════════════════
        if (!Schema::hasColumn('email_campaigns', 'campaign_type')) {
            Schema::table('email_campaigns', function (Blueprint $table) {
                $table->unsignedBigInteger('email_segment_id')->nullable()->after('email_list_id');
                $table->foreign('email_segment_id')->references('id')->on('email_segments')->nullOnDelete();
                $table->string('campaign_type')->default('regular')->after('status');
                $table->json('ab_test_config')->nullable()->after('campaign_type');
                $table->string('winning_variant_id')->nullable()->after('campaign_type');
                $table->json('send_config')->nullable()->after('scheduled_at');
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
        }

        if (!Schema::hasTable('email_campaign_variants')) {
            Schema::create('email_campaign_variants', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('email_campaign_id');
                $table->foreign('email_campaign_id')->references('id')->on('email_campaigns')->cascadeOnDelete();
                $table->string('variant_id');
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
        }

        // ═══════════════════════════════════════════
        // 4. ADVANCED AUTOMATIONS (Visual Workflow)
        // ═══════════════════════════════════════════
        if (!Schema::hasColumn('email_automations', 'entry_conditions')) {
            Schema::table('email_automations', function (Blueprint $table) {
                $table->json('entry_conditions')->nullable()->after('trigger_config');
                $table->json('exit_conditions')->nullable()->after('entry_conditions');
                $table->json('goal_config')->nullable()->after('exit_conditions');
                $table->integer('active_contacts')->default(0)->after('emails_sent');
                $table->integer('completed_contacts')->default(0)->after('active_contacts');
                $table->integer('goal_conversions')->default(0)->after('completed_contacts');
                $table->decimal('revenue_generated', 15, 2)->default(0)->after('goal_conversions');
            });
        }

        if (!Schema::hasColumn('email_automation_steps', 'step_name')) {
            Schema::table('email_automation_steps', function (Blueprint $table) {
                $table->string('step_name')->nullable()->after('step_order');
                $table->unsignedBigInteger('yes_next_step_id')->nullable()->after('conditions');
                $table->unsignedBigInteger('no_next_step_id')->nullable()->after('yes_next_step_id');
                $table->integer('wait_hours')->nullable()->after('wait_days');
                $table->string('wait_until_time')->nullable()->after('wait_hours');
                $table->json('performance')->nullable();
            });
        }

        if (!Schema::hasTable('email_automation_enrollments')) {
            Schema::create('email_automation_enrollments', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('email_automation_id');
                $table->foreign('email_automation_id')->references('id')->on('email_automations')->cascadeOnDelete();
                $table->string('contact_type');
                $table->unsignedBigInteger('contact_id');
                $table->string('email');
                $table->unsignedBigInteger('current_step_id')->nullable();
                $table->enum('status', ['active', 'paused', 'completed', 'exited', 'goal_met'])->default('active');
                $table->timestamp('entered_at');
                $table->timestamp('next_action_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->json('step_history')->nullable();
                $table->timestamps();
                $table->index(['email_automation_id', 'status']);
                $table->index(['contact_type', 'contact_id']);
                $table->index('next_action_at');
            });
        }

        // ═══════════════════════════════════════════
        // 5. BEHAVIOR TRACKING
        // ═══════════════════════════════════════════
        if (!Schema::hasTable('email_contact_behaviors')) {
            Schema::create('email_contact_behaviors', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('account_id');
                $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
                $table->string('contact_type');
                $table->unsignedBigInteger('contact_id');
                $table->string('email');
                $table->string('event_type');
                $table->string('event_source')->nullable();
                $table->json('event_data')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->string('device_type')->nullable();
                $table->timestamp('occurred_at');
                $table->timestamps();
                $table->index(['account_id', 'contact_type', 'contact_id'], 'behaviors_acct_type_id_idx');
                $table->index(['event_type', 'occurred_at']);
                $table->index('email');
            });
        }

        if (!Schema::hasTable('email_contact_scores')) {
            Schema::create('email_contact_scores', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('account_id');
                $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
                $table->string('contact_type');
                $table->unsignedBigInteger('contact_id');
                $table->string('email');
                $table->integer('engagement_score')->default(0);
                $table->integer('emails_received')->default(0);
                $table->integer('emails_opened')->default(0);
                $table->integer('emails_clicked')->default(0);
                $table->integer('pages_viewed')->default(0);
                $table->timestamp('last_engaged_at')->nullable();
                $table->timestamp('last_emailed_at')->nullable();
                $table->string('engagement_level')->default('cold');
                $table->json('interests')->nullable();
                $table->json('preferred_send_time')->nullable();
                $table->timestamps();
                $table->unique(['account_id', 'contact_type', 'contact_id'], 'scores_acct_type_id_unique');
                $table->index('engagement_level');
            });
        }

        // ═══════════════════════════════════════════
        // 6. ENHANCED EMAIL SENDS (add variant tracking)
        // ═══════════════════════════════════════════
        if (!Schema::hasColumn('email_sends', 'variant_id')) {
            Schema::table('email_sends', function (Blueprint $table) {
                $table->string('variant_id')->nullable()->after('sendable_id');
                $table->unsignedBigInteger('automation_step_id')->nullable()->after('variant_id');
                $table->timestamp('unsubscribed_at')->nullable()->after('clicked_at');
                $table->integer('revenue_attributed')->default(0)->after('click_count');
            });
        }

        // ═══════════════════════════════════════════
        // 7. REVENUE ATTRIBUTION
        // ═══════════════════════════════════════════
        if (!Schema::hasTable('email_revenue_attributions')) {
            Schema::create('email_revenue_attributions', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('account_id');
                $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
                $table->unsignedInteger('email_campaign_id')->nullable();
                $table->foreign('email_campaign_id')->references('id')->on('email_campaigns')->nullOnDelete();
                $table->unsignedInteger('email_automation_id')->nullable();
                $table->foreign('email_automation_id')->references('id')->on('email_automations')->nullOnDelete();
                $table->unsignedBigInteger('email_send_id')->nullable();
                $table->string('contact_type');
                $table->unsignedBigInteger('contact_id');
                $table->string('deal_type')->nullable();
                $table->unsignedBigInteger('deal_id')->nullable();
                $table->decimal('deal_value', 15, 2)->default(0);
                $table->string('attribution_model')->default('last_touch');
                $table->decimal('attributed_value', 15, 2)->default(0);
                $table->decimal('attribution_weight', 5, 4)->default(1.0);
                $table->integer('touchpoints_count')->default(1);
                $table->integer('days_to_conversion')->nullable();
                $table->timestamp('conversion_date');
                $table->timestamps();
                $table->index(['account_id', 'email_campaign_id']);
                $table->index(['account_id', 'email_automation_id']);
                $table->index(['contact_type', 'contact_id']);
            });
        }

        // ═══════════════════════════════════════════
        // 8. SUPPRESSION LIST (compliance)
        // ═══════════════════════════════════════════
        if (!Schema::hasTable('email_suppressions')) {
            Schema::create('email_suppressions', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('account_id');
                $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
                $table->string('email')->index();
                $table->string('reason');
                $table->string('source')->nullable();
                $table->timestamps();
                $table->unique(['account_id', 'email']);
            });
        }
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

        if (Schema::hasColumn('email_sends', 'variant_id')) {
            Schema::table('email_sends', function (Blueprint $table) {
                $table->dropColumn(['variant_id', 'automation_step_id', 'unsubscribed_at', 'revenue_attributed']);
            });
        }
        if (Schema::hasColumn('email_automation_steps', 'step_name')) {
            Schema::table('email_automation_steps', function (Blueprint $table) {
                $table->dropColumn(['step_name', 'yes_next_step_id', 'no_next_step_id', 'wait_hours', 'wait_until_time', 'performance']);
            });
        }
        if (Schema::hasColumn('email_automations', 'entry_conditions')) {
            Schema::table('email_automations', function (Blueprint $table) {
                $table->dropColumn(['entry_conditions', 'exit_conditions', 'goal_config', 'active_contacts', 'completed_contacts', 'goal_conversions', 'revenue_generated']);
            });
        }
        if (Schema::hasColumn('email_campaigns', 'campaign_type')) {
            Schema::table('email_campaigns', function (Blueprint $table) {
                $table->dropForeign(['email_segment_id']);
                $table->dropColumn(['email_segment_id', 'campaign_type', 'ab_test_config', 'winning_variant_id', 'send_config', 'from_name', 'from_email', 'reply_to', 'preview_text', 'revenue_generated', 'conversions_count', 'revenue_per_email', 'spam_reports', 'tags']);
            });
        }
        if (Schema::hasColumn('email_templates', 'category')) {
            Schema::table('email_templates', function (Blueprint $table) {
                $table->dropColumn(['category', 'personalization_tokens', 'content_blocks', 'preview_text', 'metadata', 'usage_count', 'avg_open_rate', 'avg_click_rate']);
            });
        }
    }
};
