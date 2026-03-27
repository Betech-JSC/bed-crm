<?php

use App\Http\Controllers\LinkBioController;
use App\Http\Controllers\QrCodeController;
use Illuminate\Support\Facades\Route;

// Public pages
Route::get('bio/{username}', [LinkBioController::class, 'show'])->name('link-bio.show');
Route::get('qr/{code}', [QrCodeController::class, 'redirect'])->name('qr-codes.redirect');

// Admin
Route::middleware('auth')->group(function () {
    // Link-in-Bio
    Route::get('link-bio', [LinkBioController::class, 'index'])->name('link-bio');
    Route::post('link-bio', [LinkBioController::class, 'store'])->name('link-bio.store');
    Route::put('link-bio/{linkBioPage}', [LinkBioController::class, 'update'])->name('link-bio.update');
    Route::delete('link-bio/{linkBioPage}', [LinkBioController::class, 'destroy'])->name('link-bio.destroy');

    // QR Codes
    Route::get('qr-codes', [QrCodeController::class, 'index'])->name('qr-codes');
    Route::post('qr-codes', [QrCodeController::class, 'store'])->name('qr-codes.store');
    Route::delete('qr-codes/{qrCode}', [QrCodeController::class, 'destroy'])->name('qr-codes.destroy');
});
