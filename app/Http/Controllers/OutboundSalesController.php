<?php

namespace App\Http\Controllers;

use App\Models\OutboundCampaign;
use App\Models\OutboundCampaignLog;
use App\Services\OutboundSalesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class OutboundSalesController extends Controller
{
    public function __construct(
        private OutboundSalesService $outboundService,
    ) {}

    /**
     * Dashboard — shows all outbound campaigns with stats
     */
    public function index(): Response
    {
        $accountId = Auth::user()->account_id;

        $campaigns = OutboundCampaign::where('account_id', $accountId)
            ->with(['lead:id,name,email,phone,company,status,score'])
            ->when(Request::input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(Request::input('lead_status'), function ($q, $leadStatus) {
                match ($leadStatus) {
                    'new' => $q->where('email_opened', false)->where('link_clicked', false)->where('replied', false),
                    'contacted' => $q->where('current_step', '>', 0)->where('email_opened', false),
                    'engaged' => $q->where(function ($sub) {
                        $sub->where('email_opened', true)->orWhere('link_clicked', true);
                    })->where('replied', false),
                    'qualified' => $q->where('replied', true),
                    default => null,
                };
            })
            ->when(Request::input('search'), function ($q, $search) {
                $q->whereHas('lead', function ($leadQuery) use ($search) {
                    $leadQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (OutboundCampaign $campaign) => [
                'id' => $campaign->id,
                'status' => $campaign->status,
                'current_step' => $campaign->current_step,
                'step_name' => $campaign->step_name,
                'lead_score' => $campaign->lead_score,
                'lead_status' => $campaign->lead_status,
                'lead_status_label' => $campaign->lead_status_label,
                'lead_status_color' => $campaign->lead_status_color,
                'email_opened' => $campaign->email_opened,
                'link_clicked' => $campaign->link_clicked,
                'replied' => $campaign->replied,
                'first_email_sent_at' => $campaign->first_email_sent_at?->toISOString(),
                'next_action_at' => $campaign->next_action_at?->toISOString(),
                'completed_at' => $campaign->completed_at?->toISOString(),
                'created_at' => $campaign->created_at?->toISOString(),
                'lead' => $campaign->lead ? [
                    'id' => $campaign->lead->id,
                    'name' => $campaign->lead->name,
                    'email' => $campaign->lead->email,
                    'phone' => $campaign->lead->phone,
                    'company' => $campaign->lead->company,
                ] : null,
            ]);

        return Inertia::render('OutboundSales/Index', [
            'campaigns' => $campaigns,
            'stats' => $this->outboundService->getDashboardStats($accountId),
            'filters' => Request::only('search', 'status', 'lead_status'),
            'statuses' => OutboundCampaign::getStatuses(),
        ]);
    }

    /**
     * Show single campaign detail with activity timeline
     */
    public function show(OutboundCampaign $outboundCampaign): Response
    {
        $campaign = $outboundCampaign->load(['lead', 'logs']);

        return Inertia::render('OutboundSales/Show', [
            'campaign' => [
                'id' => $campaign->id,
                'status' => $campaign->status,
                'current_step' => $campaign->current_step,
                'step_name' => $campaign->step_name,
                'lead_score' => $campaign->lead_score,
                'score_breakdown' => $campaign->score_breakdown,
                'lead_status' => $campaign->lead_status,
                'lead_status_label' => $campaign->lead_status_label,
                'email_opened' => $campaign->email_opened,
                'link_clicked' => $campaign->link_clicked,
                'replied' => $campaign->replied,
                'first_email_sent_at' => $campaign->first_email_sent_at?->toISOString(),
                'next_action_at' => $campaign->next_action_at?->toISOString(),
                'completed_at' => $campaign->completed_at?->toISOString(),
                'created_at' => $campaign->created_at?->toISOString(),
                'lead' => $campaign->lead ? [
                    'id' => $campaign->lead->id,
                    'name' => $campaign->lead->name,
                    'email' => $campaign->lead->email,
                    'phone' => $campaign->lead->phone,
                    'company' => $campaign->lead->company,
                    'status' => $campaign->lead->status,
                    'score' => $campaign->lead->score,
                    'industry' => $campaign->lead->industry,
                ] : null,
            ],
            'logs' => $campaign->logs->map(fn (OutboundCampaignLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'action_label' => $log->action_label,
                'action_icon' => $log->action_icon,
                'action_color' => $log->action_color,
                'step_name' => $log->step_name,
                'channel' => $log->channel,
                'subject' => $log->subject,
                'content_preview' => $log->content_preview,
                'metadata' => $log->metadata,
                'created_at' => $log->created_at?->toISOString(),
            ]),
        ]);
    }

    /**
     * Pause an active campaign
     */
    public function pause(OutboundCampaign $outboundCampaign): RedirectResponse
    {
        $outboundCampaign->update(['status' => OutboundCampaign::STATUS_PAUSED]);

        return back()->with('success', 'Campaign paused.');
    }

    /**
     * Resume a paused campaign
     */
    public function resume(OutboundCampaign $outboundCampaign): RedirectResponse
    {
        $outboundCampaign->update([
            'status' => OutboundCampaign::STATUS_ACTIVE,
            'next_action_at' => now(),
        ]);

        return back()->with('success', 'Campaign resumed.');
    }

    /**
     * Cancel a campaign
     */
    public function cancel(OutboundCampaign $outboundCampaign): RedirectResponse
    {
        $outboundCampaign->update([
            'status' => OutboundCampaign::STATUS_CANCELLED,
            'next_action_at' => null,
        ]);

        return back()->with('success', 'Campaign cancelled.');
    }

    /**
     * Manually trigger outbound for a specific lead (API endpoint)
     */
    public function triggerForLead(\App\Models\Lead $lead): RedirectResponse
    {
        if (!$lead->email) {
            return back()->with('error', 'Lead must have an email to start outbound campaign.');
        }

        $campaign = $this->outboundService->initiateCampaign($lead);

        if ($campaign) {
            return back()->with('success', 'Outbound campaign started for ' . ($lead->company ?? $lead->name));
        }

        return back()->with('error', 'Failed to start outbound campaign.');
    }

    /**
     * Record email open (webhook endpoint)
     */
    public function trackOpen(OutboundCampaign $outboundCampaign): \Illuminate\Http\JsonResponse
    {
        $this->outboundService->recordEmailOpened($outboundCampaign);
        return response()->json(['ok' => true]);
    }

    /**
     * Record link click (webhook endpoint)
     */
    public function trackClick(OutboundCampaign $outboundCampaign): \Illuminate\Http\JsonResponse
    {
        $this->outboundService->recordLinkClicked($outboundCampaign);
        return response()->json(['ok' => true]);
    }

    /**
     * Record reply (webhook or manual)
     */
    public function markReplied(OutboundCampaign $outboundCampaign): RedirectResponse
    {
        $this->outboundService->recordReplied($outboundCampaign);
        return back()->with('success', 'Campaign marked as replied. Lead qualified!');
    }
}
