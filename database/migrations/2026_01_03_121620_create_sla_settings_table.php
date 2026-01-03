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
        Schema::create('sla_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('account_id')->index();
            $table->string('name', 100); // e.g., "Standard Response SLA"
            $table->text('description')->nullable();
            
            // SLA Thresholds (in minutes)
            $table->integer('first_response_threshold')->default(15); // 15 minutes
            $table->integer('warning_threshold')->default(10); // 10 minutes (warning before breach)
            $table->integer('critical_threshold')->nullable(); // Optional critical threshold
            
            // Business hours (optional - for future enhancement)
            $table->json('business_hours')->nullable(); // e.g., {"monday": {"start": "09:00", "end": "17:00"}}
            $table->boolean('include_weekends')->default(false);
            
            // Notification settings
            $table->boolean('notify_assigned_user')->default(true);
            $table->boolean('notify_managers')->default(true);
            $table->json('notify_user_ids')->nullable(); // Additional users to notify
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // One default SLA per account
            
            $table->timestamps();
            
            $table->index(['account_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sla_settings');
    }
};
