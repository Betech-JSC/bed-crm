<?php

use App\Http\Controllers\AiDataHubController;
use App\Http\Controllers\AiAgentController;
use Illuminate\Support\Facades\Route;

// ── AI Data Hub ──
Route::get('ai-data-hub', [AiDataHubController::class, 'index'])->name('ai-data-hub');
Route::post('ai-data-hub/knowledge-bases', [AiDataHubController::class, 'storeKnowledgeBase'])->name('ai-data-hub.kb.store');
Route::post('ai-data-hub/documents', [AiDataHubController::class, 'storeDocument'])->name('ai-data-hub.documents.store');
Route::post('ai-data-hub/sync', [AiDataHubController::class, 'syncCrmData'])->name('ai-data-hub.sync');
Route::post('ai-data-hub/training-sets', [AiDataHubController::class, 'storeTrainingSet'])->name('ai-data-hub.training.store');
Route::delete('ai-data-hub/knowledge-bases/{aiKnowledgeBase}', [AiDataHubController::class, 'destroyKnowledgeBase'])->name('ai-data-hub.kb.destroy');
Route::delete('ai-data-hub/documents/{aiKnowledgeDocument}', [AiDataHubController::class, 'destroyDocument'])->name('ai-data-hub.documents.destroy');

// ── AI Agents ──
Route::get('ai-agents', [AiAgentController::class, 'index'])->name('ai-agents');
Route::post('ai-agents', [AiAgentController::class, 'store'])->name('ai-agents.store');
Route::put('ai-agents/{aiAgent}', [AiAgentController::class, 'update'])->name('ai-agents.update');
Route::post('ai-agents/{aiAgent}/toggle', [AiAgentController::class, 'toggle'])->name('ai-agents.toggle');
Route::get('ai-agents/{aiAgent}/chat', [AiAgentController::class, 'chat'])->name('ai-agents.chat');
Route::post('ai-agents/{aiAgent}/chat', [AiAgentController::class, 'sendMessage'])->name('ai-agents.send');
Route::post('ai-agents/conversations/{conversation}/rate', [AiAgentController::class, 'rateConversation'])->name('ai-agents.rate');
Route::delete('ai-agents/{aiAgent}', [AiAgentController::class, 'destroy'])->name('ai-agents.destroy');
