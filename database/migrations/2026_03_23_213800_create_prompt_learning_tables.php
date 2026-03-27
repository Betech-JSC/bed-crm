<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('level', 30)->default('beginner');
            $table->string('icon', 50)->default('pi pi-sparkles');
            $table->string('color', 30)->default('#8b5cf6');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('prompt_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('prompt_categories')->cascadeOnDelete();
            $table->string('title');
            $table->text('content')->nullable();
            $table->json('examples')->nullable();
            $table->json('tips')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('prompt_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('prompt_lessons')->cascadeOnDelete();
            $table->string('title');
            $table->text('instruction')->nullable();
            $table->text('sample_prompt')->nullable();
            $table->text('expected_output')->nullable();
            $table->string('difficulty', 20)->default('easy');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('prompt_exercise_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_id')->constrained('prompt_exercises')->cascadeOnDelete();
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->text('user_prompt');
            $table->tinyInteger('rating')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_exercise_attempts');
        Schema::dropIfExists('prompt_exercises');
        Schema::dropIfExists('prompt_lessons');
        Schema::dropIfExists('prompt_categories');
    }
};
