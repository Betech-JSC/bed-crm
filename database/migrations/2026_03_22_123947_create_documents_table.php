<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id')->index();
            $table->string('title');
            $table->enum('type', ['record', 'template'])->default('template'); // biên bản / biểu mẫu
            $table->string('category')->nullable(); // VD: Biên bản bàn giao, Biên bản nghiệm thu, Mẫu hợp đồng...
            $table->text('description')->nullable();
            $table->longText('content')->nullable(); // Rich-text content
            $table->string('file_path')->nullable(); // Attached file
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->string('version')->default('1.0');
            $table->json('tags')->nullable();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->integer('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['account_id', 'type']);
            $table->index(['account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
