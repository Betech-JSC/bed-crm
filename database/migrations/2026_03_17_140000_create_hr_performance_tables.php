<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Employee profiles (extends users)
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('user_id');
            $table->string('department', 50)->nullable();
            $table->string('position', 80)->nullable();
            $table->date('hire_date')->nullable();
            $table->decimal('base_salary', 14, 2)->default(0);
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->unsignedSmallInteger('target_hours_monthly')->default(160);
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['account_id', 'user_id']);
        });

        // KPI definitions
        Schema::create('kpi_definitions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit', 30)->default('number'); // number, currency, percentage, hours
            $table->string('period', 20)->default('monthly'); // monthly, quarterly, yearly
            $table->string('category', 30)->default('sales'); // sales, support, productivity, custom
            $table->decimal('target_value', 14, 2)->default(0);
            $table->boolean('higher_is_better')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
        });

        // KPI values per employee per period
        Schema::create('kpi_values', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('employee_profile_id');
            $table->unsignedInteger('kpi_definition_id');
            $table->decimal('value', 14, 2)->default(0);
            $table->decimal('target', 14, 2)->default(0);
            $table->string('period_label', 20); // e.g. "2026-03", "2026-Q1"
            $table->date('period_start');
            $table->date('period_end');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles')->cascadeOnDelete();
            $table->foreign('kpi_definition_id')->references('id')->on('kpi_definitions')->cascadeOnDelete();
            $table->index(['employee_profile_id', 'period_start']);
            $table->index(['kpi_definition_id', 'period_start']);
        });

        // Performance reviews / snapshots
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('employee_profile_id');
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->string('period_label', 20);
            $table->date('period_start');
            $table->date('period_end');

            // Scores (0-100)
            $table->unsignedTinyInteger('overall_score')->default(0);
            $table->json('score_breakdown')->nullable(); // { kpi_achievement, revenue_contribution, utilization, quality }
            $table->string('rating', 20)->nullable(); // exceptional, exceeds, meets, below, unsatisfactory

            $table->decimal('revenue_generated', 14, 2)->default(0);
            $table->decimal('deals_closed_value', 14, 2)->default(0);
            $table->unsignedSmallInteger('deals_closed_count')->default(0);
            $table->decimal('hours_logged', 8, 2)->default(0);

            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['employee_profile_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('kpi_values');
        Schema::dropIfExists('kpi_definitions');
        Schema::dropIfExists('employee_profiles');
    }
};
