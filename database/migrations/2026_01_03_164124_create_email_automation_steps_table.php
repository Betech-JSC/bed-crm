<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_automation_steps', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('email_automation_id')->index();
            $table->integer('step_order')->default(0);
            $table->string('step_type', 50); // send_email, wait, condition, tag, etc.
            $table->json('step_config')->nullable(); // Step-specific configuration
            $table->integer('email_template_id')->nullable()->index();
            $table->integer('wait_days')->nullable(); // For wait steps
            $table->json('conditions')->nullable(); // For condition steps
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['email_automation_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_automation_steps');
    }
};
