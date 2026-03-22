<?php

use App\Http\Controllers\DevPortalController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('dev-portal')->name('dev-portal.')->group(function () {
    Route::get('/', [DevPortalController::class, 'index'])->name('index');

    // API Keys
    Route::post('api-keys', [DevPortalController::class, 'storeApiKey'])->name('api-keys.store');
    Route::put('api-keys/{apiKey}', [DevPortalController::class, 'updateApiKey'])->name('api-keys.update');
    Route::delete('api-keys/{apiKey}', [DevPortalController::class, 'destroyApiKey'])->name('api-keys.destroy');
    Route::post('api-keys/{apiKey}/regenerate', [DevPortalController::class, 'regenerateApiKey'])->name('api-keys.regenerate');

    // Webhooks
    Route::post('webhooks', [DevPortalController::class, 'storeWebhook'])->name('webhooks.store');
    Route::put('webhooks/{webhook}', [DevPortalController::class, 'updateWebhook'])->name('webhooks.update');
    Route::delete('webhooks/{webhook}', [DevPortalController::class, 'destroyWebhook'])->name('webhooks.destroy');
    Route::post('webhooks/{webhook}/test', [DevPortalController::class, 'testWebhook'])->name('webhooks.test');
});
