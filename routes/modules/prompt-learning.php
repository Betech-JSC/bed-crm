<?php

use App\Http\Controllers\PromptLearningController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('prompt-learning', [PromptLearningController::class, 'index'])->name('prompt-learning.index');
    Route::post('prompt-learning/seed', [PromptLearningController::class, 'seedDefaults'])->name('prompt-learning.seed');

    // Categories
    Route::post('prompt-learning/categories', [PromptLearningController::class, 'storeCategory'])->name('prompt-learning.categories.store');
    Route::put('prompt-learning/categories/{category}', [PromptLearningController::class, 'updateCategory'])->name('prompt-learning.categories.update');
    Route::delete('prompt-learning/categories/{category}', [PromptLearningController::class, 'deleteCategory'])->name('prompt-learning.categories.delete');

    // Lessons
    Route::post('prompt-learning/lessons', [PromptLearningController::class, 'storeLesson'])->name('prompt-learning.lessons.store');
    Route::put('prompt-learning/lessons/{lesson}', [PromptLearningController::class, 'updateLesson'])->name('prompt-learning.lessons.update');
    Route::delete('prompt-learning/lessons/{lesson}', [PromptLearningController::class, 'deleteLesson'])->name('prompt-learning.lessons.delete');

    // Exercises
    Route::post('prompt-learning/exercises', [PromptLearningController::class, 'storeExercise'])->name('prompt-learning.exercises.store');
    Route::put('prompt-learning/exercises/{exercise}', [PromptLearningController::class, 'updateExercise'])->name('prompt-learning.exercises.update');
    Route::delete('prompt-learning/exercises/{exercise}', [PromptLearningController::class, 'deleteExercise'])->name('prompt-learning.exercises.delete');

    // Attempt
    Route::post('prompt-learning/exercises/{exercise}/submit', [PromptLearningController::class, 'submitExercise'])->name('prompt-learning.exercises.submit');
});
