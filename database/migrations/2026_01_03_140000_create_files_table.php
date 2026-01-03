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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->integer('account_id')->index();
            $table->integer('uploaded_by')->index(); // User who uploaded
            $table->string('name', 255); // Original filename
            $table->string('filename', 255); // Stored filename (with hash)
            $table->string('path', 500); // Storage path
            $table->string('disk', 50)->default('local'); // Storage disk
            $table->string('mime_type', 100); // MIME type
            $table->string('extension', 20); // File extension
            $table->bigInteger('size'); // File size in bytes
            $table->string('category', 100)->nullable(); // Category: document, image, video, audio, other
            $table->string('type', 50)->nullable(); // Type: proposal, contract, invoice, avatar, attachment, etc.
            $table->string('related_type', 100)->nullable(); // Polymorphic relation type
            $table->integer('related_id')->nullable(); // Polymorphic relation ID
            $table->text('description')->nullable(); // File description
            $table->json('metadata')->nullable(); // Additional metadata (dimensions, duration, etc.)
            $table->boolean('is_public')->default(false); // Public access
            $table->string('access_level', 50)->default('private'); // private, account, public
            $table->integer('download_count')->default(0); // Download tracking
            $table->timestamp('last_downloaded_at')->nullable();
            $table->string('checksum', 64)->nullable(); // SHA-256 hash for integrity
            $table->boolean('is_virus_scanned')->default(false); // Virus scan status
            $table->boolean('is_safe')->default(true); // Safety status
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['account_id', 'category']);
            $table->index(['account_id', 'type']);
            $table->index(['account_id', 'uploaded_by']);
            $table->index(['related_type', 'related_id']);
            $table->index(['account_id', 'is_public', 'access_level']);
            $table->index(['account_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};

