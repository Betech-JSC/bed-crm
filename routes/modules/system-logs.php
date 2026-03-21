<?php

use App\Http\Controllers\SystemLogController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('system-logs', [SystemLogController::class, 'index'])->name('system-logs.index');
    Route::get('api/system-logs/fetch', [SystemLogController::class, 'fetch'])->name('system-logs.fetch');
    Route::get('api/system-logs/stream', [SystemLogController::class, 'stream'])->name('system-logs.stream');
    Route::post('api/system-logs/clear', [SystemLogController::class, 'clear'])->name('system-logs.clear');
    Route::get('api/system-logs/download', [SystemLogController::class, 'download'])->name('system-logs.download');
});
