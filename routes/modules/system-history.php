<?php

use App\Http\Controllers\SystemHistoryController;
use Illuminate\Support\Facades\Route;

// System History & Trash
Route::get('system-history', [SystemHistoryController::class, 'history'])->name('system-history');
Route::get('system-trash', [SystemHistoryController::class, 'trash'])->name('system-trash');
Route::post('system-trash/restore', [SystemHistoryController::class, 'restore'])->name('system-trash.restore');
Route::delete('system-trash/force-delete', [SystemHistoryController::class, 'forceDelete'])->name('system-trash.force-delete');
