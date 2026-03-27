<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. AI Knowledge Bases ──
        Schema::create('ai_knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 30)->default('general'); // general, sales, support, hr, product, custom
            $table->json('settings')->nullable(); // chunk_size, overlap, model_embed
            $table->string('status', 20)->default('building'); // building, ready, training, error
            $table->json('stats')->nullable(); // {documents, chunks, vectors, size_bytes}
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // ── 2. AI Knowledge Documents ──
        Schema::create('ai_knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('knowledge_base_id')->index();
            $table->string('title');
            $table->string('source_type', 30)->default('text'); // upload, crm_sync, url, text, api
            $table->string('source_ref', 500)->nullable(); // table name, URL, file path
            $table->longText('content')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->json('chunks')->nullable(); // [{text, embedding_id, tokens}]
            $table->json('metadata')->nullable(); // file info, sync config
            $table->string('status', 20)->default('pending'); // pending, processing, indexed, error
            $table->timestamp('last_synced_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('knowledge_base_id')->references('id')->on('ai_knowledge_bases')->cascadeOnDelete();
        });

        // ── 3. AI Training Sets ──
        Schema::create('ai_training_sets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->string('name');
            $table->string('agent_type', 50)->default('custom'); // sales, support, content, analytics, hr, custom
            $table->text('description')->nullable();
            $table->string('format', 30)->default('qa_pairs'); // qa_pairs, conversations, instructions, examples
            $table->json('data')->nullable(); // training data array
            $table->unsignedInteger('item_count')->default(0);
            $table->decimal('quality_score', 3, 1)->nullable(); // 0-10
            $table->string('status', 20)->default('draft'); // draft, validated, active
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // ── 4. AI Agents ──
        Schema::create('ai_agents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->string('name');
            $table->string('slug', 100)->unique();
            $table->string('type', 30)->default('custom'); // sales, support, content, analytics, hr, custom
            $table->text('description')->nullable();
            $table->string('avatar', 500)->nullable();
            $table->longText('system_prompt')->nullable();
            $table->json('knowledge_base_ids')->nullable();
            $table->json('training_set_ids')->nullable();
            $table->json('tools')->nullable(); // enabled tools/capabilities
            $table->json('model_config')->nullable(); // {provider, model, temperature, max_tokens}
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('total_conversations')->default(0);
            $table->unsignedInteger('total_messages')->default(0);
            $table->decimal('avg_satisfaction', 3, 2)->nullable(); // 0-5
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // ── 5. AI Agent Conversations ──
        Schema::create('ai_agent_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('title')->nullable();
            $table->json('context')->nullable(); // {deal_id, contact_id, ticket_id}
            $table->json('messages')->nullable(); // [{role, content, sources, timestamp}]
            $table->unsignedInteger('message_count')->default(0);
            $table->unsignedTinyInteger('satisfaction_rating')->nullable(); // 1-5
            $table->text('feedback')->nullable();
            $table->unsignedInteger('tokens_used')->default(0);
            $table->timestamps();

            $table->foreign('agent_id')->references('id')->on('ai_agents')->cascadeOnDelete();
        });

        // ── 6. AI Data Sync Logs ──
        Schema::create('ai_data_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->unsignedBigInteger('knowledge_base_id')->nullable();
            $table->string('source_type', 50);
            $table->string('source_ref', 255)->nullable();
            $table->string('action', 20); // sync, index, embed, cleanup
            $table->unsignedInteger('records_processed')->default(0);
            $table->unsignedInteger('records_failed')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->string('status', 20)->default('running'); // running, completed, failed
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_data_sync_logs');
        Schema::dropIfExists('ai_agent_conversations');
        Schema::dropIfExists('ai_agents');
        Schema::dropIfExists('ai_training_sets');
        Schema::dropIfExists('ai_knowledge_documents');
        Schema::dropIfExists('ai_knowledge_bases');
    }
};
