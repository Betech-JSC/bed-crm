<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id')->index();
            $table->unsignedBigInteger('widget_id')->index();
            $table->string('visitor_id', 64)->index(); // Unique visitor identifier
            $table->string('session_id', 64)->index(); // Session identifier
            $table->string('visitor_name', 255)->nullable();
            $table->string('visitor_email', 255)->nullable()->index();
            $table->string('visitor_phone', 50)->nullable();
            $table->string('visitor_ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('referrer_url', 500)->nullable();
            $table->string('page_url', 500)->nullable();
            $table->unsignedBigInteger('lead_id')->nullable()->index(); // Linked CRM lead
            $table->unsignedBigInteger('contact_id')->nullable()->index(); // Linked CRM contact
            $table->enum('status', ['active', 'closed', 'archived'])->default('active');
            $table->timestamp('last_message_at')->nullable();
            $table->integer('message_count')->default(0);
            $table->json('metadata')->nullable(); // Additional conversation data
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('widget_id')->references('id')->on('chat_widgets')->onDelete('cascade');
            $table->index(['account_id', 'status', 'created_at']);
            $table->index(['visitor_id', 'widget_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};
