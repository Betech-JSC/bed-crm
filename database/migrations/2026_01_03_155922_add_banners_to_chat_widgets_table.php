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
        Schema::table('chat_widgets', function (Blueprint $table) {
            $table->json('banners')->nullable()->after('settings');
            $table->boolean('show_banners')->default(true)->after('banners');
            $table->integer('banner_rotation_seconds')->default(5)->after('show_banners');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_widgets', function (Blueprint $table) {
            $table->dropColumn(['banners', 'show_banners', 'banner_rotation_seconds']);
        });
    }
};
