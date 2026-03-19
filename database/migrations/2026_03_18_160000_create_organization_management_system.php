<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Organization Management System
     *
     * Architecture:
     * 1. Departments — Company → Department hierarchy
     * 2. Teams — Department → Team grouping
     * 3. Org Positions — Role definitions per department
     * 4. Org Structure Snapshots — Versioning
     * 5. Objectives — Cascading OKR (Company → Dept → Team → Individual)
     * 6. Approval Workflows — Multi-step approval flows
     * 7. Enhanced employee_profiles — link to dept/team
     *
     * NOTE: All Schema::create calls are guarded with hasTable() to make
     * this migration idempotent (safe to re-run if it previously failed partially).
     */
    public function up(): void
    {
        // ═══════════════════════════════════════════
        // 1. DEPARTMENTS
        // ═══════════════════════════════════════════
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('account_id');
                $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('name');
                $table->string('code', 20)->nullable();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('head_user_id')->nullable();
                $table->string('color', 7)->default('#3B82F6');
                $table->string('icon', 50)->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->decimal('budget_monthly', 15, 2)->default(0);
                $table->decimal('budget_yearly', 15, 2)->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('parent_id')->references('id')->on('departments')->nullOnDelete();
                $table->index(['account_id', 'is_active']);
            });
        }

        // ═══════════════════════════════════════════
        // 2. TEAMS
        // ═══════════════════════════════════════════
        if (!Schema::hasTable('teams')) {
            Schema::create('teams', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('account_id');
                $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
                $table->foreignId('department_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('leader_user_id')->nullable();
                $table->string('color', 7)->default('#10B981');
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->integer('capacity')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['account_id', 'department_id']);
            });
        }

        // ═══════════════════════════════════════════
        // 3. ORG POSITIONS (Role Definitions)
        // ═══════════════════════════════════════════
        if (!Schema::hasTable('org_positions')) {
            Schema::create('org_positions', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('account_id');
                $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->string('title');
                $table->string('level', 20)->default('staff');
                $table->text('description')->nullable();
                $table->json('responsibilities')->nullable();
                $table->json('required_skills')->nullable();
                $table->decimal('salary_range_min', 15, 2)->nullable();
                $table->decimal('salary_range_max', 15, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // ═══════════════════════════════════════════
        // 4. ENHANCE EMPLOYEE_PROFILES
        // ═══════════════════════════════════════════
        if (!Schema::hasColumn('employee_profiles', 'department_id')) {
            Schema::table('employee_profiles', function (Blueprint $table) {
                $table->unsignedBigInteger('department_id')->nullable()->after('user_id');
                $table->unsignedBigInteger('team_id')->nullable()->after('department_id');
                $table->unsignedBigInteger('org_position_id')->nullable()->after('team_id');
                $table->unsignedBigInteger('reports_to_user_id')->nullable()->after('org_position_id');
                $table->string('employee_code', 20)->nullable()->after('id');
                $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern'])->default('full_time')->after('position');
                $table->enum('status', ['active', 'on_leave', 'probation', 'terminated'])->default('active')->after('employment_type');
                $table->date('termination_date')->nullable()->after('hire_date');
                $table->json('skills')->nullable();
                $table->json('certifications')->nullable();

                $table->index('department_id');
                $table->index('team_id');
                $table->index('reports_to_user_id');
            });
        }

        // ═══════════════════════════════════════════
        // 5. ORG STRUCTURE SNAPSHOTS (Versioning)
        // ═══════════════════════════════════════════
        if (!Schema::hasTable('org_structure_snapshots')) {
            Schema::create('org_structure_snapshots', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('account_id');
                $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->json('snapshot_data');
                $table->unsignedInteger('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->timestamp('snapshot_date');
                $table->timestamps();
            });
        }

        // ═══════════════════════════════════════════
        // 6. CASCADING OBJECTIVES (OKR System)
        // ═══════════════════════════════════════════
        if (!Schema::hasTable('org_objectives')) {
            Schema::create('org_objectives', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('account_id');
                $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->enum('level', ['company', 'department', 'team', 'individual']);
                $table->unsignedBigInteger('department_id')->nullable();
                $table->unsignedBigInteger('team_id')->nullable();
                $table->unsignedBigInteger('owner_user_id')->nullable();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('period', 20)->default('quarterly');
                $table->string('period_label', 20)->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->decimal('progress', 5, 2)->default(0);
                $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
                $table->enum('health', ['on_track', 'at_risk', 'behind', 'completed'])->default('on_track');
                $table->integer('weight')->default(100);
                $table->unsignedInteger('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('parent_id')->references('id')->on('org_objectives')->nullOnDelete();
                $table->index(['account_id', 'level', 'status']);
                $table->index(['department_id', 'period_label']);
                $table->index(['owner_user_id', 'status']);
            });
        }

        // Key Results for each objective
        if (!Schema::hasTable('org_key_results')) {
            Schema::create('org_key_results', function (Blueprint $table) {
                $table->id();
                $table->foreignId('org_objective_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('metric_type', 20)->default('number');
                $table->decimal('start_value', 15, 2)->default(0);
                $table->decimal('target_value', 15, 2)->default(0);
                $table->decimal('current_value', 15, 2)->default(0);
                $table->decimal('progress', 5, 2)->default(0);
                $table->string('unit', 30)->nullable();
                $table->integer('weight')->default(100);
                $table->enum('status', ['not_started', 'in_progress', 'completed', 'cancelled'])->default('not_started');
                $table->unsignedBigInteger('owner_user_id')->nullable();
                $table->json('milestones')->nullable();
                $table->json('check_ins')->nullable();
                $table->timestamps();
            });
        }

        // ═══════════════════════════════════════════
        // 7. APPROVAL WORKFLOWS
        // ═══════════════════════════════════════════
        if (!Schema::hasTable('approval_workflows')) {
            Schema::create('approval_workflows', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('account_id');
                $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('entity_type')->nullable();
                $table->json('conditions')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // Steps in an approval workflow
        if (!Schema::hasTable('approval_workflow_steps')) {
            Schema::create('approval_workflow_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('approval_workflow_id')->constrained()->cascadeOnDelete();
                $table->integer('step_order');
                $table->string('name');
                $table->enum('approver_type', ['user', 'role', 'department_head', 'manager', 'ceo']);
                $table->unsignedBigInteger('approver_user_id')->nullable();
                $table->unsignedBigInteger('approver_role_id')->nullable();
                $table->boolean('can_skip')->default(false);
                $table->integer('timeout_hours')->nullable();
                $table->text('instructions')->nullable();
                $table->timestamps();
            });
        }

        // Individual approval requests (replaces old simple approval_requests table)
        Schema::dropIfExists('approval_request_steps');
        Schema::dropIfExists('approval_requests');
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id');
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreignId('approval_workflow_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('entity_data')->nullable();
            $table->integer('current_step')->default(1);
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedInteger('requested_by');
            $table->foreign('requested_by')->references('id')->on('users')->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'status']);
            $table->index(['entity_type', 'entity_id']);
        });

        // Individual step approvals
        if (!Schema::hasTable('approval_request_steps')) {
            Schema::create('approval_request_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('approval_request_id')->constrained()->cascadeOnDelete();
                $table->foreignId('approval_workflow_step_id')->constrained()->cascadeOnDelete();
                $table->integer('step_order');
                $table->unsignedBigInteger('approver_user_id')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected', 'skipped'])->default('pending');
                $table->text('comment')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();

                $table->index(['approval_request_id', 'status']);
            });
        }

        // ═══════════════════════════════════════════
        // 8. ORG COST TRACKING
        // ═══════════════════════════════════════════
        if (!Schema::hasTable('org_cost_entries')) {
            Schema::create('org_cost_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('account_id');
                $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
                $table->unsignedBigInteger('department_id')->nullable();
                $table->unsignedBigInteger('team_id')->nullable();
                $table->string('category');
                $table->string('description');
                $table->decimal('amount', 15, 2);
                $table->string('period_label', 20);
                $table->date('entry_date');
                $table->unsignedInteger('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['account_id', 'department_id', 'period_label']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('org_cost_entries');
        Schema::dropIfExists('approval_request_steps');
        Schema::dropIfExists('approval_requests');
        Schema::dropIfExists('approval_workflow_steps');
        Schema::dropIfExists('approval_workflows');
        Schema::dropIfExists('org_key_results');
        Schema::dropIfExists('org_objectives');
        Schema::dropIfExists('org_structure_snapshots');
        Schema::dropIfExists('org_positions');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('departments');

        if (Schema::hasColumn('employee_profiles', 'department_id')) {
            Schema::table('employee_profiles', function (Blueprint $table) {
                $table->dropColumn([
                    'department_id', 'team_id', 'org_position_id',
                    'reports_to_user_id', 'employee_code',
                    'employment_type', 'status', 'termination_date',
                    'skills', 'certifications',
                ]);
            });
        }
    }
};
