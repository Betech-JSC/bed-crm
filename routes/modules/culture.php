<?php

use App\Http\Controllers\CultureController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('culture', [CultureController::class, 'index'])->name('culture.index');
    Route::post('culture/seed', [CultureController::class, 'seedDefaults'])->name('culture.seed');

    // Values
    Route::post('culture/values', [CultureController::class, 'storeValue'])->name('culture.values.store');
    Route::put('culture/values/{value}', [CultureController::class, 'updateValue'])->name('culture.values.update');
    Route::delete('culture/values/{value}', [CultureController::class, 'deleteValue'])->name('culture.values.delete');

    // Initiatives
    Route::post('culture/initiatives', [CultureController::class, 'storeInitiative'])->name('culture.initiatives.store');
    Route::put('culture/initiatives/{initiative}', [CultureController::class, 'updateInitiative'])->name('culture.initiatives.update');
    Route::delete('culture/initiatives/{initiative}', [CultureController::class, 'deleteInitiative'])->name('culture.initiatives.delete');

    // Surveys
    Route::post('culture/surveys', [CultureController::class, 'storeSurvey'])->name('culture.surveys.store');
    Route::put('culture/surveys/{survey}', [CultureController::class, 'updateSurvey'])->name('culture.surveys.update');
    Route::delete('culture/surveys/{survey}', [CultureController::class, 'deleteSurvey'])->name('culture.surveys.delete');
    Route::post('culture/surveys/{survey}/submit', [CultureController::class, 'submitSurvey'])->name('culture.surveys.submit');
    Route::get('culture/surveys/{survey}/results', [CultureController::class, 'surveyResults'])->name('culture.surveys.results');
});
