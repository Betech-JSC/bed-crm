<?php

use App\Http\Controllers\VideoAdsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // ── AI Video Ads ──
    Route::get('video-ads', [VideoAdsController::class, 'index'])->name('video-ads');
    Route::get('video-ads/create', [VideoAdsController::class, 'create'])->name('video-ads.create');
    Route::post('video-ads', [VideoAdsController::class, 'store'])->name('video-ads.store');
    Route::get('video-ads/{project}/edit', [VideoAdsController::class, 'edit'])->name('video-ads.edit');
    Route::put('video-ads/{project}', [VideoAdsController::class, 'update'])->name('video-ads.update');
    Route::delete('video-ads/{project}', [VideoAdsController::class, 'destroy'])->name('video-ads.destroy');

    // AI Features
    Route::post('video-ads/{project}/generate-script', [VideoAdsController::class, 'generateScript'])->name('video-ads.generate-script');
    Route::post('video-ads/{project}/generate-caption', [VideoAdsController::class, 'generateCaption'])->name('video-ads.generate-caption');
    Route::post('video-ads/{project}/duplicate', [VideoAdsController::class, 'duplicate'])->name('video-ads.duplicate');
});
