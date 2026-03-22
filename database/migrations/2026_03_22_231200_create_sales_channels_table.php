<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_channels', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->string('name');                     // VD: "Zalo", "Facebook", "Website", "Shopee"
            $table->string('slug')->index();            // VD: "zalo", "facebook"
            $table->string('icon')->nullable();         // PrimeIcon class
            $table->string('color', 20)->nullable();    // Hex color for UI
            $table->text('description')->nullable();
            $table->json('stages');                     // [{"key":"lead_in","label":"Lead vào","color":"#3b82f6"},...]
            $table->json('default_fields')->nullable(); // Which fields to show for this channel
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('settings')->nullable();       // Channel-specific settings
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        // Add channel reference to sales_pipelines
        Schema::table('sales_pipelines', function (Blueprint $table) {
            $table->unsignedInteger('channel_id')->nullable()->after('account_id');
            $table->json('stage_history')->nullable()->after('stage');   // Track stage transitions
            $table->timestamp('stage_changed_at')->nullable()->after('stage');
            $table->decimal('win_probability', 5, 2)->nullable()->after('priority');

            $table->foreign('channel_id')->references('id')->on('sales_channels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_pipelines', function (Blueprint $table) {
            $table->dropForeign(['channel_id']);
            $table->dropColumn(['channel_id', 'stage_history', 'stage_changed_at', 'win_probability']);
        });

        Schema::dropIfExists('sales_channels');
    }
};
