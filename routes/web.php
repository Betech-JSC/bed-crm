<?php

use App\Http\Controllers\ActivitiesController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ContactsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DealsController;
use App\Http\Controllers\ICPsController;
use App\Http\Controllers\ImagesController;
use App\Http\Controllers\LeadScoringController;
use App\Http\Controllers\LeadsController;
use App\Http\Controllers\OrganizationsController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\WorkflowsController;
use App\Http\Controllers\SLASettingsController;
use App\Http\Controllers\ProposalsController;
use App\Http\Controllers\SalesPlaybooksController;
use App\Http\Controllers\ContentTemplatesController;
use App\Http\Controllers\ContentItemsController;
use App\Http\Controllers\SocialAccountsController;
use App\Http\Controllers\SocialPostsController;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\EmailTemplatesController;
use App\Http\Controllers\EmailListsController;
use App\Http\Controllers\EmailCampaignsController;
use App\Http\Controllers\EmailAutomationsController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\RolesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Auth

Route::get('login', [AuthenticatedSessionController::class, 'create'])
    ->name('login')
    ->middleware('guest');

Route::post('login', [AuthenticatedSessionController::class, 'store'])
    ->name('login.store')
    ->middleware('guest');

Route::delete('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

// Dashboard

Route::get('/', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->middleware('auth');

// Users

Route::get('users', [UsersController::class, 'index'])
    ->name('users')
    ->middleware('auth');

Route::get('users/create', [UsersController::class, 'create'])
    ->name('users.create')
    ->middleware('auth');

Route::post('users', [UsersController::class, 'store'])
    ->name('users.store')
    ->middleware('auth');

Route::get('users/{user}/edit', [UsersController::class, 'edit'])
    ->name('users.edit')
    ->middleware('auth');

Route::put('users/{user}', [UsersController::class, 'update'])
    ->name('users.update')
    ->middleware('auth');

Route::delete('users/{user}', [UsersController::class, 'destroy'])
    ->name('users.destroy')
    ->middleware('auth');

Route::put('users/{user}/restore', [UsersController::class, 'restore'])
    ->name('users.restore')
    ->middleware('auth');

// Organizations

Route::get('organizations', [OrganizationsController::class, 'index'])
    ->name('organizations')
    ->middleware('auth');

Route::get('organizations/create', [OrganizationsController::class, 'create'])
    ->name('organizations.create')
    ->middleware('auth');

Route::post('organizations', [OrganizationsController::class, 'store'])
    ->name('organizations.store')
    ->middleware('auth');

Route::get('organizations/{organization}/edit', [OrganizationsController::class, 'edit'])
    ->name('organizations.edit')
    ->middleware('auth');

Route::put('organizations/{organization}', [OrganizationsController::class, 'update'])
    ->name('organizations.update')
    ->middleware('auth');

Route::delete('organizations/{organization}', [OrganizationsController::class, 'destroy'])
    ->name('organizations.destroy')
    ->middleware('auth');

Route::put('organizations/{organization}/restore', [OrganizationsController::class, 'restore'])
    ->name('organizations.restore')
    ->middleware('auth');

// Contacts

Route::get('contacts', [ContactsController::class, 'index'])
    ->name('contacts')
    ->middleware('auth');

Route::get('contacts/create', [ContactsController::class, 'create'])
    ->name('contacts.create')
    ->middleware('auth');

Route::post('contacts', [ContactsController::class, 'store'])
    ->name('contacts.store')
    ->middleware('auth');

Route::get('contacts/{contact}/edit', [ContactsController::class, 'edit'])
    ->name('contacts.edit')
    ->middleware('auth');

Route::put('contacts/{contact}', [ContactsController::class, 'update'])
    ->name('contacts.update')
    ->middleware('auth');

Route::delete('contacts/{contact}', [ContactsController::class, 'destroy'])
    ->name('contacts.destroy')
    ->middleware('auth');

Route::put('contacts/{contact}/restore', [ContactsController::class, 'restore'])
    ->name('contacts.restore')
    ->middleware('auth');

// Reports

Route::get('reports', [ReportsController::class, 'index'])
    ->name('reports')
    ->middleware('auth');

// Leads

Route::get('leads', [LeadsController::class, 'index'])
    ->name('leads')
    ->middleware('auth');

Route::get('leads/create', [LeadsController::class, 'create'])
    ->name('leads.create')
    ->middleware('auth');

Route::post('leads', [LeadsController::class, 'store'])
    ->name('leads.store')
    ->middleware('auth');

Route::get('leads/{lead}/edit', [LeadsController::class, 'edit'])
    ->name('leads.edit')
    ->middleware('auth');

Route::put('leads/{lead}', [LeadsController::class, 'update'])
    ->name('leads.update')
    ->middleware('auth');

Route::delete('leads/{lead}', [LeadsController::class, 'destroy'])
    ->name('leads.destroy')
    ->middleware('auth');

Route::put('leads/{lead}/restore', [LeadsController::class, 'restore'])
    ->name('leads.restore')
    ->middleware('auth');

Route::post('leads/{lead}/notes', [LeadsController::class, 'addNote'])
    ->name('leads.notes.store')
    ->middleware('auth');

Route::post('leads/{lead}/convert', [DealsController::class, 'convertFromLead'])
    ->name('leads.convert')
    ->middleware('auth');

// Deals

Route::get('deals', [DealsController::class, 'index'])
    ->name('deals')
    ->middleware('auth');

Route::get('deals/create', [DealsController::class, 'create'])
    ->name('deals.create')
    ->middleware('auth');

Route::post('deals', [DealsController::class, 'store'])
    ->name('deals.store')
    ->middleware('auth');

Route::get('deals/{deal}/edit', [DealsController::class, 'edit'])
    ->name('deals.edit')
    ->middleware('auth');

Route::put('deals/{deal}', [DealsController::class, 'update'])
    ->name('deals.update')
    ->middleware('auth');

Route::patch('deals/{deal}/stage', [DealsController::class, 'updateStage'])
    ->name('deals.update-stage')
    ->middleware('auth');

Route::post('deals/{deal}/won', [DealsController::class, 'markWon'])
    ->name('deals.mark-won')
    ->middleware('auth');

Route::post('deals/{deal}/lost', [DealsController::class, 'markLost'])
    ->name('deals.mark-lost')
    ->middleware('auth');

Route::delete('deals/{deal}', [DealsController::class, 'destroy'])
    ->name('deals.destroy')
    ->middleware('auth');

Route::put('deals/{deal}/restore', [DealsController::class, 'restore'])
    ->name('deals.restore')
    ->middleware('auth');

// Activities

Route::post('activities', [ActivitiesController::class, 'store'])
    ->name('activities.store')
    ->middleware('auth');

Route::put('activities/{activity}', [ActivitiesController::class, 'update'])
    ->name('activities.update')
    ->middleware('auth');

Route::delete('activities/{activity}', [ActivitiesController::class, 'destroy'])
    ->name('activities.destroy')
    ->middleware('auth');

// ICPs

Route::get('icps', [ICPsController::class, 'index'])
    ->name('icps')
    ->middleware('auth');

Route::get('icps/create', [ICPsController::class, 'create'])
    ->name('icps.create')
    ->middleware('auth');

Route::post('icps', [ICPsController::class, 'store'])
    ->name('icps.store')
    ->middleware('auth');

Route::get('icps/{icp}/edit', [ICPsController::class, 'edit'])
    ->name('icps.edit')
    ->middleware('auth');

Route::put('icps/{icp}', [ICPsController::class, 'update'])
    ->name('icps.update')
    ->middleware('auth');

Route::delete('icps/{icp}', [ICPsController::class, 'destroy'])
    ->name('icps.destroy')
    ->middleware('auth');

// Lead Scoring

Route::post('leads/{lead}/score', [LeadScoringController::class, 'score'])
    ->name('leads.score')
    ->middleware('auth');

Route::post('leads/score-all', [LeadScoringController::class, 'scoreAll'])
    ->name('leads.score-all')
    ->middleware('auth');

Route::post('leads/{lead}/enrich', [LeadScoringController::class, 'enrich'])
    ->name('leads.enrich')
    ->middleware('auth');

// Workflows

Route::get('workflows', [WorkflowsController::class, 'index'])
    ->name('workflows')
    ->middleware('auth');

Route::get('workflows/create', [WorkflowsController::class, 'create'])
    ->name('workflows.create')
    ->middleware('auth');

Route::post('workflows', [WorkflowsController::class, 'store'])
    ->name('workflows.store')
    ->middleware('auth');

Route::get('workflows/{workflow}/edit', [WorkflowsController::class, 'edit'])
    ->name('workflows.edit')
    ->middleware('auth');

Route::put('workflows/{workflow}', [WorkflowsController::class, 'update'])
    ->name('workflows.update')
    ->middleware('auth');

Route::delete('workflows/{workflow}', [WorkflowsController::class, 'destroy'])
    ->name('workflows.destroy')
    ->middleware('auth');

// SLA Settings
Route::get('sla-settings', [SLASettingsController::class, 'index'])
    ->name('sla-settings')
    ->middleware('auth');

Route::get('sla-settings/create', [SLASettingsController::class, 'create'])
    ->name('sla-settings.create')
    ->middleware('auth');

Route::post('sla-settings', [SLASettingsController::class, 'store'])
    ->name('sla-settings.store')
    ->middleware('auth');

Route::get('sla-settings/{slaSetting}/edit', [SLASettingsController::class, 'edit'])
    ->name('sla-settings.edit')
    ->middleware('auth');

Route::put('sla-settings/{slaSetting}', [SLASettingsController::class, 'update'])
    ->name('sla-settings.update')
    ->middleware('auth');

Route::delete('sla-settings/{slaSetting}', [SLASettingsController::class, 'destroy'])
    ->name('sla-settings.destroy')
    ->middleware('auth');

// Proposals
Route::get('proposals', [ProposalsController::class, 'index'])
    ->name('proposals')
    ->middleware('auth');

Route::get('proposals/create', [ProposalsController::class, 'create'])
    ->name('proposals.create')
    ->middleware('auth');

Route::post('proposals', [ProposalsController::class, 'store'])
    ->name('proposals.store')
    ->middleware('auth');

Route::get('proposals/{proposal}', [ProposalsController::class, 'show'])
    ->name('proposals.show')
    ->middleware('auth');

Route::get('proposals/{proposal}/edit', [ProposalsController::class, 'edit'])
    ->name('proposals.edit')
    ->middleware('auth');

Route::put('proposals/{proposal}', [ProposalsController::class, 'update'])
    ->name('proposals.update')
    ->middleware('auth');

Route::delete('proposals/{proposal}', [ProposalsController::class, 'destroy'])
    ->name('proposals.destroy')
    ->middleware('auth');

Route::post('proposals/{proposal}/version', [ProposalsController::class, 'createVersion'])
    ->name('proposals.version')
    ->middleware('auth');

Route::post('proposals/{proposal}/send', [ProposalsController::class, 'send'])
    ->name('proposals.send')
    ->middleware('auth');

Route::post('proposals/{proposal}/accept', [ProposalsController::class, 'accept'])
    ->name('proposals.accept')
    ->middleware('auth');

Route::post('proposals/{proposal}/reject', [ProposalsController::class, 'reject'])
    ->name('proposals.reject')
    ->middleware('auth');

Route::get('proposals/{proposal}/download', [ProposalsController::class, 'download'])
    ->name('proposals.download')
    ->middleware('auth');

Route::post('proposals/{proposal}/track-view', [ProposalsController::class, 'trackView'])
    ->name('proposals.track-view')
    ->middleware('auth');

// Sales Playbooks
Route::get('sales-playbooks', [SalesPlaybooksController::class, 'index'])
    ->name('sales-playbooks')
    ->middleware('auth');

Route::get('sales-playbooks/create', [SalesPlaybooksController::class, 'create'])
    ->name('sales-playbooks.create')
    ->middleware('auth');

Route::post('sales-playbooks', [SalesPlaybooksController::class, 'store'])
    ->name('sales-playbooks.store')
    ->middleware('auth');

Route::get('sales-playbooks/{playbook}', [SalesPlaybooksController::class, 'show'])
    ->name('sales-playbooks.show')
    ->middleware('auth');

Route::get('sales-playbooks/{playbook}/edit', [SalesPlaybooksController::class, 'edit'])
    ->name('sales-playbooks.edit')
    ->middleware('auth');

Route::put('sales-playbooks/{playbook}', [SalesPlaybooksController::class, 'update'])
    ->name('sales-playbooks.update')
    ->middleware('auth');

Route::delete('sales-playbooks/{playbook}', [SalesPlaybooksController::class, 'destroy'])
    ->name('sales-playbooks.destroy')
    ->middleware('auth');

Route::get('deals/{deal}/playbook-suggestions', [SalesPlaybooksController::class, 'suggestionsForDeal'])
    ->name('deals.playbook-suggestions')
    ->middleware('auth');

Route::get('leads/{lead}/playbook-suggestions', [SalesPlaybooksController::class, 'suggestionsForLead'])
    ->name('leads.playbook-suggestions')
    ->middleware('auth');

// Content Templates
Route::get('content-templates', [ContentTemplatesController::class, 'index'])
    ->name('content-templates')
    ->middleware('auth');

Route::get('content-templates/create', [ContentTemplatesController::class, 'create'])
    ->name('content-templates.create')
    ->middleware('auth');

Route::post('content-templates', [ContentTemplatesController::class, 'store'])
    ->name('content-templates.store')
    ->middleware('auth');

Route::get('content-templates/{contentTemplate}/edit', [ContentTemplatesController::class, 'edit'])
    ->name('content-templates.edit')
    ->middleware('auth');

Route::put('content-templates/{contentTemplate}', [ContentTemplatesController::class, 'update'])
    ->name('content-templates.update')
    ->middleware('auth');

Route::delete('content-templates/{contentTemplate}', [ContentTemplatesController::class, 'destroy'])
    ->name('content-templates.destroy')
    ->middleware('auth');

// Content Items
Route::get('content-items', [ContentItemsController::class, 'index'])
    ->name('content-items')
    ->middleware('auth');

Route::get('content-items/create', [ContentItemsController::class, 'create'])
    ->name('content-items.create')
    ->middleware('auth');

Route::post('content-items', [ContentItemsController::class, 'store'])
    ->name('content-items.store')
    ->middleware('auth');

Route::get('content-items/{contentItem}', [ContentItemsController::class, 'show'])
    ->name('content-items.show')
    ->middleware('auth');

Route::get('content-items/{contentItem}/edit', [ContentItemsController::class, 'edit'])
    ->name('content-items.edit')
    ->middleware('auth');

Route::put('content-items/{contentItem}', [ContentItemsController::class, 'update'])
    ->name('content-items.update')
    ->middleware('auth');

Route::delete('content-items/{contentItem}', [ContentItemsController::class, 'destroy'])
    ->name('content-items.destroy')
    ->middleware('auth');

Route::post('content-items/{contentItem}/generate-variations', [ContentItemsController::class, 'generateVariations'])
    ->name('content-items.generate-variations')
    ->middleware('auth');

Route::post('content-items/{contentItem}/optimize', [ContentItemsController::class, 'optimizeForPlatform'])
    ->name('content-items.optimize')
    ->middleware('auth');

// Social Accounts
Route::get('social-accounts', [SocialAccountsController::class, 'index'])
    ->name('social-accounts')
    ->middleware('auth');

Route::get('social-accounts/create', [SocialAccountsController::class, 'create'])
    ->name('social-accounts.create')
    ->middleware('auth');

Route::post('social-accounts', [SocialAccountsController::class, 'store'])
    ->name('social-accounts.store')
    ->middleware('auth');

Route::delete('social-accounts/{socialAccount}', [SocialAccountsController::class, 'destroy'])
    ->name('social-accounts.destroy')
    ->middleware('auth');

Route::post('social-accounts/{socialAccount}/refresh', [SocialAccountsController::class, 'refresh'])
    ->name('social-accounts.refresh')
    ->middleware('auth');

Route::post('social-accounts/{socialAccount}/validate', [SocialAccountsController::class, 'validateConnection'])
    ->name('social-accounts.validate')
    ->middleware('auth');

// Social Posts
Route::get('social-posts', [SocialPostsController::class, 'index'])
    ->name('social-posts')
    ->middleware('auth');

Route::get('social-posts/create', [SocialPostsController::class, 'create'])
    ->name('social-posts.create')
    ->middleware('auth');

Route::post('social-posts', [SocialPostsController::class, 'store'])
    ->name('social-posts.store')
    ->middleware('auth');

Route::get('social-posts/{socialPost}', [SocialPostsController::class, 'show'])
    ->name('social-posts.show')
    ->middleware('auth');

Route::delete('social-posts/{socialPost}', [SocialPostsController::class, 'destroy'])
    ->name('social-posts.destroy')
    ->middleware('auth');

Route::post('social-posts/{socialPost}/retry', [SocialPostsController::class, 'retry'])
    ->name('social-posts.retry')
    ->middleware('auth');

Route::post('social-posts/{socialPost}/sync-analytics', [SocialPostsController::class, 'syncAnalytics'])
    ->name('social-posts.sync-analytics')
    ->middleware('auth');

// Files
Route::get('files', [FilesController::class, 'index'])
    ->name('files')
    ->middleware('auth');

Route::get('files/create', [FilesController::class, 'create'])
    ->name('files.create')
    ->middleware('auth');

Route::post('files', [FilesController::class, 'store'])
    ->name('files.store')
    ->middleware('auth');

Route::get('files/{file}', [FilesController::class, 'show'])
    ->name('files.show')
    ->middleware('auth');

Route::get('files/{file}/edit', [FilesController::class, 'edit'])
    ->name('files.edit')
    ->middleware('auth');

Route::put('files/{file}', [FilesController::class, 'update'])
    ->name('files.update')
    ->middleware('auth');

Route::delete('files/{file}', [FilesController::class, 'destroy'])
    ->name('files.destroy')
    ->middleware('auth');

Route::get('files/{file}/download', [FilesController::class, 'download'])
    ->name('files.download')
    ->middleware('auth');

Route::get('files/{file}/preview', [FilesController::class, 'preview'])
    ->name('files.preview')
    ->middleware('auth');

// Language switching
Route::get('lang/{locale}', function ($locale) {
    if (in_array($locale, ['vi', 'en'])) {
        // Set locale in session
        session(['locale' => $locale]);
        
        // Set locale for current request
        app()->setLocale($locale);
        
        // Get the previous URL or default to home
        $previousUrl = url()->previous() ?: route('dashboard');
        
        // Redirect to previous URL - this will trigger a full page reload with new locale
        return redirect($previousUrl);
    }
    return redirect()->back();
})->name('lang.switch');

// SMTP Settings
Route::get('smtp-settings', [\App\Http\Controllers\SMTPSettingsController::class, 'index'])
    ->name('smtp-settings.index')
    ->middleware('auth');

Route::post('smtp-settings', [\App\Http\Controllers\SMTPSettingsController::class, 'store'])
    ->name('smtp-settings.store')
    ->middleware('auth');

Route::post('smtp-settings/test', [\App\Http\Controllers\SMTPSettingsController::class, 'test'])
    ->name('smtp-settings.test')
    ->middleware('auth');

// Account Settings
Route::get('account-settings', [\App\Http\Controllers\AccountSettingsController::class, 'index'])
    ->name('account-settings.index')
    ->middleware('auth');

Route::put('account-settings', [\App\Http\Controllers\AccountSettingsController::class, 'update'])
    ->name('account-settings.update')
    ->middleware('auth');

// Chat Widgets (Admin)
Route::get('chat-widgets', [\App\Http\Controllers\ChatWidgetsController::class, 'index'])
    ->name('chat-widgets.index')
    ->middleware('auth');

Route::get('chat-widgets/create', [\App\Http\Controllers\ChatWidgetsController::class, 'create'])
    ->name('chat-widgets.create')
    ->middleware('auth');

Route::post('chat-widgets', [\App\Http\Controllers\ChatWidgetsController::class, 'store'])
    ->name('chat-widgets.store')
    ->middleware('auth');

Route::get('chat-widgets/{chatWidget}/preview', [\App\Http\Controllers\ChatWidgetsController::class, 'preview'])
    ->name('chat-widgets.preview')
    ->middleware('auth');

Route::get('chat-widgets/{chatWidget}/edit', [\App\Http\Controllers\ChatWidgetsController::class, 'edit'])
    ->name('chat-widgets.edit')
    ->middleware('auth');

Route::put('chat-widgets/{chatWidget}', [\App\Http\Controllers\ChatWidgetsController::class, 'update'])
    ->name('chat-widgets.update')
    ->middleware('auth');

Route::delete('chat-widgets/{chatWidget}', [\App\Http\Controllers\ChatWidgetsController::class, 'destroy'])
    ->name('chat-widgets.destroy')
    ->middleware('auth');

// Chat Widget Documents (RAG Knowledge Base)
Route::get('chat-widgets/{chatWidget}/documents', [\App\Http\Controllers\ChatWidgetDocumentsController::class, 'index'])
    ->name('chat-widgets.documents.index')
    ->middleware('auth');

Route::post('chat-widgets/{chatWidget}/documents', [\App\Http\Controllers\ChatWidgetDocumentsController::class, 'store'])
    ->name('chat-widgets.documents.store')
    ->middleware('auth');

Route::delete('chat-widgets/{chatWidget}/documents/{chatWidgetDocument}', [\App\Http\Controllers\ChatWidgetDocumentsController::class, 'destroy'])
    ->name('chat-widgets.documents.destroy')
    ->middleware('auth');

Route::post('chat-widgets/{chatWidget}/documents/{documentId}/toggle', [\App\Http\Controllers\ChatWidgetDocumentsController::class, 'toggle'])
    ->name('chat-widgets.documents.toggle')
    ->middleware('auth');

// Chat Conversations (Admin)
Route::get('chat-conversations', [\App\Http\Controllers\ChatConversationsController::class, 'index'])
    ->name('chat-conversations.index')
    ->middleware('auth');

Route::get('chat-conversations/{chatConversation}', [\App\Http\Controllers\ChatConversationsController::class, 'show'])
    ->name('chat-conversations.show')
    ->middleware('auth');

// Chat Widget API (Public - for embedding)
Route::prefix('api/chat')->middleware([\App\Http\Middleware\ValidateChatWidgetOrigin::class])->group(function () {
    Route::post('{widgetKey}/init', [\App\Http\Controllers\Api\ChatController::class, 'init'])
        ->name('api.chat.init')
        ->middleware('throttle:60,1'); // 60 requests per minute
    
    Route::post('{widgetKey}/message', [\App\Http\Controllers\Api\ChatController::class, 'sendMessage'])
        ->name('api.chat.message')
        ->middleware('throttle:30,1'); // 30 requests per minute
    
    Route::get('{widgetKey}/history', [\App\Http\Controllers\Api\ChatController::class, 'getHistory'])
        ->name('api.chat.history')
        ->middleware('throttle:60,1');
    
    Route::post('{widgetKey}/close', [\App\Http\Controllers\Api\ChatController::class, 'closeConversation'])
        ->name('api.chat.close')
        ->middleware('throttle:10,1');
});

// Chat Widget Embedding Script
Route::get('chat/widget/{widgetKey}.js', function ($widgetKey) {
    $widget = \App\Models\ChatWidget::where('widget_key', $widgetKey)
        ->where('is_active', true)
        ->firstOrFail();

    $script = view('chat-widget-embed', [
        'widgetKey' => $widgetKey,
        'apiUrl' => url('/api/chat'),
    ])->render();

    return response($script, 200, [
        'Content-Type' => 'application/javascript',
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->name('chat.widget.embed');

// Chat Widget Preview Script (bypasses is_active check)
Route::get('chat/widget-preview/{widgetKey}.js', function ($widgetKey) {
    $widget = \App\Models\ChatWidget::where('widget_key', $widgetKey)
        ->firstOrFail();

    // Check if user owns this widget
    if (!auth()->check() || auth()->user()->account_id !== $widget->account_id) {
        abort(403);
    }

    $script = view('chat-widget-embed', [
        'widgetKey' => $widgetKey,
        'apiUrl' => url('/api/chat'),
    ])->render();

    return response($script, 200, [
        'Content-Type' => 'application/javascript',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
    ]);
})->name('chat.widget.preview')->middleware('auth');

// Email Marketing - Templates
Route::resource('email-templates', EmailTemplatesController::class)->middleware('auth');

// Email Marketing - Lists
Route::resource('email-lists', EmailListsController::class)->middleware('auth');
Route::post('email-lists/{emailList}/contacts', [EmailListsController::class, 'addContact'])
    ->name('email-lists.contacts.add')
    ->middleware('auth');
Route::delete('email-lists/{emailList}/contacts/{emailListContact}', [EmailListsController::class, 'removeContact'])
    ->name('email-lists.contacts.remove')
    ->middleware('auth');

// Email Marketing - Campaigns
Route::resource('email-campaigns', EmailCampaignsController::class)->middleware('auth');
Route::post('email-campaigns/{emailCampaign}/send', [EmailCampaignsController::class, 'send'])
    ->name('email-campaigns.send')
    ->middleware('auth');

// Email Marketing - Automations
Route::resource('email-automations', EmailAutomationsController::class)->middleware('auth');
Route::post('email-automations/{emailAutomation}/activate', [EmailAutomationsController::class, 'activate'])
    ->name('email-automations.activate')
    ->middleware('auth');
Route::post('email-automations/{emailAutomation}/pause', [EmailAutomationsController::class, 'pause'])
    ->name('email-automations.pause')
    ->middleware('auth');

// Email Tracking (Public - no auth required)
Route::get('email/track/open/{messageId}', [\App\Http\Controllers\Api\EmailTrackingController::class, 'trackOpen'])
    ->name('email.track.open');
Route::get('email/track/click/{messageId}', [\App\Http\Controllers\Api\EmailTrackingController::class, 'trackClick'])
    ->name('email.track.click');
Route::get('email/unsubscribe/{token}', [\App\Http\Controllers\Api\EmailTrackingController::class, 'unsubscribe'])
    ->name('email.unsubscribe');

// Permissions & Roles
Route::resource('permissions', PermissionsController::class)->middleware('auth');
Route::resource('roles', RolesController::class)->middleware('auth');

// Images

Route::get('/img/{path}', [ImagesController::class, 'show'])
    ->where('path', '.*')
    ->name('image');
