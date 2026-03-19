<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrgPerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrgReportingController extends Controller
{
    public function __construct(private OrgPerformanceService $performanceService) {}

    /**
     * GET /api/org-reporting/revenue-by-department
     */
    public function revenueByDepartment(Request $request): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        $periodStart = $request->get('start', now()->startOfMonth()->toDateString());
        $periodEnd = $request->get('end', now()->endOfMonth()->toDateString());

        return response()->json(
            $this->performanceService->revenueByDepartment($accountId, $periodStart, $periodEnd)
        );
    }

    /**
     * GET /api/org-reporting/revenue-by-team
     */
    public function revenueByTeam(Request $request): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        $periodStart = $request->get('start', now()->startOfMonth()->toDateString());
        $periodEnd = $request->get('end', now()->endOfMonth()->toDateString());

        return response()->json(
            $this->performanceService->revenueByTeam($accountId, $periodStart, $periodEnd)
        );
    }

    /**
     * GET /api/org-reporting/cost-by-department
     */
    public function costByDepartment(Request $request): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        $periodLabel = $request->get('period', now()->format('Y-m'));

        return response()->json(
            $this->performanceService->costByDepartment($accountId, $periodLabel)
        );
    }

    /**
     * GET /api/org-reporting/cost-by-category
     */
    public function costByCategory(Request $request): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        $periodLabel = $request->get('period', now()->format('Y-m'));

        return response()->json(
            $this->performanceService->costByCategory($accountId, $periodLabel)
        );
    }

    /**
     * GET /api/org-reporting/profit-by-team
     */
    public function profitByTeam(Request $request): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        $periodStart = $request->get('start', now()->startOfMonth()->toDateString());
        $periodEnd = $request->get('end', now()->endOfMonth()->toDateString());
        $periodLabel = $request->get('period', now()->format('Y-m'));

        return response()->json(
            $this->performanceService->profitByTeam($accountId, $periodStart, $periodEnd, $periodLabel)
        );
    }

    /**
     * GET /api/org-reporting/employee-ranking
     */
    public function employeeRanking(Request $request): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        $periodStart = $request->get('start', now()->startOfMonth()->toDateString());
        $periodEnd = $request->get('end', now()->endOfMonth()->toDateString());
        $departmentId = $request->get('department_id');

        return response()->json(
            $this->performanceService->employeePerformanceRanking(
                $accountId, $periodStart, $periodEnd, $departmentId
            )
        );
    }
}
