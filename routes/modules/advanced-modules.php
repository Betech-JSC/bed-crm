<?php

use App\Http\Controllers\GmbController;
use App\Http\Controllers\AiContentController;
use App\Http\Controllers\ChatbotFlowController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Google My Business
    Route::get('gmb-dashboard', [GmbController::class, 'index'])->name('gmb-dashboard');
    Route::post('gmb-dashboard/locations', [GmbController::class, 'storeLocation'])->name('gmb.locations.store');
    Route::delete('gmb-dashboard/locations/{gmbLocation}', [GmbController::class, 'deleteLocation'])->name('gmb.locations.destroy');
    Route::post('gmb-dashboard/reviews/{gmbReview}/reply', [GmbController::class, 'replyReview'])->name('gmb.reviews.reply');
    Route::post('gmb-dashboard/posts', [GmbController::class, 'storePost'])->name('gmb.posts.store');

    // AI Content Generator
    Route::get('ai-content', [AiContentController::class, 'index'])->name('ai-content');
    Route::post('ai-content/generate', [AiContentController::class, 'generate'])->name('ai-content.generate');
    Route::post('ai-content/templates', [AiContentController::class, 'storeTemplate'])->name('ai-content.templates.store');
    Route::delete('ai-content/{aiGeneratedContent}', [AiContentController::class, 'destroy'])->name('ai-content.destroy');

    // Chatbot Flows
    Route::get('chatbot-flows', [ChatbotFlowController::class, 'index'])->name('chatbot-flows');
    Route::post('chatbot-flows', [ChatbotFlowController::class, 'store'])->name('chatbot-flows.store');
    Route::put('chatbot-flows/{chatbotFlow}', [ChatbotFlowController::class, 'update'])->name('chatbot-flows.update');
    Route::delete('chatbot-flows/{chatbotFlow}', [ChatbotFlowController::class, 'destroy'])->name('chatbot-flows.destroy');
    Route::post('chatbot-flows/{chatbotFlow}/toggle', [ChatbotFlowController::class, 'toggleStatus'])->name('chatbot-flows.toggle');
});
