<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_platform_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id');
            $table->string('platform'); // facebook, instagram, linkedin, twitter
            $table->string('client_id')->nullable();
            $table->text('client_secret')->nullable(); // encrypted
            $table->string('redirect_uri')->nullable();
            $table->json('scopes')->nullable();
            $table->json('extra_config')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['account_id', 'platform']);
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_platform_settings');
    }
};
