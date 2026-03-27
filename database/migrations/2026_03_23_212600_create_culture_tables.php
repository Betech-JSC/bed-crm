<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('culture_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon', 50)->default('pi pi-heart');
            $table->string('color', 30)->default('#ec4899');
            $table->json('behaviors')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('culture_initiatives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 50)->default('general');
            $table->string('status', 30)->default('planned');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('assigned_to')->nullable();
            $table->string('impact', 30)->default('medium');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('culture_surveys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('questions');
            $table->string('status', 30)->default('draft');
            $table->boolean('anonymous')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('culture_survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('culture_surveys')->cascadeOnDelete();
            $table->unsignedInteger('user_id')->nullable();
            $table->json('answers');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('culture_survey_responses');
        Schema::dropIfExists('culture_surveys');
        Schema::dropIfExists('culture_initiatives');
        Schema::dropIfExists('culture_values');
    }
};
