<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Strategic Plans ──
        if (!Schema::hasTable('strategic_plans')) {
            Schema::create('strategic_plans', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('account_id')->index();
                $table->string('title');
                $table->text('vision')->nullable();
                $table->text('mission')->nullable();
                $table->json('values')->nullable();
                $table->date('time_horizon_start')->nullable();
                $table->date('time_horizon_end')->nullable();
                $table->string('status', 20)->default('draft'); // draft, active, archived
                $table->integer('created_by')->nullable();
                $table->timestamps();
                $table->index(['account_id', 'status']);
            });
        }

        // ── 2. Strategic Themes ──
        if (!Schema::hasTable('strategic_themes')) {
            Schema::create('strategic_themes', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('account_id')->index();
                $table->unsignedInteger('strategic_plan_id')->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('color', 20)->default('#6366f1');
                $table->string('icon', 50)->default('pi pi-flag');
                $table->unsignedTinyInteger('sort_order')->default(0);
                $table->string('status', 20)->default('active'); // active, paused, completed
                $table->unsignedTinyInteger('weight')->default(25); // 0-100
                $table->timestamps();
            });
        }

        // ── 3. Objectives (OKR) — self-referencing for cascade ──
        if (!Schema::hasTable('objectives')) {
            Schema::create('objectives', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('account_id')->index();
                $table->unsignedInteger('strategic_theme_id')->nullable()->index();
                $table->unsignedInteger('parent_id')->nullable()->index(); // cascade
                $table->string('level', 20)->default('company'); // company, team, individual
                $table->string('title');
                $table->text('description')->nullable();
                $table->integer('owner_id')->nullable()->index();
                $table->string('team', 50)->nullable();
                $table->string('period', 20)->nullable(); // Q1-2026, H1-2026, FY-2026
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->string('status', 20)->default('draft'); // draft, active, at_risk, completed, cancelled
                $table->decimal('progress', 5, 2)->default(0); // 0-100
                $table->unsignedTinyInteger('confidence')->default(70); // 0-100
                $table->unsignedTinyInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
                $table->index(['account_id', 'level', 'status']);
                $table->index(['account_id', 'period']);
            });
        }

        // ── 4. Key Results ──
        if (!Schema::hasTable('key_results')) {
            Schema::create('key_results', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('account_id')->index();
                $table->unsignedInteger('objective_id')->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('metric_type', 20)->default('number'); // number, currency, percentage, boolean
                $table->decimal('start_value', 15, 2)->default(0);
                $table->decimal('target_value', 15, 2)->default(0);
                $table->decimal('current_value', 15, 2)->default(0);
                $table->string('unit', 20)->nullable();
                // Auto-tracking from CRM
                $table->string('data_source', 30)->default('manual');
                // manual, deals_count, deals_value, leads_count, leads_qualified,
                // revenue, new_customers, churn_rate, project_completion, custom_kpi
                $table->json('data_source_config')->nullable(); // filters, scopes
                $table->unsignedInteger('kpi_definition_id')->nullable();
                $table->integer('owner_id')->nullable();
                $table->string('status', 20)->default('on_track'); // on_track, at_risk, behind, completed
                $table->unsignedTinyInteger('confidence')->default(70);
                $table->unsignedTinyInteger('weight')->default(100); // relative weight
                $table->timestamps();
            });
        }

        // ── 5. Initiatives ──
        if (!Schema::hasTable('initiatives')) {
            Schema::create('initiatives', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('account_id')->index();
                $table->unsignedInteger('objective_id')->nullable()->index();
                $table->unsignedInteger('project_id')->nullable()->index(); // link to existing projects
                $table->string('title');
                $table->text('description')->nullable();
                $table->integer('owner_id')->nullable();
                $table->string('status', 20)->default('planned'); // planned, in_progress, completed, cancelled
                $table->string('priority', 20)->default('medium'); // low, medium, high, critical
                $table->date('start_date')->nullable();
                $table->date('due_date')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->decimal('budget', 15, 2)->default(0);
                $table->decimal('actual_cost', 15, 2)->default(0);
                $table->decimal('progress', 5, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // ── 6. Initiative Tasks ──
        if (!Schema::hasTable('initiative_tasks')) {
            Schema::create('initiative_tasks', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('account_id')->index();
                $table->unsignedInteger('initiative_id')->index();
                $table->unsignedInteger('project_task_id')->nullable(); // link to existing task
                $table->string('title');
                $table->text('description')->nullable();
                $table->integer('assigned_to')->nullable();
                $table->string('status', 20)->default('todo'); // todo, in_progress, done, blocked
                $table->string('priority', 20)->default('medium');
                $table->date('due_date')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedTinyInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // ── 7. Strategic KPIs (bridge table) ──
        if (!Schema::hasTable('strategic_kpis')) {
            Schema::create('strategic_kpis', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('account_id')->index();
                $table->unsignedInteger('strategic_theme_id')->index();
                $table->unsignedInteger('kpi_definition_id')->index();
                $table->decimal('target_value', 15, 2)->nullable();
                $table->unsignedTinyInteger('weight')->default(100);
                $table->unsignedTinyInteger('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['strategic_theme_id', 'kpi_definition_id'], 'strat_kpi_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('strategic_kpis');
        Schema::dropIfExists('initiative_tasks');
        Schema::dropIfExists('initiatives');
        Schema::dropIfExists('key_results');
        Schema::dropIfExists('objectives');
        Schema::dropIfExists('strategic_themes');
        Schema::dropIfExists('strategic_plans');
    }
};
