<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $accountId = auth()->user()->account_id;
        $query = Contract::where('account_id', $accountId)->with(['customer', 'creator']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('contract_number', 'like', "%{$s}%")
                  ->orWhere('title', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('type')) $query->where('contract_type', $request->type);

        $contracts = $query->latest()->paginate(30)->through(fn ($c) => [
            'id' => $c->id,
            'contract_number' => $c->contract_number,
            'title' => $c->title,
            'status' => $c->status,
            'status_label' => $c->status_label,
            'contract_type' => $c->contract_type,
            'type_label' => $c->type_label,
            'value' => $c->value,
            'currency' => $c->currency,
            'customer_id' => $c->customer_id,
            'customer_name' => $c->customer?->name ?? '—',
            'deal_id' => $c->deal_id,
            'quotation_id' => $c->quotation_id,
            'creator_name' => $c->creator?->name ?? '—',
            'start_date' => $c->start_date?->format('Y-m-d'),
            'end_date' => $c->end_date?->format('Y-m-d'),
            'days_remaining' => $c->days_remaining,
            'is_active_contract' => $c->is_active_contract,
            'payment_terms' => $c->payment_terms,
            'scope_of_work' => $c->scope_of_work,
            'terms_conditions' => $c->terms_conditions,
            'auto_renew' => $c->auto_renew,
        ]);

        $stats = [
            'total' => Contract::where('account_id', $accountId)->count(),
            'active' => Contract::where('account_id', $accountId)->where('status', 'active')->count(),
            'pending' => Contract::where('account_id', $accountId)->where('status', 'pending_approval')->count(),
            'total_value' => Contract::where('account_id', $accountId)->whereIn('status', ['active', 'completed'])->sum('value'),
        ];

        return Inertia::render('Contracts/Index', [
            'contracts' => $contracts,
            'filters' => $request->only(['search', 'status', 'type']),
            'stats' => $stats,
            'customers' => Customer::where('account_id', $accountId)->select('id', 'name')->get(),
            'deals' => Deal::where('account_id', $accountId)->select('id', 'name')->get(),
            'quotations' => Quotation::where('account_id', $accountId)->whereIn('status', ['accepted', 'approved'])->select('id', 'quote_number', 'title', 'total')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'contract_type' => 'required|in:one_time,recurring,retainer,project_based',
            'customer_id' => 'nullable|integer',
            'deal_id' => 'nullable|integer',
            'quotation_id' => 'nullable|integer',
            'value' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'payment_terms' => 'nullable|string',
            'scope_of_work' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'auto_renew' => 'boolean',
        ]);

        $accountId = auth()->user()->account_id;
        $validated['account_id'] = $accountId;
        $validated['contract_number'] = Contract::generateNumber($accountId);
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'draft';

        Contract::create($validated);

        return back()->with('success', 'Đã tạo hợp đồng.');
    }

    public function update(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'contract_type' => 'required|in:one_time,recurring,retainer,project_based',
            'customer_id' => 'nullable|integer',
            'deal_id' => 'nullable|integer',
            'quotation_id' => 'nullable|integer',
            'value' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'payment_terms' => 'nullable|string',
            'scope_of_work' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'auto_renew' => 'boolean',
        ]);

        $contract->update($validated);

        return back()->with('success', 'Đã cập nhật hợp đồng.');
    }

    public function destroy(Contract $contract)
    {
        $contract->delete();
        return back()->with('success', 'Đã xóa hợp đồng.');
    }

    public function submitApproval(Contract $contract)
    {
        $contract->update(['status' => 'pending_approval']);
        return back()->with('success', 'Đã gửi hợp đồng để phê duyệt.');
    }
}
