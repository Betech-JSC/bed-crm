<?php

use App\Http\Controllers\LeadsController;
use App\Http\Controllers\LeadScoringController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // CRUD
    Route::get('leads', [LeadsController::class, 'index'])->name('leads');
    Route::get('leads/create', [LeadsController::class, 'create'])->name('leads.create');
    Route::post('leads', [LeadsController::class, 'store'])->name('leads.store');
    Route::get('leads/{lead}/edit', [LeadsController::class, 'edit'])->name('leads.edit');
    Route::put('leads/{lead}', [LeadsController::class, 'update'])->name('leads.update');
    Route::delete('leads/{lead}', [LeadsController::class, 'destroy'])->name('leads.destroy');
    Route::put('leads/{lead}/restore', [LeadsController::class, 'restore'])->name('leads.restore');

    // Actions
    Route::post('leads/{lead}/notes', [LeadsController::class, 'addNote'])->name('leads.notes.store');
    Route::post('leads/{lead}/convert', [\App\Http\Controllers\DealsController::class, 'convertFromLead'])->name('leads.convert');

    // Scoring
    Route::post('leads/{lead}/score', [LeadScoringController::class, 'score'])->name('leads.score');
    Route::post('leads/score-all', [LeadScoringController::class, 'scoreAll'])->name('leads.score-all');
    Route::post('leads/{lead}/enrich', [LeadScoringController::class, 'enrich'])->name('leads.enrich');

    // Playbook suggestions
    Route::get('leads/{lead}/playbook-suggestions', [\App\Http\Controllers\SalesPlaybooksController::class, 'suggestionsForLead'])
        ->name('leads.playbook-suggestions');
});
