<?php

use App\Http\Controllers\WebFormController;
use Illuminate\Support\Facades\Route;

// Admin routes (auth required)
Route::middleware('auth')->group(function () {
    Route::get('web-forms', [WebFormController::class, 'index'])->name('web-forms');
    Route::get('web-forms/create', [WebFormController::class, 'create'])->name('web-forms.create');
    Route::post('web-forms', [WebFormController::class, 'store'])->name('web-forms.store');
    Route::get('web-forms/{webForm}/edit', [WebFormController::class, 'edit'])->name('web-forms.edit');
    Route::put('web-forms/{webForm}', [WebFormController::class, 'update'])->name('web-forms.update');
    Route::delete('web-forms/{webForm}', [WebFormController::class, 'destroy'])->name('web-forms.destroy');
    Route::post('web-forms/submissions/{submission}/read', [WebFormController::class, 'markRead'])->name('web-forms.mark-read');
});

// Public routes (no auth)
Route::get('forms/embed/{slug}', [WebFormController::class, 'embed'])->name('forms.embed');
Route::post('forms/submit/{slug}', [WebFormController::class, 'submit'])->name('forms.submit');
