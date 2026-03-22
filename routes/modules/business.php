<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ContractController;
use Illuminate\Support\Facades\Route;

// Products / Services (Popup CRUD — no create/edit pages)
Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::post('products', [ProductController::class, 'store'])->name('products.store');
Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

// Quotations (Separate pages — has dynamic line items)
Route::resource('quotations', QuotationController::class);
Route::post('quotations/{quotation}/submit-approval', [QuotationController::class, 'submitApproval'])->name('quotations.submit-approval');

// Contracts (Popup CRUD — no create/edit pages)
Route::get('contracts', [ContractController::class, 'index'])->name('contracts.index');
Route::post('contracts', [ContractController::class, 'store'])->name('contracts.store');
Route::put('contracts/{contract}', [ContractController::class, 'update'])->name('contracts.update');
Route::delete('contracts/{contract}', [ContractController::class, 'destroy'])->name('contracts.destroy');
Route::post('contracts/{contract}/submit-approval', [ContractController::class, 'submitApproval'])->name('contracts.submit-approval');

// Approvals
Route::get('approvals', [\App\Http\Controllers\ApprovalController::class, 'index'])->name('approvals.index');
Route::post('approvals/approve', [\App\Http\Controllers\ApprovalController::class, 'approve'])->name('approvals.approve');
Route::post('approvals/reject', [\App\Http\Controllers\ApprovalController::class, 'reject'])->name('approvals.reject');
