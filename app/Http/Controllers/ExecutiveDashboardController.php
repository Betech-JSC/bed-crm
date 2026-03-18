<?php

namespace App\Http\Controllers;

use App\Services\ExecutiveDashboardService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ExecutiveDashboardController extends Controller
{
    public function __construct(
        private ExecutiveDashboardService $dashboardService
    ) {}

    /**
     * Executive Dashboard page.
     */
    public function index(): Response
    {
        $accountId = Auth::user()->account_id;
        $metrics = $this->dashboardService->getMetrics($accountId);

        return Inertia::render('ExecutiveDashboard/Index', [
            'metrics' => $metrics,
        ]);
    }

    /**
     * API endpoint for real-time refresh.
     */
    public function refresh(): \Illuminate\Http\JsonResponse
    {
        $accountId = Auth::user()->account_id;

        // Invalidate cache and re-fetch
        $this->dashboardService->invalidateCache($accountId);
        $metrics = $this->dashboardService->getMetrics($accountId);

        return response()->json($metrics);
    }
}
