<?php

use App\Http\Controllers\CustomerReviewController;
use App\Http\Controllers\ReferralProgramController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Customer Reviews
    Route::get('customer-reviews', [CustomerReviewController::class, 'index'])->name('customer-reviews');
    Route::post('customer-reviews', [CustomerReviewController::class, 'store'])->name('customer-reviews.store');
    Route::put('customer-reviews/{customerReview}', [CustomerReviewController::class, 'update'])->name('customer-reviews.update');
    Route::delete('customer-reviews/{customerReview}', [CustomerReviewController::class, 'destroy'])->name('customer-reviews.destroy');
    Route::post('customer-reviews/{customerReview}/status', [CustomerReviewController::class, 'updateStatus'])->name('customer-reviews.status');

    // Referral Program
    Route::get('referral-program', [ReferralProgramController::class, 'index'])->name('referral-program');
    Route::post('referral-program', [ReferralProgramController::class, 'store'])->name('referral-program.store');
    Route::put('referral-program/{referralCode}', [ReferralProgramController::class, 'update'])->name('referral-program.update');
    Route::delete('referral-program/{referralCode}', [ReferralProgramController::class, 'destroy'])->name('referral-program.destroy');
    Route::post('referral-program/{referralCode}/conversion', [ReferralProgramController::class, 'addConversion'])->name('referral-program.conversion');
});
