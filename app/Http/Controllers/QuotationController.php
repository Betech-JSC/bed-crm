<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Deal;
use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $accountId = auth()->user()->account_id;
        $query = Quotation::where('account_id', $accountId)->with(['customer', 'lead', 'creator']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('quote_number', 'like', "%{$s}%")
                  ->orWhere('title', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);

        $quotations = $query->latest()->paginate(30)->through(fn ($q) => [
            'id' => $q->id,
            'quote_number' => $q->quote_number,
            'title' => $q->title,
            'status' => $q->status,
            'status_label' => $q->status_label,
            'total' => $q->total,
            'currency' => $q->currency,
            'customer_name' => $q->customer?->name ?? $q->lead?->company ?? '—',
            'creator_name' => $q->creator?->name ?? '—',
            'issue_date' => $q->issue_date?->format('d/m/Y'),
            'valid_until' => $q->valid_until?->format('d/m/Y'),
            'is_expired' => $q->is_expired,
            'created_at' => $q->created_at->format('d/m/Y'),
        ]);

        $stats = [
            'total' => Quotation::where('account_id', $accountId)->count(),
            'draft' => Quotation::where('account_id', $accountId)->where('status', 'draft')->count(),
            'pending' => Quotation::where('account_id', $accountId)->where('status', 'pending_approval')->count(),
            'accepted' => Quotation::where('account_id', $accountId)->where('status', 'accepted')->count(),
            'total_value' => Quotation::where('account_id', $accountId)->whereIn('status', ['accepted', 'sent', 'approved'])->sum('total'),
        ];

        return Inertia::render('Quotations/Index', [
            'quotations' => $quotations,
            'filters' => $request->only(['search', 'status']),
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        $accountId = auth()->user()->account_id;
        return Inertia::render('Quotations/Create', [
            'customers' => Customer::where('account_id', $accountId)->select('id', 'name')->get(),
            'leads' => Lead::where('account_id', $accountId)->select('id', 'company')->get(),
            'deals' => Deal::where('account_id', $accountId)->select('id', 'title')->get(),
            'products' => Product::where('account_id', $accountId)->active()->select('id', 'name', 'sku', 'unit', 'unit_price', 'tax_rate', 'description')->get(),
            'nextNumber' => Quotation::generateNumber($accountId),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'customer_id' => 'nullable|integer',
            'lead_id' => 'nullable|integer',
            'deal_id' => 'nullable|integer',
            'issue_date' => 'nullable|date',
            'valid_until' => 'nullable|date',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|integer',
            'items.*.name' => 'required|string',
            'items.*.unit' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $accountId = auth()->user()->account_id;

        $quotation = Quotation::create([
            'account_id' => $accountId,
            'quote_number' => Quotation::generateNumber($accountId),
            'title' => $validated['title'],
            'customer_id' => $validated['customer_id'] ?? null,
            'lead_id' => $validated['lead_id'] ?? null,
            'deal_id' => $validated['deal_id'] ?? null,
            'issue_date' => $validated['issue_date'] ?? now(),
            'valid_until' => $validated['valid_until'] ?? now()->addDays(30),
            'discount_percent' => $validated['discount_percent'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'terms' => $validated['terms'] ?? null,
            'created_by' => auth()->id(),
            'status' => 'draft',
        ]);

        foreach ($validated['items'] as $i => $item) {
            $quotation->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'name' => $item['name'],
                'unit' => $item['unit'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount_percent' => $item['discount_percent'] ?? 0,
                'tax_rate' => $item['tax_rate'] ?? 10,
                'sort_order' => $i,
            ]);
        }

        $quotation->recalculate();

        return redirect()->route('quotations.index')->with('success', 'Đã tạo báo giá.');
    }

    public function edit(Quotation $quotation)
    {
        $accountId = auth()->user()->account_id;
        $quotation->load(['items', 'customer', 'lead', 'deal', 'creator', 'approver']);

        return Inertia::render('Quotations/Edit', [
            'quotation' => $quotation,
            'customers' => Customer::where('account_id', $accountId)->select('id', 'name')->get(),
            'leads' => Lead::where('account_id', $accountId)->select('id', 'company')->get(),
            'deals' => Deal::where('account_id', $accountId)->select('id', 'title')->get(),
            'products' => Product::where('account_id', $accountId)->active()->select('id', 'name', 'sku', 'unit', 'unit_price', 'tax_rate', 'description')->get(),
        ]);
    }

    public function update(Request $request, Quotation $quotation)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'customer_id' => 'nullable|integer',
            'lead_id' => 'nullable|integer',
            'deal_id' => 'nullable|integer',
            'issue_date' => 'nullable|date',
            'valid_until' => 'nullable|date',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|integer',
            'items.*.name' => 'required|string',
            'items.*.unit' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $quotation->update([
            'title' => $validated['title'],
            'customer_id' => $validated['customer_id'] ?? null,
            'lead_id' => $validated['lead_id'] ?? null,
            'deal_id' => $validated['deal_id'] ?? null,
            'issue_date' => $validated['issue_date'],
            'valid_until' => $validated['valid_until'],
            'discount_percent' => $validated['discount_percent'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'terms' => $validated['terms'] ?? null,
        ]);

        // Sync items
        $quotation->items()->delete();
        foreach ($validated['items'] as $i => $item) {
            $quotation->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'name' => $item['name'],
                'unit' => $item['unit'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount_percent' => $item['discount_percent'] ?? 0,
                'tax_rate' => $item['tax_rate'] ?? 10,
                'sort_order' => $i,
            ]);
        }

        $quotation->recalculate();

        return redirect()->route('quotations.index')->with('success', 'Đã cập nhật báo giá.');
    }

    public function destroy(Quotation $quotation)
    {
        $quotation->delete();
        return redirect()->route('quotations.index')->with('success', 'Đã xóa báo giá.');
    }

    // Submit for approval
    public function submitApproval(Quotation $quotation)
    {
        $quotation->update(['status' => 'pending_approval']);
        return back()->with('success', 'Đã gửi báo giá để phê duyệt.');
    }
}
