<?php

use App\Http\Controllers\ShowcaseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('showcase')->name('showcase.')->group(function () {
    Route::get('/', [ShowcaseController::class, 'index'])->name('index');
    Route::post('/analyze-url', [ShowcaseController::class, 'analyzeUrl'])->name('analyze');
    Route::post('/discover', [ShowcaseController::class, 'discoverByIndustry'])->name('discover');
    Route::post('/collections', [ShowcaseController::class, 'saveCollection'])->name('collections.store');
    Route::delete('/collections/{collection}', [ShowcaseController::class, 'deleteCollection'])->name('collections.destroy');
    Route::delete('/items/{item}', [ShowcaseController::class, 'deleteItem'])->name('items.destroy');
});
