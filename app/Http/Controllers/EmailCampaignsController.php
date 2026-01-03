<?php

namespace App\Http\Controllers;

use App\Models\EmailCampaign;
use App\Services\EmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailCampaignsController extends Controller
{
    public function __construct(
        private EmailService $emailService
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('EmailCampaigns/Index', [
            'campaigns' => Auth::user()->account->emailCampaigns()
                ->with(['emailTemplate', 'emailList'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($campaign) => [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'email_list' => $campaign->emailList?->name,
                    'total_recipients' => $campaign->total_recipients,
                    'sent_count' => $campaign->sent_count,
                    'opened_count' => $campaign->opened_count,
                    'clicked_count' => $campaign->clicked_count,
                    'open_rate' => $campaign->open_rate,
                    'click_rate' => $campaign->click_rate,
                    'scheduled_at' => $campaign->scheduled_at?->format('Y-m-d H:i'),
                    'sent_at' => $campaign->sent_at?->format('Y-m-d H:i'),
                    'created_at' => $campaign->created_at->format('Y-m-d H:i'),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('EmailCampaigns/Create', [
            'templates' => Auth::user()->account->emailTemplates()
                ->where('is_active', true)
                ->get()
                ->map(fn ($template) => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'subject' => $template->subject,
                ]),
            'lists' => Auth::user()->account->emailLists()
                ->where('is_active', true)
                ->get()
                ->map(fn ($list) => [
                    'id' => $list->id,
                    'name' => $list->name,
                    'contacts_count' => $list->contacts_count,
                ]),
        ]);
    }

    public function store(): RedirectResponse
    {
        $validated = Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'email_template_id' => ['nullable', 'integer', 'exists:email_templates,id'],
            'subject' => ['required', 'string', 'max:500'],
            'body_html' => ['nullable', 'string'],
            'body_text' => ['nullable', 'string'],
            'email_list_id' => ['required', 'integer', 'exists:email_lists,id'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $validated['account_id'] = Auth::user()->account_id;
        $validated['created_by'] = Auth::id();
        $validated['status'] = $validated['scheduled_at'] 
            ? EmailCampaign::STATUS_SCHEDULED 
            : EmailCampaign::STATUS_DRAFT;

        $campaign = EmailCampaign::create($validated);

        return Redirect::route('email-campaigns.show', $campaign)->with('success', 'Email campaign created.');
    }

    public function show(EmailCampaign $emailCampaign): Response
    {
        // Ensure campaign belongs to user's account
        if ($emailCampaign->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $emailCampaign->updateStatistics();

        return Inertia::render('EmailCampaigns/Show', [
            'campaign' => [
                'id' => $emailCampaign->id,
                'name' => $emailCampaign->name,
                'description' => $emailCampaign->description,
                'subject' => $emailCampaign->subject,
                'body_html' => $emailCampaign->body_html,
                'body_text' => $emailCampaign->body_text,
                'status' => $emailCampaign->status,
                'email_list_id' => $emailCampaign->email_list_id,
                'email_list_name' => $emailCampaign->emailList?->name,
                'scheduled_at' => $emailCampaign->scheduled_at?->format('Y-m-d H:i'),
                'sent_at' => $emailCampaign->sent_at?->format('Y-m-d H:i'),
                'total_recipients' => $emailCampaign->total_recipients,
                'sent_count' => $emailCampaign->sent_count,
                'delivered_count' => $emailCampaign->delivered_count,
                'opened_count' => $emailCampaign->opened_count,
                'clicked_count' => $emailCampaign->clicked_count,
                'bounced_count' => $emailCampaign->bounced_count,
                'unsubscribed_count' => $emailCampaign->unsubscribed_count,
                'open_rate' => $emailCampaign->open_rate,
                'click_rate' => $emailCampaign->click_rate,
            ],
        ]);
    }

    public function edit(EmailCampaign $emailCampaign): Response
    {
        // Ensure campaign belongs to user's account
        if ($emailCampaign->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        return Inertia::render('EmailCampaigns/Edit', [
            'campaign' => [
                'id' => $emailCampaign->id,
                'name' => $emailCampaign->name,
                'description' => $emailCampaign->description,
                'email_template_id' => $emailCampaign->email_template_id,
                'subject' => $emailCampaign->subject,
                'body_html' => $emailCampaign->body_html,
                'body_text' => $emailCampaign->body_text,
                'email_list_id' => $emailCampaign->email_list_id,
                'scheduled_at' => $emailCampaign->scheduled_at?->format('Y-m-d\TH:i'),
                'status' => $emailCampaign->status,
            ],
            'templates' => Auth::user()->account->emailTemplates()
                ->where('is_active', true)
                ->get()
                ->map(fn ($template) => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'subject' => $template->subject,
                ]),
            'lists' => Auth::user()->account->emailLists()
                ->where('is_active', true)
                ->get()
                ->map(fn ($list) => [
                    'id' => $list->id,
                    'name' => $list->name,
                    'contacts_count' => $list->contacts_count,
                ]),
        ]);
    }

    public function update(EmailCampaign $emailCampaign): RedirectResponse
    {
        // Ensure campaign belongs to user's account
        if ($emailCampaign->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        // Can't edit if already sent
        if ($emailCampaign->status === EmailCampaign::STATUS_SENT) {
            return Redirect::back()->with('error', 'Cannot edit a sent campaign.');
        }

        $validated = Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'email_template_id' => ['nullable', 'integer', 'exists:email_templates,id'],
            'subject' => ['required', 'string', 'max:500'],
            'body_html' => ['nullable', 'string'],
            'body_text' => ['nullable', 'string'],
            'email_list_id' => ['required', 'integer', 'exists:email_lists,id'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        if ($validated['scheduled_at'] && !$emailCampaign->scheduled_at) {
            $validated['status'] = EmailCampaign::STATUS_SCHEDULED;
        }

        $emailCampaign->update($validated);

        return Redirect::route('email-campaigns.show', $emailCampaign)->with('success', 'Email campaign updated.');
    }

    public function send(EmailCampaign $emailCampaign): RedirectResponse
    {
        // Ensure campaign belongs to user's account
        if ($emailCampaign->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        if ($emailCampaign->status !== EmailCampaign::STATUS_DRAFT && 
            $emailCampaign->status !== EmailCampaign::STATUS_SCHEDULED) {
            return Redirect::back()->with('error', 'Campaign cannot be sent in current status.');
        }

        // Send campaign (in production, this should be queued)
        $this->emailService->sendCampaign($emailCampaign);

        return Redirect::back()->with('success', 'Campaign sent successfully.');
    }

    public function destroy(EmailCampaign $emailCampaign): RedirectResponse
    {
        // Ensure campaign belongs to user's account
        if ($emailCampaign->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        if ($emailCampaign->status === EmailCampaign::STATUS_SENDING) {
            return Redirect::back()->with('error', 'Cannot delete a campaign that is currently sending.');
        }

        $emailCampaign->delete();

        return Redirect::route('email-campaigns.index')->with('success', 'Email campaign deleted.');
    }
}
