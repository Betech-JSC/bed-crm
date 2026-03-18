<?php

namespace App\Http\Controllers;

use App\Models\FinancialTransaction;
use App\Services\FinancialAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class FinancialController extends Controller
{
    public function __construct(private FinancialAnalyticsService $analyticsService) {}

    /**
     * Financial Overview Dashboard.
     */
    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;

        $analytics = $this->analyticsService->getDashboardAnalytics($accountId);

        return Inertia::render('Finance/Dashboard', [
            'analytics' => $analytics,
        ]);
    }

    /**
     * Transactions list with filters.
     */
    public function transactions(Request $request): Response
    {
        $accountId = Auth::user()->account_id;

        $transactions = FinancialTransaction::where('account_id', $accountId)
            ->with('recorder:id,first_name,last_name')
            ->filter($request->only('search', 'type', 'category', 'date_from', 'date_to'))
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'category' => $t->category,
                'category_label' => FinancialTransaction::getAllCategories()[$t->category] ?? $t->category,
                'description' => $t->description,
                'amount' => (float) $t->amount,
                'signed_amount' => $t->getSignedAmount(),
                'transaction_date' => $t->transaction_date->format('Y-m-d'),
                'reference' => $t->reference,
                'is_recurring' => $t->is_recurring,
                'recurring_period' => $t->recurring_period,
                'notes' => $t->notes,
                'recorder' => $t->recorder?->name ?? null,
                'created_at' => $t->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Finance/Transactions', [
            'transactions' => $transactions,
            'filters' => $request->only('search', 'type', 'category', 'date_from', 'date_to'),
            'incomeCategories' => FinancialTransaction::getIncomeCategories(),
            'expenseCategories' => FinancialTransaction::getExpenseCategories(),
        ]);
    }

    /**
     * Store a new financial transaction.
     */
    public function store(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'type' => 'required|in:income,expense',
            'category' => 'required|string|max:50',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'is_recurring' => 'boolean',
            'recurring_period' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:5000',
        ]);

        $v['account_id'] = Auth::user()->account_id;
        $v['recorded_by'] = Auth::id();

        FinancialTransaction::create($v);

        return Redirect::back()->with('success', 'Transaction recorded.');
    }

    /**
     * Update a financial transaction.
     */
    public function update(Request $request, FinancialTransaction $transaction): RedirectResponse
    {
        $v = $request->validate([
            'type' => 'sometimes|in:income,expense',
            'category' => 'sometimes|string|max:50',
            'description' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0.01',
            'transaction_date' => 'sometimes|date',
            'reference' => 'nullable|string|max:100',
            'is_recurring' => 'boolean',
            'recurring_period' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:5000',
        ]);

        $transaction->update($v);

        return Redirect::back()->with('success', 'Transaction updated.');
    }

    /**
     * Delete a financial transaction.
     */
    public function destroy(FinancialTransaction $transaction): RedirectResponse
    {
        $transaction->delete();
        return Redirect::back()->with('success', 'Transaction deleted.');
    }

    /**
     * API: Get analytics for a specific period.
     */
    public function periodAnalytics(Request $request)
    {
        $accountId = Auth::user()->account_id;
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $summary = $this->analyticsService->getMonthlySummary($accountId, (int) $year, (int) $month);
        $expenses = $this->analyticsService->getExpenseBreakdown($accountId, (int) $year, (int) $month);
        $income = $this->analyticsService->getIncomeBreakdown($accountId, (int) $year, (int) $month);

        return response()->json([
            'summary' => $summary,
            'expense_breakdown' => $expenses,
            'income_breakdown' => $income,
        ]);
    }

    /**
     * API: Generate snapshot for a month.
     */
    public function generateSnapshot(Request $request)
    {
        $accountId = Auth::user()->account_id;
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $snapshot = $this->analyticsService->generateSnapshot($accountId, (int) $year, (int) $month);

        return response()->json($snapshot);
    }
}
