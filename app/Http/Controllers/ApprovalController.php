<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\Contract;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $accountId = auth()->user()->account_id;

        // Get pending quotations
        $quotationQuery = Quotation::where('account_id', $accountId)
            ->where('status', 'pending_approval')
            ->with(['customer', 'lead', 'creator']);

        $contractQuery = Contract::where('account_id', $accountId)
            ->where('status', 'pending_approval')
            ->with(['customer', 'creator']);

        $pendingQuotations = $quotationQuery->latest()->get()->map(fn ($q) => [
            'id' => $q->id,
            'type' => 'quotation',
            'type_label' => 'Báo giá',
            'number' => $q->quote_number,
            'title' => $q->title,
            'total' => $q->total,
            'currency' => $q->currency,
            'customer_name' => $q->customer?->name ?? $q->lead?->company ?? '—',
            'creator_name' => $q->creator?->name ?? '—',
            'created_at' => $q->created_at->format('d/m/Y H:i'),
            'valid_until' => $q->valid_until?->format('d/m/Y'),
        ]);

        $pendingContracts = $contractQuery->latest()->get()->map(fn ($c) => [
            'id' => $c->id,
            'type' => 'contract',
            'type_label' => 'Hợp đồng',
            'number' => $c->contract_number,
            'title' => $c->title,
            'total' => $c->value,
            'currency' => $c->currency,
            'customer_name' => $c->customer?->name ?? '—',
            'creator_name' => $c->creator?->name ?? '—',
            'created_at' => $c->created_at->format('d/m/Y H:i'),
            'contract_type' => $c->type_label,
        ]);

        // Recently processed
        $recentlyApproved = collect()
            ->merge(
                Quotation::where('account_id', $accountId)
                    ->whereIn('status', ['approved', 'rejected'])
                    ->whereNotNull('approved_at')
                    ->latest('approved_at')
                    ->limit(10)
                    ->with(['creator', 'approver'])
                    ->get()
                    ->map(fn ($q) => [
                        'id' => $q->id,
                        'type' => 'quotation',
                        'type_label' => 'Báo giá',
                        'number' => $q->quote_number,
                        'title' => $q->title,
                        'status' => $q->status,
                        'status_label' => $q->status_label,
                        'total' => $q->total,
                        'approved_by' => $q->approver?->name ?? '—',
                        'approved_at' => $q->approved_at?->format('d/m/Y H:i'),
                    ])
            )
            ->merge(
                Contract::where('account_id', $accountId)
                    ->whereIn('status', ['approved', 'cancelled'])
                    ->whereNotNull('approved_at')
                    ->latest('approved_at')
                    ->limit(10)
                    ->with(['creator', 'approver'])
                    ->get()
                    ->map(fn ($c) => [
                        'id' => $c->id,
                        'type' => 'contract',
                        'type_label' => 'Hợp đồng',
                        'number' => $c->contract_number,
                        'title' => $c->title,
                        'status' => $c->status,
                        'status_label' => $c->status_label,
                        'total' => $c->value,
                        'approved_by' => $c->approver?->name ?? '—',
                        'approved_at' => $c->approved_at?->format('d/m/Y H:i'),
                    ])
            )
            ->sortByDesc('approved_at')
            ->take(15)
            ->values();

        $stats = [
            'pending_quotations' => $pendingQuotations->count(),
            'pending_contracts' => $pendingContracts->count(),
            'total_pending' => $pendingQuotations->count() + $pendingContracts->count(),
            'approved_this_month' => Quotation::where('account_id', $accountId)->where('status', 'approved')->whereMonth('approved_at', now()->month)->count()
                + Contract::where('account_id', $accountId)->where('status', 'approved')->whereMonth('approved_at', now()->month)->count(),
        ];

        return Inertia::render('Approvals/Index', [
            'pendingQuotations' => $pendingQuotations,
            'pendingContracts' => $pendingContracts,
            'recentlyProcessed' => $recentlyApproved,
            'stats' => $stats,
        ]);
    }

    public function approve(Request $request)
    {
        $request->validate([
            'type' => 'required|in:quotation,contract',
            'id' => 'required|integer',
        ]);

        if ($request->type === 'quotation') {
            $item = Quotation::findOrFail($request->id);
            $item->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        } else {
            $item = Contract::findOrFail($request->id);
            $item->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        }

        return back()->with('success', 'Đã phê duyệt thành công.');
    }

    public function reject(Request $request)
    {
        $request->validate([
            'type' => 'required|in:quotation,contract',
            'id' => 'required|integer',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($request->type === 'quotation') {
            $item = Quotation::findOrFail($request->id);
            $item->update([
                'status' => 'rejected',
                'rejection_reason' => $request->reason,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        } else {
            $item = Contract::findOrFail($request->id);
            $item->update([
                'status' => 'cancelled',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        }

        return back()->with('success', 'Đã từ chối.');
    }
}
