<?php

use App\Http\Controllers\DealsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // CRUD
    Route::get('deals', [DealsController::class, 'index'])->name('deals');
    Route::get('deals/create', [DealsController::class, 'create'])->name('deals.create');
    Route::post('deals', [DealsController::class, 'store'])->name('deals.store');
    Route::get('deals/{deal}/edit', [DealsController::class, 'edit'])->name('deals.edit');
    Route::put('deals/{deal}', [DealsController::class, 'update'])->name('deals.update');
    Route::delete('deals/{deal}', [DealsController::class, 'destroy'])->name('deals.destroy');
    Route::put('deals/{deal}/restore', [DealsController::class, 'restore'])->name('deals.restore');

    // Kanban actions
    Route::patch('deals/{deal}/stage', [DealsController::class, 'updateStage'])->name('deals.update-stage');
    Route::post('deals/{deal}/won', [DealsController::class, 'markWon'])->name('deals.mark-won');
    Route::post('deals/{deal}/lost', [DealsController::class, 'markLost'])->name('deals.mark-lost');
});
