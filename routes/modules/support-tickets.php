<?php

use App\Http\Controllers\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('support-tickets', [SupportTicketController::class, 'index'])->name('support-tickets');
    Route::get('support-tickets/create', [SupportTicketController::class, 'create'])->name('support-tickets.create');
    Route::post('support-tickets', [SupportTicketController::class, 'store'])->name('support-tickets.store');
    Route::get('support-tickets/{supportTicket}/edit', [SupportTicketController::class, 'edit'])->name('support-tickets.edit');
    Route::put('support-tickets/{supportTicket}', [SupportTicketController::class, 'update'])->name('support-tickets.update');
    Route::delete('support-tickets/{supportTicket}', [SupportTicketController::class, 'destroy'])->name('support-tickets.destroy');
});
