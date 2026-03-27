<?php

use App\Http\Controllers\LandingPageController;
use Illuminate\Support\Facades\Route;

// Public pages
Route::get('lp/{slug}', [LandingPageController::class, 'show'])->name('landing-pages.show');

// Admin
Route::middleware('auth')->group(function () {
    Route::get('landing-pages', [LandingPageController::class, 'index'])->name('landing-pages');
    Route::get('landing-pages/create', [LandingPageController::class, 'create'])->name('landing-pages.create');
    Route::post('landing-pages', [LandingPageController::class, 'store'])->name('landing-pages.store');
    Route::get('landing-pages/{landingPage}/edit', [LandingPageController::class, 'edit'])->name('landing-pages.edit');
    Route::put('landing-pages/{landingPage}', [LandingPageController::class, 'update'])->name('landing-pages.update');
    Route::delete('landing-pages/{landingPage}', [LandingPageController::class, 'destroy'])->name('landing-pages.destroy');
});
