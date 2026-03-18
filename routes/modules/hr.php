<?php

use App\Http\Controllers\TeamPerformanceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('hr', [TeamPerformanceController::class, 'index'])->name('hr.dashboard');

    // Employee Profiles
    Route::get('hr/employees', [TeamPerformanceController::class, 'employees'])->name('hr.employees');
    Route::post('hr/employees', [TeamPerformanceController::class, 'storeEmployee'])->name('hr.employees.store');
    Route::put('hr/employees/{employee}', [TeamPerformanceController::class, 'updateEmployee'])->name('hr.employees.update');
    Route::delete('hr/employees/{employee}', [TeamPerformanceController::class, 'destroyEmployee'])->name('hr.employees.destroy');

    // Employee Detail + KPI Values + Reviews
    Route::get('hr/employees/{employee}', [TeamPerformanceController::class, 'employeeDetail'])->name('hr.employees.show');
    Route::post('hr/employees/{employee}/kpi-values', [TeamPerformanceController::class, 'storeKpiValue'])->name('hr.employees.kpi-values.store');
    Route::post('hr/employees/{employee}/reviews', [TeamPerformanceController::class, 'storeReview'])->name('hr.employees.reviews.store');
    Route::get('hr/employees/{employee}/calculate-score', [TeamPerformanceController::class, 'calculateScore'])->name('hr.employees.calculate-score');

    // KPI Definitions
    Route::get('hr/kpi-definitions', [TeamPerformanceController::class, 'kpiDefinitions'])->name('hr.kpi-definitions');
    Route::post('hr/kpi-definitions', [TeamPerformanceController::class, 'storeKpiDefinition'])->name('hr.kpi-definitions.store');
    Route::put('hr/kpi-definitions/{kpiDefinition}', [TeamPerformanceController::class, 'updateKpiDefinition'])->name('hr.kpi-definitions.update');
    Route::delete('hr/kpi-definitions/{kpiDefinition}', [TeamPerformanceController::class, 'destroyKpiDefinition'])->name('hr.kpi-definitions.destroy');
});
