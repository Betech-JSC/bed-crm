<?php

use App\Http\Controllers\UtmTrackingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('utm-tracking', [UtmTrackingController::class, 'index'])->name('utm-tracking');
    Route::post('utm-tracking', [UtmTrackingController::class, 'store'])->name('utm-tracking.store');
    Route::put('utm-tracking/{utmLink}', [UtmTrackingController::class, 'update'])->name('utm-tracking.update');
    Route::delete('utm-tracking/{utmLink}', [UtmTrackingController::class, 'destroy'])->name('utm-tracking.destroy');
});
