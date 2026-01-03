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
        Schema::create('chat_widget_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id')->index();
            $table->unsignedBigInteger('widget_id')->index();
            $table->string('name', 255);
            $table->text('content'); // Document content
            $table->string('file_path')->nullable(); // Original file path if uploaded
            $table->string('file_type', 50)->nullable(); // pdf, txt, docx, etc.
            $table->integer('chunk_index')->default(0); // For chunked documents
            $table->text('embedding')->nullable(); // JSON array of embedding vector
            $table->integer('token_count')->nullable(); // Token count for this chunk
            $table->json('metadata')->nullable(); // Additional metadata
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('widget_id')->references('id')->on('chat_widgets')->onDelete('cascade');
            $table->index(['widget_id', 'is_active']);
            $table->index(['widget_id', 'chunk_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_widget_documents');
    }
};
