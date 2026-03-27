<?php

use App\Http\Controllers\PersonalTaskController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('my-tasks', [PersonalTaskController::class, 'index'])->name('personal-tasks');
    Route::post('my-tasks', [PersonalTaskController::class, 'store'])->name('personal-tasks.store');
    Route::put('my-tasks/{personalTask}', [PersonalTaskController::class, 'update'])->name('personal-tasks.update');
    Route::delete('my-tasks/{personalTask}', [PersonalTaskController::class, 'destroy'])->name('personal-tasks.destroy');
    Route::post('my-tasks/{personalTask}/toggle', [PersonalTaskController::class, 'toggleStatus'])->name('personal-tasks.toggle');
    Route::post('my-tasks/{personalTask}/pin', [PersonalTaskController::class, 'togglePin'])->name('personal-tasks.pin');
    Route::post('my-tasks/bulk', [PersonalTaskController::class, 'bulkUpdate'])->name('personal-tasks.bulk');
});
