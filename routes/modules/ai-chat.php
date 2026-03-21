<?php

use App\Http\Controllers\AiChatController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('ai-chat', [AiChatController::class, 'index'])->name('ai-chat.index');
    Route::post('ai-chat', [AiChatController::class, 'store'])->name('ai-chat.store');
    Route::get('ai-chat/{conversation}', [AiChatController::class, 'show'])->name('ai-chat.show');
    Route::put('ai-chat/{conversation}', [AiChatController::class, 'update'])->name('ai-chat.update');
    Route::delete('ai-chat/{conversation}', [AiChatController::class, 'destroy'])->name('ai-chat.destroy');
    Route::post('ai-chat/{conversation}/messages', [AiChatController::class, 'sendMessage'])->name('ai-chat.send');

    // AI-Native API endpoints
    Route::post('ai/suggest', [AiChatController::class, 'suggest'])->name('ai.suggest');
    Route::post('ai/draft-email', [AiChatController::class, 'draftEmail'])->name('ai.draft-email');
    Route::post('ai/report', [AiChatController::class, 'report'])->name('ai.report');
});
