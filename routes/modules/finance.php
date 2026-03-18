<?php

use App\Http\Controllers\FinancialController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('finance', [FinancialController::class, 'index'])->name('finance.dashboard');

    // Transactions
    Route::get('finance/transactions', [FinancialController::class, 'transactions'])->name('finance.transactions');
    Route::post('finance/transactions', [FinancialController::class, 'store'])->name('finance.transactions.store');
    Route::put('finance/transactions/{transaction}', [FinancialController::class, 'update'])->name('finance.transactions.update');
    Route::delete('finance/transactions/{transaction}', [FinancialController::class, 'destroy'])->name('finance.transactions.destroy');

    // API endpoints
    Route::get('finance/api/period', [FinancialController::class, 'periodAnalytics'])->name('finance.api.period');
    Route::post('finance/api/snapshot', [FinancialController::class, 'generateSnapshot'])->name('finance.api.snapshot');
});
