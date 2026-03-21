<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id');
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->string('slug', 50)->comment('gemini, openai, claude, deepseek, etc.');
            $table->string('display_name', 100);
            $table->string('api_key')->nullable();
            $table->string('model', 100)->nullable()->comment('Active model for this provider');
            $table->json('available_models')->nullable()->comment('List of models user can choose');
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->json('config')->nullable()->comment('Extra config: base_url, org_id, etc.');
            $table->string('status', 20)->default('unconfigured')->comment('unconfigured, connected, error');
            $table->timestamp('last_tested_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('total_requests')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->timestamps();

            $table->unique(['account_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};
