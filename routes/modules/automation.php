<?php

use App\Http\Controllers\ChatWidgetsController;
use App\Http\Controllers\ChatWidgetDocumentsController;
use App\Http\Controllers\ChatConversationsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Chat Widgets
    Route::get('chat-widgets', [ChatWidgetsController::class, 'index'])->name('chat-widgets.index');
    Route::get('chat-widgets/create', [ChatWidgetsController::class, 'create'])->name('chat-widgets.create');
    Route::post('chat-widgets', [ChatWidgetsController::class, 'store'])->name('chat-widgets.store');
    Route::get('chat-widgets/{chatWidget}/preview', [ChatWidgetsController::class, 'preview'])->name('chat-widgets.preview');
    Route::get('chat-widgets/{chatWidget}/edit', [ChatWidgetsController::class, 'edit'])->name('chat-widgets.edit');
    Route::put('chat-widgets/{chatWidget}', [ChatWidgetsController::class, 'update'])->name('chat-widgets.update');
    Route::delete('chat-widgets/{chatWidget}', [ChatWidgetsController::class, 'destroy'])->name('chat-widgets.destroy');

    // Chat Widget Documents (RAG)
    Route::get('chat-widgets/{chatWidget}/documents', [ChatWidgetDocumentsController::class, 'index'])->name('chat-widgets.documents.index');
    Route::post('chat-widgets/{chatWidget}/documents', [ChatWidgetDocumentsController::class, 'store'])->name('chat-widgets.documents.store');
    Route::delete('chat-widgets/{chatWidget}/documents/{chatWidgetDocument}', [ChatWidgetDocumentsController::class, 'destroy'])->name('chat-widgets.documents.destroy');
    Route::post('chat-widgets/{chatWidget}/documents/{documentId}/toggle', [ChatWidgetDocumentsController::class, 'toggle'])->name('chat-widgets.documents.toggle');

    // Chat Conversations
    Route::get('chat-conversations', [ChatConversationsController::class, 'index'])->name('chat-conversations.index');
    Route::get('chat-conversations/{chatConversation}', [ChatConversationsController::class, 'show'])->name('chat-conversations.show');
});

// Chat Widget Public API
Route::prefix('api/chat')->middleware([\App\Http\Middleware\ValidateChatWidgetOrigin::class])->group(function () {
    Route::post('{widgetKey}/init', [\App\Http\Controllers\Api\ChatController::class, 'init'])
        ->name('api.chat.init')->middleware('throttle:60,1');
    Route::post('{widgetKey}/message', [\App\Http\Controllers\Api\ChatController::class, 'sendMessage'])
        ->name('api.chat.message')->middleware('throttle:30,1');
    Route::get('{widgetKey}/history', [\App\Http\Controllers\Api\ChatController::class, 'getHistory'])
        ->name('api.chat.history')->middleware('throttle:60,1');
    Route::post('{widgetKey}/close', [\App\Http\Controllers\Api\ChatController::class, 'closeConversation'])
        ->name('api.chat.close')->middleware('throttle:10,1');
});

// Chat Widget Embedding Scripts
Route::get('chat/widget/{widgetKey}.js', function ($widgetKey) {
    $widget = \App\Models\ChatWidget::where('widget_key', $widgetKey)->where('is_active', true)->firstOrFail();
    $script = view('chat-widget-embed', ['widgetKey' => $widgetKey, 'apiUrl' => url('/api/chat')])->render();
    return response($script, 200, ['Content-Type' => 'application/javascript', 'Cache-Control' => 'public, max-age=3600']);
})->name('chat.widget.embed');

Route::get('chat/widget-preview/{widgetKey}.js', function ($widgetKey) {
    $widget = \App\Models\ChatWidget::where('widget_key', $widgetKey)->firstOrFail();
    if (!auth()->check() || auth()->user()->account_id !== $widget->account_id) { abort(403); }
    $script = view('chat-widget-embed', ['widgetKey' => $widgetKey, 'apiUrl' => url('/api/chat')])->render();
    return response($script, 200, ['Content-Type' => 'application/javascript', 'Cache-Control' => 'no-cache, no-store, must-revalidate']);
})->name('chat.widget.preview')->middleware('auth');
