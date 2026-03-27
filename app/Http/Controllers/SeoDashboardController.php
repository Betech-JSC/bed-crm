<?php

namespace App\Http\Controllers;

use App\Models\SeoAuditIssue;
use App\Models\SeoKeyword;
use App\Models\SeoRankHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SeoDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;
        $filters = $request->only('search', 'status', 'difficulty');

        $keywords = SeoKeyword::where('account_id', $accountId)
            ->filter($filters)
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($k) => [
                'id' => $k->id,
                'keyword' => $k->keyword,
                'url' => $k->url,
                'current_rank' => $k->current_rank,
                'previous_rank' => $k->previous_rank,
                'best_rank' => $k->best_rank,
                'rank_change' => $k->rank_change,
                'rank_trend' => $k->rank_trend,
                'search_volume' => $k->search_volume,
                'difficulty' => $k->difficulty,
                'status' => $k->status,
                'last_checked_at' => $k->last_checked_at?->format('d/m/Y'),
            ]);

        // Stats
        $totalKw = SeoKeyword::where('account_id', $accountId)->count();
        $top10 = SeoKeyword::where('account_id', $accountId)->where('current_rank', '<=', 10)->count();
        $top30 = SeoKeyword::where('account_id', $accountId)->where('current_rank', '<=', 30)->count();
        $improved = SeoKeyword::where('account_id', $accountId)->whereNotNull('previous_rank')
            ->whereColumn('current_rank', '<', 'previous_rank')->count();

        // Audit issues summary
        $auditSummary = SeoAuditIssue::where('account_id', $accountId)
            ->where('status', 'open')
            ->select('severity', DB::raw('COUNT(*) as total'))
            ->groupBy('severity')
            ->pluck('total', 'severity');

        $auditIssues = SeoAuditIssue::where('account_id', $accountId)
            ->filter($request->only('status', 'severity', 'issue_type'))
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'page_url' => $i->page_url,
                'issue_type' => $i->issue_type,
                'severity' => $i->severity,
                'description' => $i->description,
                'status' => $i->status,
                'recommendation' => $i->recommendation,
            ]);

        // Rank distribution
        $rankDistribution = [
            'top3' => SeoKeyword::where('account_id', $accountId)->where('current_rank', '<=', 3)->count(),
            'top10' => $top10,
            'top30' => $top30,
            'top50' => SeoKeyword::where('account_id', $accountId)->where('current_rank', '<=', 50)->count(),
            'beyond' => SeoKeyword::where('account_id', $accountId)->where('current_rank', '>', 50)->orWhereNull('current_rank')->where('account_id', $accountId)->count(),
        ];

        return Inertia::render('SeoDashboard/Index', [
            'keywords' => $keywords,
            'stats' => [
                'total' => $totalKw, 'top10' => $top10, 'top30' => $top30,
                'improved' => $improved,
                'audit_critical' => $auditSummary['critical'] ?? 0,
                'audit_warning' => $auditSummary['warning'] ?? 0,
            ],
            'auditIssues' => $auditIssues,
            'issueTypes' => SeoAuditIssue::getIssueTypes(),
            'rankDistribution' => $rankDistribution,
            'filters' => $filters,
        ]);
    }

    public function storeKeyword(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'url' => 'nullable|url|max:500',
            'search_volume' => 'nullable|integer|min:0',
            'difficulty' => 'nullable|in:easy,medium,hard',
            'current_rank' => 'nullable|integer|min:1|max:999',
        ]);

        $user = Auth::user();
        $kw = SeoKeyword::create([
            'account_id' => $user->account_id,
            ...$validated,
            'best_rank' => $validated['current_rank'] ?? null,
        ]);

        if ($kw->current_rank) {
            SeoRankHistory::create([
                'seo_keyword_id' => $kw->id,
                'rank' => $kw->current_rank,
                'checked_date' => now()->toDateString(),
            ]);
        }

        return redirect()->back()->with('success', 'Đã thêm keyword!');
    }

    public function updateKeyword(Request $request, SeoKeyword $seoKeyword): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'url' => 'nullable|url|max:500',
            'search_volume' => 'nullable|integer|min:0',
            'difficulty' => 'nullable|in:easy,medium,hard',
            'current_rank' => 'nullable|integer|min:1|max:999',
            'status' => 'nullable|in:tracking,paused,achieved',
        ]);

        // Track rank change
        if (isset($validated['current_rank']) && $validated['current_rank'] != $seoKeyword->current_rank) {
            $validated['previous_rank'] = $seoKeyword->current_rank;
            if (!$seoKeyword->best_rank || $validated['current_rank'] < $seoKeyword->best_rank) {
                $validated['best_rank'] = $validated['current_rank'];
            }
            $validated['last_checked_at'] = now();

            SeoRankHistory::create([
                'seo_keyword_id' => $seoKeyword->id,
                'rank' => $validated['current_rank'],
                'checked_date' => now()->toDateString(),
            ]);
        }

        $seoKeyword->update($validated);
        return redirect()->back()->with('success', 'Đã cập nhật!');
    }

    public function destroyKeyword(SeoKeyword $seoKeyword): \Illuminate\Http\RedirectResponse
    {
        $seoKeyword->delete();
        return redirect()->back()->with('success', 'Đã xóa.');
    }

    public function fixIssue(SeoAuditIssue $seoAuditIssue): \Illuminate\Http\RedirectResponse
    {
        $seoAuditIssue->update(['status' => 'fixed']);
        return redirect()->back()->with('success', 'Đã đánh dấu fixed.');
    }
}
