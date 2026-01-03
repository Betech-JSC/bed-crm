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
        Schema::create('workflows', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->index();
            $table->string('name', 200);
            $table->text('description')->nullable();

            // Trigger configuration (JSON)
            // e.g., {"event": "lead.created", "conditions": {"score": ">=", "value": 80}}
            $table->json('trigger')->nullable();

            // Actions configuration (JSON array)
            // e.g., [{"type": "assign_user", "user_id": 1}, {"type": "send_email", "template": "welcome"}]
            $table->json('actions');

            $table->boolean('is_active')->default(true);
            $table->integer('execution_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
