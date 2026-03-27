<?php

use App\Http\Controllers\CeoRoadmapController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('ceo-roadmap', [CeoRoadmapController::class, 'index'])->name('ceo-roadmap.index');
    Route::post('ceo-roadmap/seed', [CeoRoadmapController::class, 'seedDefaults'])->name('ceo-roadmap.seed');

    // Phases
    Route::post('ceo-roadmap/phases', [CeoRoadmapController::class, 'storePhase'])->name('ceo-roadmap.phases.store');
    Route::put('ceo-roadmap/phases/{phase}', [CeoRoadmapController::class, 'updatePhase'])->name('ceo-roadmap.phases.update');
    Route::delete('ceo-roadmap/phases/{phase}', [CeoRoadmapController::class, 'deletePhase'])->name('ceo-roadmap.phases.delete');

    // Milestones
    Route::post('ceo-roadmap/milestones', [CeoRoadmapController::class, 'storeMilestone'])->name('ceo-roadmap.milestones.store');
    Route::put('ceo-roadmap/milestones/{milestone}', [CeoRoadmapController::class, 'updateMilestone'])->name('ceo-roadmap.milestones.update');
    Route::delete('ceo-roadmap/milestones/{milestone}', [CeoRoadmapController::class, 'deleteMilestone'])->name('ceo-roadmap.milestones.delete');

    // Tests
    Route::post('ceo-roadmap/tests', [CeoRoadmapController::class, 'storeTest'])->name('ceo-roadmap.tests.store');
    Route::put('ceo-roadmap/tests/{test}', [CeoRoadmapController::class, 'updateTest'])->name('ceo-roadmap.tests.update');
    Route::delete('ceo-roadmap/tests/{test}', [CeoRoadmapController::class, 'deleteTest'])->name('ceo-roadmap.tests.delete');

    // Test attempts
    Route::post('ceo-roadmap/tests/{test}/submit', [CeoRoadmapController::class, 'submitTest'])->name('ceo-roadmap.tests.submit');
});
