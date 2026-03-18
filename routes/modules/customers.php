<?php

use App\Http\Controllers\CustomerSuccessController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('customers', [CustomerSuccessController::class, 'index'])->name('customers');
    Route::get('customers/create', [CustomerSuccessController::class, 'create'])->name('customers.create');
    Route::post('customers', [CustomerSuccessController::class, 'store'])->name('customers.store');
    Route::get('customers/{customer}/edit', [CustomerSuccessController::class, 'edit'])->name('customers.edit');
    Route::put('customers/{customer}', [CustomerSuccessController::class, 'update'])->name('customers.update');
    Route::delete('customers/{customer}', [CustomerSuccessController::class, 'destroy'])->name('customers.destroy');
    Route::put('customers/{customer}/restore', [CustomerSuccessController::class, 'restore'])->name('customers.restore');

    // Health
    Route::post('customers/{customer}/recalculate-health', [CustomerSuccessController::class, 'recalculateHealth'])->name('customers.recalculate-health');

    // Tickets
    Route::post('customers/{customer}/tickets', [CustomerSuccessController::class, 'storeTicket'])->name('customers.tickets.store');
    Route::patch('customers/{customer}/tickets/{ticket}', [CustomerSuccessController::class, 'updateTicket'])->name('customers.tickets.update');

    // Upsell
    Route::post('customers/{customer}/upsells', [CustomerSuccessController::class, 'storeUpsell'])->name('customers.upsells.store');
});
