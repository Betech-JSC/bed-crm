<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/logs', [NotificationController::class, 'logs'])->name('notifications.logs');

    // API
    Route::get('notifications/api/recent', [NotificationController::class, 'recent'])->name('notifications.api.recent');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.api.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.api.readAll');
    Route::post('notifications/preferences', [NotificationController::class, 'updatePreferences'])->name('notifications.api.preferences');
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.api.destroy');
});
