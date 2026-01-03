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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id')->index();
            $table->unsignedBigInteger('conversation_id')->index();
            $table->enum('role', ['user', 'assistant', 'system'])->default('user');
            $table->text('content');
            $table->json('ai_metadata')->nullable(); // AI response metadata (tokens, model, etc.)
            $table->integer('tokens_used')->nullable(); // Tokens consumed
            $table->decimal('cost', 10, 6)->nullable(); // Cost in USD
            $table->integer('response_time_ms')->nullable(); // Response time in milliseconds
            $table->enum('status', ['pending', 'sent', 'failed'])->default('sent');
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Additional message data
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('conversation_id')->references('id')->on('chat_conversations')->onDelete('cascade');
            $table->index(['conversation_id', 'created_at']);
            $table->index(['account_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
