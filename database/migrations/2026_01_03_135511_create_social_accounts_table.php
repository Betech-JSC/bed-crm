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
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->integer('account_id')->index();
            $table->string('platform', 50); // facebook, linkedin, twitter, instagram, etc.
            $table->string('platform_account_id', 255); // External platform account ID
            $table->string('name', 200); // Display name
            $table->string('username', 200)->nullable(); // Username/handle
            $table->text('access_token')->nullable(); // Encrypted access token
            $table->text('refresh_token')->nullable(); // Encrypted refresh token
            $table->timestamp('token_expires_at')->nullable();
            $table->json('platform_metadata')->nullable(); // Platform-specific data
            $table->boolean('is_active')->default(true);
            $table->boolean('is_connected')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['account_id', 'platform', 'is_active']);
            $table->unique(['account_id', 'platform', 'platform_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
