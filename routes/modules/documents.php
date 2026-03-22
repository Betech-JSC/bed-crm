<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\CrmGuideController;
use Illuminate\Support\Facades\Route;

// Documents (Biên bản & Biểu mẫu)
Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
Route::put('documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

// CRM Guides (Hướng dẫn sử dụng)
Route::get('crm-guides', [CrmGuideController::class, 'index'])->name('crm-guides.index');
Route::post('crm-guides', [CrmGuideController::class, 'store'])->name('crm-guides.store');
Route::put('crm-guides/{crm_guide}', [CrmGuideController::class, 'update'])->name('crm-guides.update');
Route::delete('crm-guides/{crm_guide}', [CrmGuideController::class, 'destroy'])->name('crm-guides.destroy');
