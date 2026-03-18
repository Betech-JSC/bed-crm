<?php

use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('projects', [ProjectController::class, 'index'])->name('projects');
    Route::get('projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::put('projects/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore');

    // Tasks
    Route::post('projects/{project}/tasks', [ProjectController::class, 'storeTask'])->name('projects.tasks.store');
    Route::patch('projects/{project}/tasks/{task}', [ProjectController::class, 'updateTask'])->name('projects.tasks.update');
    Route::delete('projects/{project}/tasks/{task}', [ProjectController::class, 'destroyTask'])->name('projects.tasks.destroy');

    // Resources
    Route::post('projects/{project}/resources', [ProjectController::class, 'storeResource'])->name('projects.resources.store');
    Route::patch('projects/{project}/resources/{resource}', [ProjectController::class, 'updateResource'])->name('projects.resources.update');

    // Expenses
    Route::post('projects/{project}/expenses', [ProjectController::class, 'storeExpense'])->name('projects.expenses.store');
});
