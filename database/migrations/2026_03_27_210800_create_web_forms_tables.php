<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Web Forms ──
        Schema::create('web_forms', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('created_by');

            $table->string('name');                          // Internal name
            $table->string('slug')->unique();                // For embed URL
            $table->text('description')->nullable();

            // Design settings
            $table->string('form_type')->default('inline');  // inline, popup, slide_in, floating_bar
            $table->string('status')->default('active');     // active, paused, archived
            $table->json('style_settings')->nullable();      // colors, font, button text, etc.

            // Popup trigger settings
            $table->json('trigger_settings')->nullable();    // {type, delay_seconds, scroll_percent, exit_intent}

            // After submit
            $table->string('success_action')->default('message'); // message, redirect, hide
            $table->text('success_message')->nullable();
            $table->string('redirect_url')->nullable();

            // Notifications
            $table->boolean('email_notify')->default(true);
            $table->string('notify_emails')->nullable();     // comma-separated

            // Lead creation
            $table->boolean('auto_create_lead')->default(true);
            $table->string('lead_source')->nullable();       // source tag for created leads
            $table->string('lead_status')->default('new');

            // Stats (denormalized for speed)
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('submissions_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        // ── Form Fields ──
        Schema::create('web_form_fields', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('web_form_id');

            $table->string('field_type');              // text, email, phone, textarea, select, checkbox, radio, hidden, number, date
            $table->string('label');                   // "Your Name", "Email", etc.
            $table->string('name');                    // field_name for data key
            $table->string('placeholder')->nullable();
            $table->text('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->json('options')->nullable();        // For select/radio/checkbox: [{label, value}]
            $table->string('default_value')->nullable();
            $table->string('validation_rule')->nullable(); // regex or preset
            $table->string('crm_mapping')->nullable();  // maps to: lead.email, lead.phone, lead.company, etc.
            $table->integer('width')->default(100);     // percentage 50 or 100
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->foreign('web_form_id')->references('id')->on('web_forms')->onDelete('cascade');
        });

        // ── Form Submissions ──
        Schema::create('web_form_submissions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('web_form_id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('lead_id')->nullable();  // Auto-created lead

            $table->json('data');                       // submitted field values
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referrer_url')->nullable();

            // UTM tracking
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();

            $table->string('page_url')->nullable();     // Page where form was submitted
            $table->string('status')->default('new');   // new, contacted, converted, spam
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->foreign('web_form_id')->references('id')->on('web_forms')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
            $table->index(['account_id', 'web_form_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_form_submissions');
        Schema::dropIfExists('web_form_fields');
        Schema::dropIfExists('web_forms');
    }
};
