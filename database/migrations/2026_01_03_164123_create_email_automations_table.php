<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_automations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->index();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('trigger_type', 50); // lead_created, contact_created, deal_won, date_based, etc.
            $table->json('trigger_config')->nullable(); // Trigger-specific configuration
            $table->string('status', 50)->default('draft'); // draft, active, paused, completed
            $table->integer('contacts_processed')->default(0);
            $table->integer('emails_sent')->default(0);
            $table->integer('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_automations');
    }
};
