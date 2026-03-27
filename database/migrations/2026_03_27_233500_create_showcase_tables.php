<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('showcase_collections', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id');
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->string('title');
            $table->string('industry')->nullable();
            $table->string('client_name')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->index('account_id');
            $table->index('industry');
        });

        Schema::create('showcase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('showcase_collections')->cascadeOnDelete();
            $table->string('url', 500);
            $table->string('title');
            $table->string('screenshot_url', 500)->nullable();
            $table->string('industry')->nullable();
            $table->json('analysis')->nullable();
            $table->string('source')->default('manual'); // manual / ai_discovered
            $table->boolean('is_own_project')->default(false);
            $table->timestamps();

            $table->index('collection_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('showcase_items');
        Schema::dropIfExists('showcase_collections');
    }
};
