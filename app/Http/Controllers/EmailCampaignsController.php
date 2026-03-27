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
        $accountId = Auth::user()->account_id;
        $baseQuery = EmailCampaign::where('account_id', $accountId);

        // Stats (before filtering)
        $allCampaigns = (clone $baseQuery)->get();
        $stats = [
            'total' => $allCampaigns->count(),
            'total_sent' => $allCampaigns->sum('sent_count'),
            'delivered' => $allCampaigns->sum('delivered_count'),
            'avg_open_rate' => $allCampaigns->where('sent_count', '>', 0)->avg('open_rate') ? round($allCampaigns->where('sent_count', '>', 0)->avg('open_rate'), 1) : 0,
            'avg_click_rate' => $allCampaigns->where('sent_count', '>', 0)->avg('click_rate') ? round($allCampaigns->where('sent_count', '>', 0)->avg('click_rate'), 1) : 0,
            'by_status' => $allCampaigns->groupBy('status')->map->count(),
        ];

        $campaigns = $baseQuery
            ->with(['emailTemplate:id,name', 'emailList:id,name'])
            ->when(Request::input('search'), function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->when(Request::input('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (EmailCampaign $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'subject' => $c->subject,
                'status' => $c->status,
                'email_list' => $c->emailList?->name,
                'total_recipients' => $c->total_recipients,
                'sent_count' => $c->sent_count,
                'opened_count' => $c->opened_count,
                'clicked_count' => $c->clicked_count,
                'open_rate' => $c->open_rate,
                'click_rate' => $c->click_rate,
                'scheduled_at' => $c->scheduled_at?->format('d/m/Y H:i'),
                'sent_at' => $c->sent_at?->format('d/m/Y H:i'),
                'created_at' => $c->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('EmailCampaigns/Index', [
            'campaigns' => $campaigns,
            'stats' => $stats,
            'filters' => Request::only('search', 'status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('EmailCampaigns/Create', [
            'templates' => Auth::user()->account->emailTemplates()
                ->where('is_active', true)
                ->get()
                ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'subject' => $t->subject]),
            'lists' => Auth::user()->account->emailLists()
                ->where('is_active', true)
                ->get()
                ->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'contacts_count' => $l->contacts_count]),
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

        return Redirect::route('email-campaigns.show', $campaign)->with('success', 'Campaign đã tạo thành công.');
    }

    public function show(EmailCampaign $emailCampaign): Response
    {
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
                'scheduled_at' => $emailCampaign->scheduled_at?->format('d/m/Y H:i'),
                'sent_at' => $emailCampaign->sent_at?->format('d/m/Y H:i'),
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
                ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'subject' => $t->subject]),
            'lists' => Auth::user()->account->emailLists()
                ->where('is_active', true)
                ->get()
                ->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'contacts_count' => $l->contacts_count]),
        ]);
    }

    public function update(EmailCampaign $emailCampaign): RedirectResponse
    {
        if ($emailCampaign->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        if ($emailCampaign->status === EmailCampaign::STATUS_SENT) {
            return Redirect::back()->with('error', 'Không thể chỉnh sửa campaign đã gửi.');
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

        return Redirect::route('email-campaigns.show', $emailCampaign)->with('success', 'Campaign đã cập nhật.');
    }

    public function send(EmailCampaign $emailCampaign): RedirectResponse
    {
        if ($emailCampaign->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        if ($emailCampaign->status !== EmailCampaign::STATUS_DRAFT &&
            $emailCampaign->status !== EmailCampaign::STATUS_SCHEDULED) {
            return Redirect::back()->with('error', 'Campaign không thể gửi ở trạng thái hiện tại.');
        }

        $this->emailService->sendCampaign($emailCampaign);

        return Redirect::back()->with('success', 'Campaign đã gửi thành công!');
    }

    public function destroy(EmailCampaign $emailCampaign): RedirectResponse
    {
        if ($emailCampaign->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        if ($emailCampaign->status === EmailCampaign::STATUS_SENDING) {
            return Redirect::back()->with('error', 'Không thể xóa campaign đang gửi.');
        }

        $emailCampaign->delete();

        return Redirect::route('email-campaigns.index')->with('success', 'Campaign đã xóa.');
    }
}
