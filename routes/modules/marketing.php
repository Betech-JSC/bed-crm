<?php

use App\Http\Controllers\EmailTemplatesController;
use App\Http\Controllers\EmailListsController;
use App\Http\Controllers\EmailCampaignsController;
use App\Http\Controllers\EmailAutomationsController;
use App\Http\Controllers\EmailSegmentsController;
use App\Http\Controllers\ContentTemplatesController;
use App\Http\Controllers\ContentItemsController;
use App\Http\Controllers\SocialAccountsController;
use App\Http\Controllers\SocialPostsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    // ═══ EMAIL SEGMENTS (replaces email-lists for targeting) ═══
    Route::resource('email-segments', EmailSegmentsController::class);
    Route::post('email-segments/{emailSegment}/recompute', [EmailSegmentsController::class, 'recompute'])->name('email-segments.recompute');
    Route::post('email-segments/{emailSegment}/contacts', [EmailSegmentsController::class, 'addContact'])->name('email-segments.contacts.add');
    Route::delete('email-segments/{emailSegment}/contacts/{segmentContact}', [EmailSegmentsController::class, 'removeContact'])->name('email-segments.contacts.remove');

    // Email Templates (enhanced)
    Route::resource('email-templates', EmailTemplatesController::class);

    // Legacy Email Lists (kept for backward compatibility)
    Route::resource('email-lists', EmailListsController::class);
    Route::post('email-lists/{emailList}/contacts', [EmailListsController::class, 'addContact'])->name('email-lists.contacts.add');
    Route::delete('email-lists/{emailList}/contacts/{emailListContact}', [EmailListsController::class, 'removeContact'])->name('email-lists.contacts.remove');

    // ═══ ENHANCED CAMPAIGNS (A/B testing, revenue attribution) ═══
    Route::resource('email-campaigns', EmailCampaignsController::class);
    Route::post('email-campaigns/{emailCampaign}/send', [EmailCampaignsController::class, 'send'])->name('email-campaigns.send');
    Route::post('email-campaigns/{emailCampaign}/ab-test/declare-winner', [EmailCampaignsController::class, 'declareWinner'])->name('email-campaigns.declare-winner');
    Route::get('email-campaigns/{emailCampaign}/analytics', [EmailCampaignsController::class, 'analytics'])->name('email-campaigns.analytics');

    // ═══ ENHANCED AUTOMATIONS (workflow engine) ═══
    Route::resource('email-automations', EmailAutomationsController::class);
    Route::post('email-automations/{emailAutomation}/activate', [EmailAutomationsController::class, 'activate'])->name('email-automations.activate');
    Route::post('email-automations/{emailAutomation}/pause', [EmailAutomationsController::class, 'pause'])->name('email-automations.pause');
    Route::get('email-automations/{emailAutomation}/enrollments', [EmailAutomationsController::class, 'enrollments'])->name('email-automations.enrollments');

    // ═══ ATTRIBUTION & ANALYTICS API ═══
    Route::prefix('api/email')->group(function () {
        Route::get('attribution/summary', [\App\Http\Controllers\Api\EmailAnalyticsController::class, 'attributionSummary']);
        Route::get('attribution/by-campaign', [\App\Http\Controllers\Api\EmailAnalyticsController::class, 'byCampaign']);
        Route::get('engagement/scores', [\App\Http\Controllers\Api\EmailAnalyticsController::class, 'engagementScores']);
        Route::get('behaviors', [\App\Http\Controllers\Api\EmailAnalyticsController::class, 'behaviors']);
    });


    // Content Templates
    Route::get('content-templates', [ContentTemplatesController::class, 'index'])->name('content-templates');
    Route::get('content-templates/create', [ContentTemplatesController::class, 'create'])->name('content-templates.create');
    Route::post('content-templates', [ContentTemplatesController::class, 'store'])->name('content-templates.store');
    Route::get('content-templates/{contentTemplate}/edit', [ContentTemplatesController::class, 'edit'])->name('content-templates.edit');
    Route::put('content-templates/{contentTemplate}', [ContentTemplatesController::class, 'update'])->name('content-templates.update');
    Route::delete('content-templates/{contentTemplate}', [ContentTemplatesController::class, 'destroy'])->name('content-templates.destroy');

    // Content Items
    Route::get('content-items', [ContentItemsController::class, 'index'])->name('content-items');
    Route::get('content-items/create', [ContentItemsController::class, 'create'])->name('content-items.create');
    Route::post('content-items', [ContentItemsController::class, 'store'])->name('content-items.store');
    Route::get('content-items/{contentItem}', [ContentItemsController::class, 'show'])->name('content-items.show');
    Route::get('content-items/{contentItem}/edit', [ContentItemsController::class, 'edit'])->name('content-items.edit');
    Route::put('content-items/{contentItem}', [ContentItemsController::class, 'update'])->name('content-items.update');
    Route::delete('content-items/{contentItem}', [ContentItemsController::class, 'destroy'])->name('content-items.destroy');
    Route::post('content-items/{contentItem}/generate-variations', [ContentItemsController::class, 'generateVariations'])->name('content-items.generate-variations');
    Route::post('content-items/{contentItem}/optimize', [ContentItemsController::class, 'optimizeForPlatform'])->name('content-items.optimize');

    // Social Accounts
    Route::get('social-accounts', [SocialAccountsController::class, 'index'])->name('social-accounts');
    Route::get('social-accounts/create', [SocialAccountsController::class, 'create'])->name('social-accounts.create');
    Route::post('social-accounts', [SocialAccountsController::class, 'store'])->name('social-accounts.store');
    Route::delete('social-accounts/{socialAccount}', [SocialAccountsController::class, 'destroy'])->name('social-accounts.destroy');
    Route::post('social-accounts/{socialAccount}/refresh', [SocialAccountsController::class, 'refresh'])->name('social-accounts.refresh');
    Route::post('social-accounts/{socialAccount}/validate', [SocialAccountsController::class, 'validateConnection'])->name('social-accounts.validate');

    // Social Posts
    Route::get('social-posts', [SocialPostsController::class, 'index'])->name('social-posts');
    Route::get('social-posts/create', [SocialPostsController::class, 'create'])->name('social-posts.create');
    Route::post('social-posts', [SocialPostsController::class, 'store'])->name('social-posts.store');
    Route::get('social-posts/{socialPost}', [SocialPostsController::class, 'show'])->name('social-posts.show');
    Route::delete('social-posts/{socialPost}', [SocialPostsController::class, 'destroy'])->name('social-posts.destroy');
    Route::post('social-posts/{socialPost}/retry', [SocialPostsController::class, 'retry'])->name('social-posts.retry');
    Route::post('social-posts/{socialPost}/sync-analytics', [SocialPostsController::class, 'syncAnalytics'])->name('social-posts.sync-analytics');
});

// Email Tracking (Public - no auth)
Route::get('email/track/open/{messageId}', [\App\Http\Controllers\Api\EmailTrackingController::class, 'trackOpen'])->name('email.track.open');
Route::get('email/track/click/{messageId}', [\App\Http\Controllers\Api\EmailTrackingController::class, 'trackClick'])->name('email.track.click');
Route::get('email/unsubscribe/{token}', [\App\Http\Controllers\Api\EmailTrackingController::class, 'unsubscribe'])->name('email.unsubscribe');
