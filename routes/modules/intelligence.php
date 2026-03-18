<?php

use App\Http\Controllers\BusinessIntelligenceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('intelligence', [BusinessIntelligenceController::class, 'index'])->name('intelligence.dashboard');
    Route::get('intelligence/api/prediction', [BusinessIntelligenceController::class, 'prediction'])->name('intelligence.api.prediction');
    Route::get('intelligence/api/refresh', [BusinessIntelligenceController::class, 'refresh'])->name('intelligence.api.refresh');
});
