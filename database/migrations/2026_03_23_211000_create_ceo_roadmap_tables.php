<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ceo_roadmap_phases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon', 50)->default('pi pi-star');
            $table->string('color', 30)->default('#10b981');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ceo_roadmap_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phase_id')->constrained('ceo_roadmap_phases')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('skills')->nullable();
            $table->json('resources')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ceo_roadmap_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_id')->constrained('ceo_roadmap_milestones')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('questions');
            $table->integer('passing_score')->default(70);
            $table->integer('time_limit_minutes')->default(15);
            $table->timestamps();
        });

        Schema::create('ceo_roadmap_test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('ceo_roadmap_tests')->cascadeOnDelete();
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->json('answers')->nullable();
            $table->integer('score')->default(0);
            $table->boolean('passed')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ceo_roadmap_test_attempts');
        Schema::dropIfExists('ceo_roadmap_tests');
        Schema::dropIfExists('ceo_roadmap_milestones');
        Schema::dropIfExists('ceo_roadmap_phases');
    }
};
