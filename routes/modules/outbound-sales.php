<?php

use App\Http\Controllers\OutboundSalesController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('outbound-sales', [OutboundSalesController::class, 'index'])->name('outbound-sales.index');
    Route::get('outbound-sales/{outboundCampaign}', [OutboundSalesController::class, 'show'])->name('outbound-sales.show');

    // Campaign actions
    Route::post('outbound-sales/{outboundCampaign}/pause', [OutboundSalesController::class, 'pause'])->name('outbound-sales.pause');
    Route::post('outbound-sales/{outboundCampaign}/resume', [OutboundSalesController::class, 'resume'])->name('outbound-sales.resume');
    Route::post('outbound-sales/{outboundCampaign}/cancel', [OutboundSalesController::class, 'cancel'])->name('outbound-sales.cancel');
    Route::post('outbound-sales/{outboundCampaign}/mark-replied', [OutboundSalesController::class, 'markReplied'])->name('outbound-sales.mark-replied');

    // Manual trigger from leads
    Route::post('leads/{lead}/outbound', [OutboundSalesController::class, 'triggerForLead'])->name('leads.outbound');
});

// Webhook endpoints (no auth — called from external services)
Route::prefix('api/outbound')->group(function () {
    Route::post('{outboundCampaign}/track-open', [OutboundSalesController::class, 'trackOpen'])->name('outbound-sales.track-open');
    Route::post('{outboundCampaign}/track-click', [OutboundSalesController::class, 'trackClick'])->name('outbound-sales.track-click');
});
