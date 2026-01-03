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
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->integer('account_id')->index();
            $table->integer('deal_id')->nullable()->index(); // Link to deal
            $table->integer('version')->default(1); // Version number
            $table->integer('parent_id')->nullable()->index(); // Parent proposal for versioning
            
            // Proposal details
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2)->nullable(); // Proposal amount
            $table->date('valid_until')->nullable(); // Proposal expiry date
            
            // File storage
            $table->string('file_path')->nullable(); // Path to uploaded file
            $table->string('file_name')->nullable(); // Original file name
            $table->string('file_size')->nullable(); // File size in bytes
            $table->string('file_type')->nullable(); // MIME type
            
            // Status tracking
            $table->string('status', 20)->default('draft'); // draft, sent, viewed, accepted, rejected
            $table->timestamp('sent_at')->nullable(); // When proposal was sent
            $table->timestamp('viewed_at')->nullable(); // When proposal was first viewed
            $table->timestamp('accepted_at')->nullable(); // When proposal was accepted
            $table->timestamp('rejected_at')->nullable(); // When proposal was rejected
            $table->text('rejection_reason')->nullable(); // Reason for rejection
            
            // Tracking
            $table->integer('view_count')->default(0); // Number of times viewed
            $table->timestamp('last_viewed_at')->nullable(); // Last view timestamp
            
            // Metadata
            $table->integer('created_by')->index(); // User who created the proposal
            $table->integer('sent_by')->nullable(); // User who sent the proposal
            $table->json('metadata')->nullable(); // Additional metadata (e.g., tracking pixels, IP addresses)
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['account_id', 'deal_id']);
            $table->index(['account_id', 'status']);
            $table->index(['parent_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
