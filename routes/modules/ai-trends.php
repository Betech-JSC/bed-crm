<?php

use App\Http\Controllers\AiTrendController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Main page
    Route::get('ai-trends', [AiTrendController::class, 'index'])->name('ai-trends.index');

    // Ecosystem Map
    Route::get('ai-trends/ecosystem', [AiTrendController::class, 'ecosystem'])->name('ai-trends.ecosystem');

    // Monitor CRUD
    Route::post('ai-trends/monitors', [AiTrendController::class, 'storeMonitor'])->name('ai-trends.monitors.store');
    Route::put('ai-trends/monitors/{monitor}', [AiTrendController::class, 'updateMonitor'])->name('ai-trends.monitors.update');
    Route::delete('ai-trends/monitors/{monitor}', [AiTrendController::class, 'destroyMonitor'])->name('ai-trends.monitors.destroy');
    Route::post('ai-trends/monitors/{monitor}/fetch', [AiTrendController::class, 'triggerFetch'])->name('ai-trends.monitors.fetch');

    // Item actions
    Route::post('ai-trends/items/{item}/pin', [AiTrendController::class, 'togglePin'])->name('ai-trends.items.pin');
    Route::post('ai-trends/items/{item}/read', [AiTrendController::class, 'markRead'])->name('ai-trends.items.read');
    Route::post('ai-trends/items/read-all', [AiTrendController::class, 'markAllRead'])->name('ai-trends.items.readAll');
    Route::delete('ai-trends/items/{item}', [AiTrendController::class, 'destroyItem'])->name('ai-trends.items.destroy');
});
