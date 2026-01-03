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
        Schema::create('chat_widgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id')->index();
            $table->string('name', 255);
            $table->string('widget_key', 64)->unique(); // Unique key for embedding
            $table->text('welcome_message')->nullable();
            $table->text('system_prompt')->nullable(); // AI system prompt
            $table->string('primary_color', 7)->default('#ef6820'); // Brand color
            $table->string('position', 20)->default('bottom-right'); // bottom-right, bottom-left, etc.
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_create_leads')->default(true); // Auto-create leads from conversations
            $table->boolean('collect_email')->default(true);
            $table->boolean('collect_phone')->default(false);
            $table->json('allowed_domains')->nullable(); // Domains where widget can be embedded
            $table->json('settings')->nullable(); // Additional widget settings
            $table->string('ai_model', 50)->default('gpt-4o-mini'); // OpenAI model
            $table->decimal('temperature', 3, 2)->default(0.7); // AI temperature
            $table->integer('max_tokens')->default(500);
            $table->integer('rate_limit_per_hour')->default(100); // Rate limit per visitor
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->index(['account_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_widgets');
    }
};
