<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\UtmLink;
use App\Models\WebFormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UtmTrackingController extends Controller
{
    /**
     * Main page — UTM builder + attribution dashboard + saved links
     */
    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;
        $filters = $request->only('search', 'source', 'medium', 'campaign');

        // Saved UTM links
        $links = UtmLink::where('account_id', $accountId)
            ->filter($filters)
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'base_url' => $l->base_url,
                'utm_source' => $l->utm_source,
                'utm_medium' => $l->utm_medium,
                'utm_campaign' => $l->utm_campaign,
                'utm_term' => $l->utm_term,
                'utm_content' => $l->utm_content,
                'full_url' => $l->full_url,
                'clicks_count' => $l->clicks_count,
                'leads_count' => $l->leads_count,
                'conversion_rate' => $l->clicks_count > 0
                    ? round(($l->leads_count / $l->clicks_count) * 100, 1) : 0,
                'created_at' => $l->created_at->format('d/m/Y'),
            ]);

        // ── Attribution Analytics ──

        // Leads by source
        $leadsBySource = Lead::where('account_id', $accountId)
            ->select('source', DB::raw('COUNT(*) as total'))
            ->groupBy('source')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['source' => $r->source ?? 'unknown', 'total' => $r->total]);

        // Leads by UTM source (from web forms)
        $leadsByUtmSource = WebFormSubmission::where('account_id', $accountId)
            ->whereNotNull('utm_source')
            ->select('utm_source', DB::raw('COUNT(*) as total'))
            ->groupBy('utm_source')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['source' => $r->utm_source, 'total' => $r->total]);

        // Leads by UTM medium
        $leadsByUtmMedium = WebFormSubmission::where('account_id', $accountId)
            ->whereNotNull('utm_medium')
            ->select('utm_medium', DB::raw('COUNT(*) as total'))
            ->groupBy('utm_medium')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['medium' => $r->utm_medium, 'total' => $r->total]);

        // Leads by UTM campaign
        $leadsByCampaign = WebFormSubmission::where('account_id', $accountId)
            ->whereNotNull('utm_campaign')
            ->select('utm_campaign', DB::raw('COUNT(*) as total'))
            ->groupBy('utm_campaign')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['campaign' => $r->utm_campaign, 'total' => $r->total]);

        // Leads trend (last 30 days)
        $leadsTrend = Lead::where('account_id', $accountId)
            ->where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => ['date' => $r->date, 'total' => $r->total]);

        // Global stats
        $stats = [
            'total_leads' => Lead::where('account_id', $accountId)->count(),
            'this_month' => Lead::where('account_id', $accountId)->whereMonth('created_at', now()->month)->count(),
            'total_utm_links' => UtmLink::where('account_id', $accountId)->count(),
            'total_submissions' => WebFormSubmission::where('account_id', $accountId)->count(),
            'top_source' => $leadsBySource->first()['source'] ?? '—',
        ];

        // Distinct values for filters
        $distinctSources = UtmLink::where('account_id', $accountId)->distinct()->pluck('utm_source')->toArray();
        $distinctMediums = UtmLink::where('account_id', $accountId)->distinct()->pluck('utm_medium')->toArray();
        $distinctCampaigns = UtmLink::where('account_id', $accountId)->distinct()->pluck('utm_campaign')->toArray();

        return Inertia::render('UtmTracking/Index', [
            'links' => $links,
            'stats' => $stats,
            'attribution' => [
                'by_source' => $leadsBySource,
                'by_utm_source' => $leadsByUtmSource,
                'by_utm_medium' => $leadsByUtmMedium,
                'by_campaign' => $leadsByCampaign,
                'trend' => $leadsTrend,
            ],
            'sourcePresets' => UtmLink::getSourcePresets(),
            'mediumPresets' => UtmLink::getMediumPresets(),
            'distinctSources' => $distinctSources,
            'distinctMediums' => $distinctMediums,
            'distinctCampaigns' => $distinctCampaigns,
            'filters' => $filters,
        ]);
    }

    /**
     * Store a new UTM link
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'base_url' => 'required|url|max:2000',
            'utm_source' => 'required|string|max:100',
            'utm_medium' => 'required|string|max:100',
            'utm_campaign' => 'required|string|max:100',
            'utm_term' => 'nullable|string|max:100',
            'utm_content' => 'nullable|string|max:100',
        ]);

        $user = Auth::user();

        UtmLink::create([
            'account_id' => $user->account_id,
            'created_by' => $user->id,
            ...$validated,
        ]);

        return redirect()->route('utm-tracking')->with('success', 'Đã tạo UTM link!');
    }

    /**
     * Update UTM link
     */
    public function update(Request $request, UtmLink $utmLink): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'base_url' => 'required|url|max:2000',
            'utm_source' => 'required|string|max:100',
            'utm_medium' => 'required|string|max:100',
            'utm_campaign' => 'required|string|max:100',
            'utm_term' => 'nullable|string|max:100',
            'utm_content' => 'nullable|string|max:100',
        ]);

        $utmLink->update($validated);

        return redirect()->route('utm-tracking')->with('success', 'Đã cập nhật!');
    }

    /**
     * Delete UTM link
     */
    public function destroy(UtmLink $utmLink): \Illuminate\Http\RedirectResponse
    {
        $utmLink->delete();
        return redirect()->route('utm-tracking')->with('success', 'Đã xóa.');
    }
}
