<?php

use App\Http\Controllers\BrandGuidelineController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('brand-foundation')->name('brand.')->group(function () {
    Route::get('/', [BrandGuidelineController::class, 'index'])->name('index');
    Route::post('/foundation', [BrandGuidelineController::class, 'updateFoundation'])->name('foundation');
    Route::post('/visual', [BrandGuidelineController::class, 'updateVisual'])->name('visual');
    Route::post('/voice', [BrandGuidelineController::class, 'updateVoice'])->name('voice');
    Route::post('/upload-logo', [BrandGuidelineController::class, 'uploadLogo'])->name('upload-logo');
    Route::post('/assets', [BrandGuidelineController::class, 'uploadAsset'])->name('assets.store');
    Route::delete('/assets/{brandAsset}', [BrandGuidelineController::class, 'deleteAsset'])->name('assets.destroy');
    Route::post('/publish', [BrandGuidelineController::class, 'publish'])->name('publish');
});
