<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_pipelines', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->index();
            $table->integer('lead_id')->nullable()->index();
            $table->integer('deal_id')->nullable()->index();

            // Contact info
            $table->string('company_name', 255);
            $table->string('contact_name', 255);
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('website_url', 500)->nullable();

            // Pipeline stage
            $table->string('stage', 50)->default('audit');
            $table->integer('assigned_to')->nullable()->index();

            // Social connect
            $table->string('social_channel', 50)->nullable();
            $table->string('social_account', 255)->nullable();

            // Audit data (JSON)
            $table->json('audit_data')->nullable();

            // Proposal / Solution
            $table->text('proposal_summary')->nullable();
            $table->string('proposal_file_path', 500)->nullable();

            // Discussion notes
            $table->text('discussion_notes')->nullable();

            // Quote
            $table->decimal('quote_amount', 15, 2)->nullable();
            $table->date('quote_valid_until')->nullable();
            $table->string('quote_file_path', 500)->nullable();
            $table->text('quote_notes')->nullable();

            // Close
            $table->date('close_date')->nullable();
            $table->text('close_notes')->nullable();
            $table->string('lost_reason', 500)->nullable();

            // Metadata
            $table->string('priority', 20)->default('warm');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_pipelines');
    }
};
