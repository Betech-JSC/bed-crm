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
        Schema::create('sales_playbooks', function (Blueprint $table) {
            $table->id();
            $table->integer('account_id')->index();
            $table->string('name', 200);
            $table->text('description')->nullable();
            
            // Matching criteria
            $table->json('industries')->nullable(); // Array of industries this playbook applies to
            $table->json('deal_stages')->nullable(); // Array of deal stages (prospecting, qualification, etc.)
            $table->json('pain_points')->nullable(); // Array of pain points/keywords
            
            // Playbook content
            $table->text('talking_points')->nullable(); // Suggested talking points
            $table->text('email_template_subject')->nullable(); // Email subject template
            $table->text('email_template_body')->nullable(); // Email body template
            $table->json('recommended_documents')->nullable(); // Array of document names/URLs to send
            
            // Additional content
            $table->text('objections_handling')->nullable(); // Common objections and responses
            $table->text('next_steps')->nullable(); // Suggested next steps
            $table->json('tags')->nullable(); // Tags for categorization
            
            // Priority and ordering
            $table->integer('priority')->default(0); // Higher priority = shown first
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['account_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_playbooks');
    }
};
