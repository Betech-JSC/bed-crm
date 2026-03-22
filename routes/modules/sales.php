<?php

use App\Http\Controllers\ActivitiesController;

use App\Http\Controllers\WorkflowsController;
use App\Http\Controllers\SLASettingsController;

use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SalesIntelligenceController;
use App\Http\Controllers\SalesTargetsController;
use App\Http\Controllers\LeadAssignmentRulesController;
use App\Http\Controllers\SalesPipelineController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth')->group(function () {
    // ── Sales Pipeline (Quy trình bán hàng) ──
    Route::get('sales-pipeline', [SalesPipelineController::class, 'index'])->name('sales-pipeline');
    Route::get('sales-pipeline/create', [SalesPipelineController::class, 'create'])->name('sales-pipeline.create');
    Route::post('sales-pipeline', [SalesPipelineController::class, 'store'])->name('sales-pipeline.store');
    Route::get('sales-pipeline/{pipeline}/edit', [SalesPipelineController::class, 'edit'])->name('sales-pipeline.edit');
    Route::put('sales-pipeline/{pipeline}', [SalesPipelineController::class, 'update'])->name('sales-pipeline.update');
    Route::delete('sales-pipeline/{pipeline}', [SalesPipelineController::class, 'destroy'])->name('sales-pipeline.destroy');
    Route::put('sales-pipeline/{pipeline}/restore', [SalesPipelineController::class, 'restore'])->name('sales-pipeline.restore');
    Route::patch('sales-pipeline/{pipeline}/stage', [SalesPipelineController::class, 'updateStage'])->name('sales-pipeline.update-stage');
    Route::post('sales-pipeline/{pipeline}/audit', [SalesPipelineController::class, 'storeAudit'])->name('sales-pipeline.audit');
    Route::post('sales-pipeline/{pipeline}/ai-analyze', [SalesPipelineController::class, 'aiAnalyze'])->name('sales-pipeline.ai-analyze');
    Route::post('sales-pipeline/{pipeline}/ai-proposal', [SalesPipelineController::class, 'aiProposal'])->name('sales-pipeline.ai-proposal');
    Route::post('sales-pipeline/{pipeline}/quote', [SalesPipelineController::class, 'storeQuote'])->name('sales-pipeline.quote');
    Route::post('sales-pipeline/{pipeline}/close-won', [SalesPipelineController::class, 'closeWon'])->name('sales-pipeline.close-won');
    Route::post('sales-pipeline/{pipeline}/close-lost', [SalesPipelineController::class, 'closeLost'])->name('sales-pipeline.close-lost');

    // ── Sales Channels (Kênh bán hàng) ──
    Route::post('sales-channels', [SalesPipelineController::class, 'storeChannel'])->name('sales-channels.store');
    Route::put('sales-channels/{channel}', [SalesPipelineController::class, 'updateChannel'])->name('sales-channels.update');
    Route::delete('sales-channels/{channel}', [SalesPipelineController::class, 'destroyChannel'])->name('sales-channels.destroy');

    // Activities
    Route::post('activities', [ActivitiesController::class, 'store'])->name('activities.store');
    Route::put('activities/{activity}', [ActivitiesController::class, 'update'])->name('activities.update');
    Route::delete('activities/{activity}', [ActivitiesController::class, 'destroy'])->name('activities.destroy');

    // Reports
    Route::get('reports', [ReportsController::class, 'index'])->name('reports');



    // Workflows
    Route::get('workflows', [WorkflowsController::class, 'index'])->name('workflows');
    Route::get('workflows/create', [WorkflowsController::class, 'create'])->name('workflows.create');
    Route::post('workflows', [WorkflowsController::class, 'store'])->name('workflows.store');
    Route::get('workflows/{workflow}/edit', [WorkflowsController::class, 'edit'])->name('workflows.edit');
    Route::put('workflows/{workflow}', [WorkflowsController::class, 'update'])->name('workflows.update');
    Route::delete('workflows/{workflow}', [WorkflowsController::class, 'destroy'])->name('workflows.destroy');

    // SLA Settings
    Route::get('sla-settings', [SLASettingsController::class, 'index'])->name('sla-settings');
    Route::get('sla-settings/create', [SLASettingsController::class, 'create'])->name('sla-settings.create');
    Route::post('sla-settings', [SLASettingsController::class, 'store'])->name('sla-settings.store');
    Route::get('sla-settings/{slaSetting}/edit', [SLASettingsController::class, 'edit'])->name('sla-settings.edit');
    Route::put('sla-settings/{slaSetting}', [SLASettingsController::class, 'update'])->name('sla-settings.update');
    Route::delete('sla-settings/{slaSetting}', [SLASettingsController::class, 'destroy'])->name('sla-settings.destroy');



    // ── Sales Intelligence APIs ──
    Route::prefix('sales-intelligence')->name('sales-intel.')->group(function () {

        // Revenue forecasting
        Route::get('forecast', [SalesIntelligenceController::class, 'forecast'])->name('forecast');
        Route::get('forecast/{userId}', [SalesIntelligenceController::class, 'forecastForRep'])->name('forecast.rep');

        // Sales velocity
        Route::get('velocity', [SalesIntelligenceController::class, 'velocity'])->name('velocity');
        Route::get('velocity/{userId}', [SalesIntelligenceController::class, 'velocityForRep'])->name('velocity.rep');

        // Deal health & risk
        Route::get('risks', [SalesIntelligenceController::class, 'risks'])->name('risks');
        Route::post('deals/{deal}/health', [SalesIntelligenceController::class, 'dealHealth'])->name('deal.health');
        Route::post('deals/{deal}/probability', [SalesIntelligenceController::class, 'updateProbability'])->name('deal.probability');

        // Batch refresh (cron/manual)
        Route::post('refresh', [SalesIntelligenceController::class, 'refreshAll'])->name('refresh');

        // Rep KPIs
        Route::get('rep-kpis', [SalesIntelligenceController::class, 'myKPIs'])->name('rep.kpis');
        Route::get('rep-kpis/{userId}', [SalesIntelligenceController::class, 'repKPIs'])->name('rep.kpis.user');
    });

    // Sales Targets (quota management)
    Route::get('sales-targets', [SalesTargetsController::class, 'index'])->name('sales-targets.index');
    Route::post('sales-targets', [SalesTargetsController::class, 'store'])->name('sales-targets.store');
    Route::put('sales-targets/{target}', [SalesTargetsController::class, 'update'])->name('sales-targets.update');

    // Lead Assignment Rules
    Route::get('lead-assignment-rules', [LeadAssignmentRulesController::class, 'index'])->name('lead-assignment.index');
    Route::post('lead-assignment-rules', [LeadAssignmentRulesController::class, 'store'])->name('lead-assignment.store');
    Route::put('lead-assignment-rules/{rule}', [LeadAssignmentRulesController::class, 'update'])->name('lead-assignment.update');
    Route::delete('lead-assignment-rules/{rule}', [LeadAssignmentRulesController::class, 'destroy'])->name('lead-assignment.destroy');
    // ── AI Business Advisor ──
    Route::prefix('ai-advisor')->name('ai-advisor.')->group(function () {
        Route::get('briefing', [\App\Http\Controllers\AiAdvisorController::class, 'briefing'])->name('briefing');
        Route::get('cashflow', [\App\Http\Controllers\AiAdvisorController::class, 'cashflowProjection'])->name('cashflow');
        Route::post('simulate', [\App\Http\Controllers\AiAdvisorController::class, 'simulate'])->name('simulate');
    });
});

