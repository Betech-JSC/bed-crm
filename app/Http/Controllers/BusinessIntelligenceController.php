<?php

namespace App\Http\Controllers;

use App\Services\BusinessIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class BusinessIntelligenceController extends Controller
{
    public function __construct(private BusinessIntelligenceService $biService) {}

    /**
     * AI Business Intelligence Dashboard for CEO.
     */
    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;
        $analysis = $this->biService->analyze($accountId);

        return Inertia::render('Intelligence/Dashboard', [
            'analysis' => $analysis,
        ]);
    }

    /**
     * API: Get revenue prediction only.
     */
    public function prediction(Request $request)
    {
        $accountId = Auth::user()->account_id;
        return response()->json(
            $this->biService->predictNextMonthRevenue($accountId)
        );
    }

    /**
     * API: Re-run full analysis.
     */
    public function refresh(Request $request)
    {
        $accountId = Auth::user()->account_id;
        return response()->json(
            $this->biService->analyze($accountId)
        );
    }
}
