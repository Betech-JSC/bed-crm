<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Services\SalesIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalesIntelligenceController extends Controller
{
    public function __construct(private SalesIntelligenceService $service) {}

    /** GET /sales-intelligence/forecast */
    public function forecast(Request $request): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        $period = $request->input('period', now()->format('Y-m'));
        return response()->json($this->service->getRevenueForecast($accountId, null, $period));
    }

    /** GET /sales-intelligence/forecast/{userId} */
    public function forecastForRep(Request $request, int $userId): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        $period = $request->input('period', now()->format('Y-m'));
        return response()->json($this->service->getRevenueForecast($accountId, $userId, $period));
    }

    /** GET /sales-intelligence/velocity */
    public function velocity(): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        return response()->json($this->service->calculateSalesVelocity($accountId));
    }

    /** GET /sales-intelligence/velocity/{userId} */
    public function velocityForRep(int $userId): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        return response()->json($this->service->calculateSalesVelocity($accountId, $userId));
    }

    /** GET /sales-intelligence/risks */
    public function risks(): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        return response()->json($this->service->detectRisksAndRecommendations($accountId));
    }

    /** POST /sales-intelligence/deals/{deal}/health */
    public function dealHealth(Deal $deal): JsonResponse
    {
        $this->authorize('view', $deal);
        $health = $this->service->calculateDealHealth($deal);
        $deal->update(['health_score' => $health['score'], 'at_risk' => $health['at_risk']]);
        return response()->json($health);
    }

    /** POST /sales-intelligence/deals/{deal}/probability */
    public function updateProbability(Request $request, Deal $deal): JsonResponse
    {
        $this->authorize('update', $deal);
        $probability = $request->integer('probability') ?: $this->service->calculateWinProbability($deal);
        $deal->update([
            'win_probability' => $probability,
            'weighted_value' => round(($deal->value ?? 0) * $probability / 100, 2),
        ]);
        return response()->json(['win_probability' => $probability, 'weighted_value' => $deal->weighted_value]);
    }

    /** POST /sales-intelligence/refresh */
    public function refreshAll(): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        $updated = $this->service->refreshAllDeals($accountId);
        return response()->json(['updated' => $updated]);
    }

    /** GET /sales-intelligence/rep-kpis */
    public function myKPIs(Request $request): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        $period = $request->input('period', 'month');
        return response()->json($this->service->getRepKPIs($accountId, Auth::id(), $period));
    }

    /** GET /sales-intelligence/rep-kpis/{userId} */
    public function repKPIs(Request $request, int $userId): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        $period = $request->input('period', 'month');
        return response()->json($this->service->getRepKPIs($accountId, $userId, $period));
    }
}
