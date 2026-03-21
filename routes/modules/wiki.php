<?php

use App\Http\Controllers\WikiController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Wiki Articles
    Route::get('wiki', [WikiController::class, 'index'])->name('wiki');
    Route::get('wiki/create', [WikiController::class, 'create'])->name('wiki.create');
    Route::post('wiki', [WikiController::class, 'store'])->name('wiki.store');
    Route::get('wiki/{article}', [WikiController::class, 'show'])->name('wiki.show');
    Route::get('wiki/{article}/edit', [WikiController::class, 'edit'])->name('wiki.edit');
    Route::put('wiki/{article}', [WikiController::class, 'update'])->name('wiki.update');
    Route::delete('wiki/{article}', [WikiController::class, 'destroy'])->name('wiki.destroy');
    Route::put('wiki/{article}/restore', [WikiController::class, 'restore'])->name('wiki.restore');
    Route::post('wiki/{article}/toggle-pin', [WikiController::class, 'togglePin'])->name('wiki.toggle-pin');

    // Wiki Categories
    Route::post('wiki-categories', [WikiController::class, 'storeCategory'])->name('wiki-categories.store');
    Route::put('wiki-categories/{category}', [WikiController::class, 'updateCategory'])->name('wiki-categories.update');
    Route::delete('wiki-categories/{category}', [WikiController::class, 'destroyCategory'])->name('wiki-categories.destroy');
});
