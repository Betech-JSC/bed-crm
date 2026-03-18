<?php

use App\Http\Controllers\StrategyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // ── Strategy Dashboard ──
    Route::get('strategy', [StrategyController::class, 'dashboard'])->name('strategy.dashboard');

    // ── Strategic Plan ──
    Route::post('strategy/plans', [StrategyController::class, 'storePlan'])->name('strategy.plans.store');
    Route::put('strategy/plans/{plan}', [StrategyController::class, 'updatePlan'])->name('strategy.plans.update');

    // ── Strategic Themes ──
    Route::post('strategy/themes', [StrategyController::class, 'storeTheme'])->name('strategy.themes.store');
    Route::put('strategy/themes/{theme}', [StrategyController::class, 'updateTheme'])->name('strategy.themes.update');

    // ── OKR Page ──
    Route::get('okrs', [StrategyController::class, 'okrs'])->name('strategy.okrs');

    // ── Objectives ──
    Route::post('okrs/objectives', [StrategyController::class, 'storeObjective'])->name('okrs.objectives.store');
    Route::put('okrs/objectives/{objective}', [StrategyController::class, 'updateObjective'])->name('okrs.objectives.update');
    Route::delete('okrs/objectives/{objective}', [StrategyController::class, 'deleteObjective'])->name('okrs.objectives.delete');
    Route::post('okrs/objectives/{objective}/cascade', [StrategyController::class, 'cascadeObjective'])->name('okrs.objectives.cascade');

    // ── Key Results ──
    Route::post('okrs/objectives/{objective}/key-results', [StrategyController::class, 'storeKeyResult'])->name('okrs.kr.store');
    Route::post('okrs/key-results/{keyResult}/check-in', [StrategyController::class, 'checkIn'])->name('okrs.kr.checkin');

    // ── Initiatives ──
    Route::post('initiatives', [StrategyController::class, 'storeInitiative'])->name('initiatives.store');
    Route::put('initiatives/{initiative}', [StrategyController::class, 'updateInitiative'])->name('initiatives.update');

    // ── API ──
    Route::get('okrs/api/tree', [StrategyController::class, 'apiTree'])->name('okrs.api.tree');
    Route::get('strategy/api/health', [StrategyController::class, 'apiHealth'])->name('strategy.api.health');
    Route::post('strategy/api/refresh', [StrategyController::class, 'apiRefresh'])->name('strategy.api.refresh');
    Route::get('strategy/api/alignment', [StrategyController::class, 'apiAlignment'])->name('strategy.api.alignment');
});
