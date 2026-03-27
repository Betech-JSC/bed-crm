<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Outbound campaigns
        Schema::create('outbound_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('lead_id');
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->string('status')->default('active'); // active, paused, completed, cancelled
            $table->integer('current_step')->default(0);
            $table->integer('lead_score')->default(0);
            $table->json('score_breakdown')->nullable();

            // Tracking
            $table->boolean('email_opened')->default(false);
            $table->boolean('link_clicked')->default(false);
            $table->boolean('replied')->default(false);
            $table->timestamp('first_email_sent_at')->nullable();
            $table->timestamp('last_action_at')->nullable();
            $table->timestamp('next_action_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'next_action_at']);
            $table->index(['lead_id', 'status']);
        });

        // Activity logs for outbound
        Schema::create('outbound_campaign_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('outbound_campaign_id');
            $table->unsignedInteger('lead_id');
            $table->foreign('outbound_campaign_id')->references('id')->on('outbound_campaigns')->cascadeOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->string('action'); // email_sent, zalo_sent, email_opened, link_clicked, replied, score_updated
            $table->string('step_name')->nullable(); // intro, follow_up_1, case_study, final_offer
            $table->string('channel')->nullable(); // email, zalo
            $table->string('subject')->nullable();
            $table->text('content_preview')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['outbound_campaign_id', 'action']);
            $table->index('lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_campaign_logs');
        Schema::dropIfExists('outbound_campaigns');
    }
};
