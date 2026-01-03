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
        Schema::create('content_templates', function (Blueprint $table) {
            $table->id();
            $table->integer('account_id')->index();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable(); // blog, social, email, etc.
            $table->text('prompt_template'); // AI prompt template with placeholders
            $table->json('variables')->nullable(); // Available variables for the template
            $table->json('settings')->nullable(); // AI model settings, tone, style, etc.
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['account_id', 'category', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_templates');
    }
};
