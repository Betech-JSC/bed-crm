<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailContactBehavior;
use App\Models\EmailContactScore;
use App\Models\EmailRevenueAttribution;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmailAnalyticsController extends Controller
{
    /**
     * Revenue attribution summary across all models
     */
    public function attributionSummary(): JsonResponse
    {
        $accountId = Auth::user()->account_id;

        $summary = EmailRevenueAttribution::where('account_id', $accountId)
            ->select(
                'attribution_model',
                DB::raw('SUM(attributed_value) as total_attributed'),
                DB::raw('COUNT(DISTINCT deal_id) as deals_count'),
                DB::raw('AVG(days_to_conversion) as avg_days_to_conversion'),
                DB::raw('AVG(touchpoints_count) as avg_touchpoints'),
            )
            ->groupBy('attribution_model')
            ->get();

        $totalRevenue = EmailRevenueAttribution::where('account_id', $accountId)
            ->where('attribution_model', 'last_touch')
            ->sum('attributed_value');

        return response()->json([
            'total_attributed_revenue' => $totalRevenue,
            'by_model' => $summary,
            'top_campaigns' => EmailRevenueAttribution::where('account_id', $accountId)
                ->whereNotNull('email_campaign_id')
                ->where('attribution_model', 'last_touch')
                ->select('email_campaign_id', DB::raw('SUM(attributed_value) as revenue'))
                ->groupBy('email_campaign_id')
                ->orderByDesc('revenue')
                ->limit(10)
                ->with('campaign:id,name')
                ->get(),
        ]);
    }

    /**
     * Revenue attribution broken down by campaign
     */
    public function byCampaign(): JsonResponse
    {
        $accountId = Auth::user()->account_id;

        $data = EmailRevenueAttribution::where('account_id', $accountId)
            ->whereNotNull('email_campaign_id')
            ->where('attribution_model', request('model', 'last_touch'))
            ->select(
                'email_campaign_id',
                DB::raw('SUM(attributed_value) as attributed_revenue'),
                DB::raw('COUNT(*) as conversions'),
                DB::raw('AVG(days_to_conversion) as avg_days'),
            )
            ->groupBy('email_campaign_id')
            ->orderByDesc('attributed_revenue')
            ->limit(20)
            ->get();

        return response()->json($data);
    }

    /**
     * Contact engagement score distribution
     */
    public function engagementScores(): JsonResponse
    {
        $accountId = Auth::user()->account_id;

        $distribution = EmailContactScore::where('account_id', $accountId)
            ->select('engagement_level', DB::raw('COUNT(*) as count'))
            ->groupBy('engagement_level')
            ->get();

        $topEngaged = EmailContactScore::where('account_id', $accountId)
            ->orderByDesc('engagement_score')
            ->limit(20)
            ->get(['email', 'engagement_score', 'engagement_level', 'last_engaged_at', 'emails_opened', 'emails_clicked']);

        return response()->json([
            'distribution' => $distribution,
            'top_engaged' => $topEngaged,
            'avg_score' => EmailContactScore::where('account_id', $accountId)->avg('engagement_score'),
        ]);
    }

    /**
     * Recent behavior events
     */
    public function behaviors(): JsonResponse
    {
        $accountId = Auth::user()->account_id;

        $behaviors = EmailContactBehavior::where('account_id', $accountId)
            ->orderByDesc('occurred_at')
            ->limit(50)
            ->get(['email', 'event_type', 'event_source', 'device_type', 'occurred_at']);

        $eventSummary = EmailContactBehavior::where('account_id', $accountId)
            ->where('occurred_at', '>=', now()->subDays(30))
            ->select('event_type', DB::raw('COUNT(*) as count'))
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->get();

        return response()->json([
            'recent' => $behaviors,
            'summary_30d' => $eventSummary,
        ]);
    }
}
