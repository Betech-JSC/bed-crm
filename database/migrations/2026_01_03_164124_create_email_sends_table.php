<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_sends', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->index();
            $table->string('sendable_type', 50); // 'campaign', 'automation'
            $table->integer('sendable_id')->index(); // ID of campaign or automation
            $table->integer('email_template_id')->nullable()->index();
            $table->string('contact_type', 50); // 'contact', 'lead'
            $table->integer('contact_id')->nullable()->index();
            $table->string('email', 255)->index();
            $table->string('name', 255)->nullable();
            $table->string('subject', 500);
            $table->text('body_html')->nullable();
            $table->text('body_text')->nullable();
            $table->string('status', 50)->default('pending'); // pending, sent, delivered, bounced, failed
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->integer('open_count')->default(0);
            $table->integer('click_count')->default(0);
            $table->string('bounce_type', 50)->nullable(); // hard, soft
            $table->text('bounce_reason')->nullable();
            $table->string('message_id', 255)->nullable()->unique(); // For tracking opens/clicks
            $table->timestamps();

            $table->index(['sendable_type', 'sendable_id']);
            $table->index(['account_id', 'status']);
            $table->index(['email', 'status']);
            $table->index(['contact_type', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_sends');
    }
};
