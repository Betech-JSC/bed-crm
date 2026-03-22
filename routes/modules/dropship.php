<?php

use App\Http\Controllers\DropshipController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('dropship', [DropshipController::class, 'index'])->name('dropship.index');

    // Orders
    Route::post('dropship/orders', [DropshipController::class, 'storeOrder'])->name('dropship.orders.store');
    Route::put('dropship/orders/{order}', [DropshipController::class, 'updateOrder'])->name('dropship.orders.update');
    Route::delete('dropship/orders/{order}', [DropshipController::class, 'destroyOrder'])->name('dropship.orders.destroy');
    Route::patch('dropship/orders/{order}/status', [DropshipController::class, 'updateOrderStatus'])->name('dropship.orders.status');

    // Suppliers
    Route::post('dropship/suppliers', [DropshipController::class, 'storeSupplier'])->name('dropship.suppliers.store');
    Route::put('dropship/suppliers/{supplier}', [DropshipController::class, 'updateSupplier'])->name('dropship.suppliers.update');
    Route::delete('dropship/suppliers/{supplier}', [DropshipController::class, 'destroySupplier'])->name('dropship.suppliers.destroy');
});
