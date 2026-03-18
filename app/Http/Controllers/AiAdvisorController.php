<?php

namespace App\Http\Controllers;

use App\Services\AiAdvisorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiAdvisorController extends Controller
{
    public function __construct(private AiAdvisorService $service) {}

    /**
     * GET /api/ai-advisor/briefing
     */
    public function briefing(): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        return response()->json($this->service->generateDailyBriefing($accountId));
    }

    /**
     * GET /api/ai-advisor/cashflow-projection
     */
    public function cashflowProjection(): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        return response()->json($this->service->getPredictiveCashflowStatus($accountId));
    /**
     * POST /api/ai-advisor/simulate
     */
    public function simulate(Request $request): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        $params = $request->only(['win_rate_boost', 'hiring_count']);
        return response()->json($this->service->runWhatIfSimulation($accountId, $params));
    }
}

