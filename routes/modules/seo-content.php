<?php

use App\Http\Controllers\SeoDashboardController;
use App\Http\Controllers\ContentCalendarController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // SEO Dashboard
    Route::get('seo-dashboard', [SeoDashboardController::class, 'index'])->name('seo-dashboard');
    Route::post('seo-dashboard/keywords', [SeoDashboardController::class, 'storeKeyword'])->name('seo-keywords.store');
    Route::put('seo-dashboard/keywords/{seoKeyword}', [SeoDashboardController::class, 'updateKeyword'])->name('seo-keywords.update');
    Route::delete('seo-dashboard/keywords/{seoKeyword}', [SeoDashboardController::class, 'destroyKeyword'])->name('seo-keywords.destroy');
    Route::post('seo-dashboard/issues/{seoAuditIssue}/fix', [SeoDashboardController::class, 'fixIssue'])->name('seo-issues.fix');

    // Content Calendar
    Route::get('content-calendar', [ContentCalendarController::class, 'index'])->name('content-calendar');
    Route::post('content-calendar', [ContentCalendarController::class, 'store'])->name('content-calendar.store');
    Route::put('content-calendar/{contentCalendar}', [ContentCalendarController::class, 'update'])->name('content-calendar.update');
    Route::delete('content-calendar/{contentCalendar}', [ContentCalendarController::class, 'destroy'])->name('content-calendar.destroy');
});
