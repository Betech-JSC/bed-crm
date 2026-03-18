<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Notification Preferences per User ──
        if (!Schema::hasTable('notification_preferences')) {
            Schema::create('notification_preferences', function (Blueprint $table) {
                $table->id();
                $table->integer('account_id')->index();
                $table->integer('user_id')->index();
                $table->string('event_type', 50);
                $table->boolean('in_app')->default(true);
                $table->boolean('email')->default(true);
                $table->timestamps();
                $table->unique(['user_id', 'event_type']);
            });
        }

        // ── Notifications (in-app + audit) ──
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->integer('account_id')->index();
                $table->integer('user_id')->index();
                $table->string('event_type', 50)->index();
                $table->string('title');
                $table->text('body')->nullable();
                $table->string('icon', 50)->nullable();
                $table->string('severity', 20)->default('info');
                $table->string('link')->nullable();
                $table->string('linkable_type', 50)->nullable();
                $table->unsignedBigInteger('linkable_id')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->json('data')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'read_at']);
                $table->index('created_at');
            });
        }

        // ── Notification Log (email sends, retries, failures) ──
        if (!Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function (Blueprint $table) {
                $table->id();
                $table->integer('account_id')->index();
                $table->unsignedBigInteger('notification_id')->nullable()->index();
                $table->string('channel', 20);
                $table->string('event_type', 50);
                $table->string('recipient_email')->nullable();
                $table->integer('recipient_user_id')->nullable()->index();
                $table->string('status', 20)->default('pending');
                $table->text('error_message')->nullable();
                $table->unsignedTinyInteger('attempt')->default(1);
                $table->unsignedTinyInteger('max_attempts')->default(3);
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('next_retry_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index('status');
                $table->index('next_retry_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notification_preferences');
    }
};
