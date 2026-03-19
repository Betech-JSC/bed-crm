<?php

use App\Http\Controllers\OrgStructureController;
use App\Http\Controllers\OrgObjectivesController;
use App\Http\Controllers\ApprovalsController;
use App\Http\Controllers\Api\OrgReportingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // ═══════════════════════════════════════════
    // Organization Structure
    // ═══════════════════════════════════════════
    Route::get('/org-structure', [OrgStructureController::class, 'index'])->name('org-structure');
    Route::post('/org-structure/reorder', [OrgStructureController::class, 'reorder'])->name('org-structure.reorder');
    Route::post('/org-structure/snapshot', [OrgStructureController::class, 'takeSnapshot'])->name('org-structure.snapshot');
    Route::get('/org-structure/snapshots', [OrgStructureController::class, 'snapshots'])->name('org-structure.snapshots');

    // Departments
    Route::post('/departments', [OrgStructureController::class, 'storeDepartment'])->name('departments.store');
    Route::put('/departments/{department}', [OrgStructureController::class, 'updateDepartment'])->name('departments.update');
    Route::delete('/departments/{department}', [OrgStructureController::class, 'destroyDepartment'])->name('departments.destroy');

    // Teams
    Route::post('/teams', [OrgStructureController::class, 'storeTeam'])->name('teams.store');
    Route::put('/teams/{team}', [OrgStructureController::class, 'updateTeam'])->name('teams.update');
    Route::delete('/teams/{team}', [OrgStructureController::class, 'destroyTeam'])->name('teams.destroy');

    // Employee assignment
    Route::put('/employees/{employee}/assign', [OrgStructureController::class, 'assignEmployee'])->name('employees.assign');

    // ═══════════════════════════════════════════
    // OKR / Objectives
    // ═══════════════════════════════════════════
    Route::get('/org-objectives', [OrgObjectivesController::class, 'index'])->name('org-objectives');
    Route::post('/org-objectives', [OrgObjectivesController::class, 'store'])->name('org-objectives.store');
    Route::put('/org-objectives/{orgObjective}', [OrgObjectivesController::class, 'update'])->name('org-objectives.update');
    Route::delete('/org-objectives/{orgObjective}', [OrgObjectivesController::class, 'destroy'])->name('org-objectives.destroy');

    // Key Results
    Route::post('/org-key-results', [OrgObjectivesController::class, 'storeKeyResult'])->name('org-key-results.store');
    Route::post('/org-key-results/{keyResult}/check-in', [OrgObjectivesController::class, 'checkIn'])->name('org-key-results.check-in');

    // ═══════════════════════════════════════════
    // Approvals
    // ═══════════════════════════════════════════
    Route::get('/approvals', [ApprovalsController::class, 'index'])->name('approvals');
    Route::post('/approvals/{stepId}/approve', [ApprovalsController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{requestId}/reject', [ApprovalsController::class, 'reject'])->name('approvals.reject');

    // Approval Workflows (admin)
    Route::post('/approval-workflows', [ApprovalsController::class, 'storeWorkflow'])->name('approval-workflows.store');
    Route::delete('/approval-workflows/{workflow}', [ApprovalsController::class, 'destroyWorkflow'])->name('approval-workflows.destroy');

    // ═══════════════════════════════════════════
    // Org Reporting API
    // ═══════════════════════════════════════════
    Route::prefix('api/org-reporting')->group(function () {
        Route::get('/revenue-by-department', [OrgReportingController::class, 'revenueByDepartment']);
        Route::get('/revenue-by-team', [OrgReportingController::class, 'revenueByTeam']);
        Route::get('/cost-by-department', [OrgReportingController::class, 'costByDepartment']);
        Route::get('/cost-by-category', [OrgReportingController::class, 'costByCategory']);
        Route::get('/profit-by-team', [OrgReportingController::class, 'profitByTeam']);
        Route::get('/employee-ranking', [OrgReportingController::class, 'employeeRanking']);
    });
});
