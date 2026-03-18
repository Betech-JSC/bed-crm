<?php

namespace App\Http\Controllers;

use App\Models\SalesTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalesTargetsController extends Controller
{
    /** GET /sales-targets */
    public function index(Request $request): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        $year = $request->integer('year', now()->year);
        $userId = $request->integer('user_id');

        $query = SalesTarget::where('account_id', $accountId)->where('year', $year)->with('user:id,first_name,last_name');
        if ($userId) $query->where('user_id', $userId);

        return response()->json($query->get());
    }

    /** POST /sales-targets */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'year' => 'required|integer',
            'quarter' => 'nullable|integer|between:1,4',
            'month' => 'nullable|integer|between:1,12',
            'period_type' => 'required|in:month,quarter,year',
            'revenue_target' => 'required|numeric|min:0',
            'deals_target' => 'nullable|integer|min:0',
            'leads_target' => 'nullable|integer|min:0',
            'activities_target' => 'nullable|integer|min:0',
        ]);

        $validated['account_id'] = Auth::user()->account_id;
        $target = SalesTarget::updateOrCreate(
            ['account_id' => $validated['account_id'], 'user_id' => $validated['user_id'], 'year' => $validated['year'], 'month' => $validated['month'] ?? null, 'quarter' => $validated['quarter'] ?? null, 'period_type' => $validated['period_type']],
            $validated
        );

        return response()->json($target, 201);
    }

    /** PUT /sales-targets/{target} */
    public function update(Request $request, SalesTarget $target): JsonResponse
    {
        $target->update($request->only(['revenue_target', 'deals_target', 'leads_target', 'activities_target']));
        return response()->json($target);
    }
}
