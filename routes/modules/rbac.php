<?php

use App\Http\Controllers\RBACController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // RBAC Management page
    Route::get('settings/roles', [RBACController::class, 'index'])->name('rbac.index');

    // Role CRUD
    Route::post('settings/roles', [RBACController::class, 'createRole'])->name('rbac.roles.create');
    Route::put('settings/roles/{role}', [RBACController::class, 'updateRole'])->name('rbac.roles.update');
    Route::delete('settings/roles/{role}', [RBACController::class, 'deleteRole'])->name('rbac.roles.delete');

    // Permission sync
    Route::post('settings/roles/{role}/permissions', [RBACController::class, 'syncPermissions'])->name('rbac.permissions.sync');

    // User-role assignment
    Route::post('settings/users/{user}/roles', [RBACController::class, 'assignRoles'])->name('rbac.users.roles');
    Route::delete('settings/users/{user}/roles', [RBACController::class, 'removeRole'])->name('rbac.users.roles.remove');

    // Seed defaults (admin-only)
    Route::post('settings/roles/seed', [RBACController::class, 'seed'])->name('rbac.seed');
});

// ═══════════════════════════════════════════════════
//  EXAMPLE: Using permission middleware on routes
// ═══════════════════════════════════════════════════
//
//  Route::middleware(['auth', 'permission:leads.view'])->group(function () {
//      Route::get('/leads', [LeadController::class, 'index']);
//  });
//
//  Route::middleware(['auth', 'permission:deals.create'])->group(function () {
//      Route::post('/deals', [DealController::class, 'store']);
//  });
//
//  Route::middleware(['auth', 'role:admin,sales_manager'])->group(function () {
//      Route::get('/reports', [ReportController::class, 'index']);
//  });
//
//  // Multiple permissions (user needs ANY one):
//  Route::middleware(['auth', 'permission:leads.edit,leads.delete'])->group(function () {
//      Route::put('/leads/{lead}', [LeadController::class, 'update']);
//  });
