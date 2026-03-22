<?php

namespace App\Http\Controllers;

use App\Models\DropshipSupplier;
use App\Models\DropshipProduct;
use App\Models\DropshipOrder;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DropshipController extends Controller
{
    public function index(Request $request)
    {
        $accountId = auth()->user()->account_id;

        // ── Orders ──
        $orderQuery = DropshipOrder::where('account_id', $accountId)->with(['supplier', 'creator']);

        if ($request->filled('search')) {
            $s = $request->search;
            $orderQuery->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%{$s}%")
                  ->orWhere('shopify_order_number', 'like', "%{$s}%")
                  ->orWhere('customer_name', 'like', "%{$s}%")
                  ->orWhere('tracking_number', 'like', "%{$s}%");
            });
        }
        if ($request->filled('order_status')) $orderQuery->where('order_status', $request->order_status);
        if ($request->filled('supplier_id')) $orderQuery->where('supplier_id', $request->supplier_id);

        $orders = $orderQuery->latest()->paginate(30)->through(fn ($o) => [
            'id' => $o->id,
            'order_number' => $o->order_number,
            'shopify_order_number' => $o->shopify_order_number,
            'customer_name' => $o->customer_name,
            'supplier_name' => $o->supplier?->name ?? '—',
            'supplier_id' => $o->supplier_id,
            'items' => $o->items,
            'total_cost' => $o->total_cost,
            'selling_price' => $o->selling_price,
            'profit' => $o->profit,
            'profit_margin' => $o->profit_margin,
            'order_status' => $o->order_status,
            'status_label' => $o->status_label,
            'fulfillment_status' => $o->fulfillment_status,
            'fulfillment_label' => $o->fulfillment_label,
            'tracking_number' => $o->tracking_number,
            'tracking_url' => $o->tracking_url,
            'carrier' => $o->carrier,
            'ordered_at' => $o->ordered_at?->format('d/m/Y'),
            'shipped_at' => $o->shipped_at?->format('d/m/Y'),
            'delivered_at' => $o->delivered_at?->format('d/m/Y'),
            'created_at' => $o->created_at->format('d/m/Y'),
            'notes' => $o->notes,
            'shipping_name' => $o->shipping_name,
            'shipping_address' => $o->shipping_address,
            'shipping_city' => $o->shipping_city,
            'shipping_country' => $o->shipping_country,
            'shipping_method' => $o->shipping_method,
            'customer_email' => $o->customer_email,
            'customer_phone' => $o->customer_phone,
        ]);

        // ── Suppliers ──
        $suppliers = DropshipSupplier::where('account_id', $accountId)
            ->withCount('orders')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'code' => $s->code,
                'contact_name' => $s->contact_name,
                'email' => $s->email,
                'phone' => $s->phone,
                'website' => $s->website,
                'platform' => $s->platform,
                'country' => $s->country,
                'address' => $s->address,
                'shipping_methods' => $s->shipping_methods,
                'avg_processing_days' => $s->avg_processing_days,
                'avg_shipping_days' => $s->avg_shipping_days,
                'return_policy' => $s->return_policy,
                'payment_terms' => $s->payment_terms,
                'notes' => $s->notes,
                'rating' => $s->rating,
                'is_active' => $s->is_active,
                'orders_count' => $s->orders_count,
                'fulfillment_rate' => $s->fulfillment_rate,
            ]);

        // ── Stats ──
        $stats = [
            'total_orders' => DropshipOrder::where('account_id', $accountId)->count(),
            'pending_orders' => DropshipOrder::where('account_id', $accountId)->whereIn('order_status', ['pending', 'processing'])->count(),
            'shipped_orders' => DropshipOrder::where('account_id', $accountId)->where('order_status', 'shipped')->count(),
            'total_revenue' => DropshipOrder::where('account_id', $accountId)->whereNotIn('order_status', ['cancelled', 'returned'])->sum('selling_price'),
            'total_cost' => DropshipOrder::where('account_id', $accountId)->whereNotIn('order_status', ['cancelled', 'returned'])->sum('total_cost'),
            'total_profit' => DropshipOrder::where('account_id', $accountId)->whereNotIn('order_status', ['cancelled', 'returned'])->sum('profit'),
            'active_suppliers' => DropshipSupplier::where('account_id', $accountId)->where('is_active', true)->count(),
            'month_orders' => DropshipOrder::where('account_id', $accountId)->whereMonth('created_at', now()->month)->count(),
            'month_profit' => DropshipOrder::where('account_id', $accountId)->whereMonth('created_at', now()->month)->whereNotIn('order_status', ['cancelled'])->sum('profit'),
        ];

        return Inertia::render('Dropship/Index', [
            'orders' => $orders,
            'suppliers' => $suppliers,
            'filters' => $request->only(['search', 'order_status', 'supplier_id']),
            'stats' => $stats,
        ]);
    }

    // ── Order CRUD ──
    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|integer',
            'shopify_order_id' => 'nullable|string',
            'shopify_order_number' => 'nullable|string',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string',
            'shipping_name' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'shipping_city' => 'nullable|string',
            'shipping_country' => 'nullable|string',
            'shipping_zip' => 'nullable|string',
            'shipping_method' => 'nullable|string',
            'items' => 'nullable|array',
            'subtotal' => 'nullable|numeric',
            'shipping_cost' => 'nullable|numeric',
            'total_cost' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $accountId = auth()->user()->account_id;
        $validated['account_id'] = $accountId;
        $validated['order_number'] = DropshipOrder::generateNumber($accountId);
        $validated['created_by'] = auth()->id();
        $validated['order_status'] = 'pending';
        $validated['fulfillment_status'] = 'unfulfilled';
        $validated['payment_status'] = 'unpaid';
        $validated['profit'] = ($validated['selling_price'] ?? 0) - ($validated['total_cost'] ?? 0);

        DropshipOrder::create($validated);

        return back()->with('success', 'Đã tạo đơn dropship.');
    }

    public function updateOrder(Request $request, DropshipOrder $order)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|integer',
            'shopify_order_id' => 'nullable|string',
            'shopify_order_number' => 'nullable|string',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string',
            'shipping_name' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'shipping_city' => 'nullable|string',
            'shipping_country' => 'nullable|string',
            'shipping_zip' => 'nullable|string',
            'shipping_method' => 'nullable|string',
            'items' => 'nullable|array',
            'subtotal' => 'nullable|numeric',
            'shipping_cost' => 'nullable|numeric',
            'total_cost' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'order_status' => 'nullable|string',
            'fulfillment_status' => 'nullable|string',
            'supplier_order_id' => 'nullable|string',
            'tracking_number' => 'nullable|string',
            'tracking_url' => 'nullable|string',
            'carrier' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['profit'] = ($validated['selling_price'] ?? 0) - ($validated['total_cost'] ?? 0);

        // Auto-set dates based on status changes
        if (($validated['order_status'] ?? null) === 'ordered' && !$order->ordered_at) {
            $validated['ordered_at'] = now();
        }
        if (($validated['order_status'] ?? null) === 'shipped' && !$order->shipped_at) {
            $validated['shipped_at'] = now();
        }
        if (($validated['order_status'] ?? null) === 'delivered' && !$order->delivered_at) {
            $validated['delivered_at'] = now();
            $validated['fulfillment_status'] = 'delivered';
        }
        if (($validated['order_status'] ?? null) === 'cancelled' && !$order->cancelled_at) {
            $validated['cancelled_at'] = now();
        }

        $order->update($validated);

        return back()->with('success', 'Đã cập nhật đơn.');
    }

    public function destroyOrder(DropshipOrder $order)
    {
        $order->delete();
        return back()->with('success', 'Đã xóa đơn.');
    }

    // ── Supplier CRUD ──
    public function storeSupplier(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'website' => 'nullable|string',
            'platform' => 'nullable|string',
            'country' => 'nullable|string',
            'address' => 'nullable|string',
            'shipping_methods' => 'nullable|array',
            'avg_processing_days' => 'nullable|integer',
            'avg_shipping_days' => 'nullable|integer',
            'return_policy' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'notes' => 'nullable|string',
            'rating' => 'nullable|numeric|min:0|max:5',
            'is_active' => 'boolean',
        ]);

        $accountId = auth()->user()->account_id;
        $validated['account_id'] = $accountId;
        $validated['code'] = DropshipSupplier::generateCode($accountId);

        DropshipSupplier::create($validated);

        return back()->with('success', 'Đã thêm nhà cung cấp.');
    }

    public function updateSupplier(Request $request, DropshipSupplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'website' => 'nullable|string',
            'platform' => 'nullable|string',
            'country' => 'nullable|string',
            'address' => 'nullable|string',
            'shipping_methods' => 'nullable|array',
            'avg_processing_days' => 'nullable|integer',
            'avg_shipping_days' => 'nullable|integer',
            'return_policy' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'notes' => 'nullable|string',
            'rating' => 'nullable|numeric|min:0|max:5',
            'is_active' => 'boolean',
        ]);

        $supplier->update($validated);

        return back()->with('success', 'Đã cập nhật nhà cung cấp.');
    }

    public function destroySupplier(DropshipSupplier $supplier)
    {
        $supplier->delete();
        return back()->with('success', 'Đã xóa nhà cung cấp.');
    }

    // ── Quick status update ──
    public function updateOrderStatus(Request $request, DropshipOrder $order)
    {
        $validated = $request->validate([
            'order_status' => 'required|string',
            'tracking_number' => 'nullable|string',
            'tracking_url' => 'nullable|string',
            'carrier' => 'nullable|string',
            'supplier_order_id' => 'nullable|string',
        ]);

        if ($validated['order_status'] === 'ordered' && !$order->ordered_at) {
            $validated['ordered_at'] = now();
        }
        if ($validated['order_status'] === 'shipped' && !$order->shipped_at) {
            $validated['shipped_at'] = now();
        }
        if ($validated['order_status'] === 'delivered' && !$order->delivered_at) {
            $validated['delivered_at'] = now();
            $validated['fulfillment_status'] = 'delivered';
        }
        if ($validated['order_status'] === 'cancelled' && !$order->cancelled_at) {
            $validated['cancelled_at'] = now();
        }

        $order->update($validated);

        return back()->with('success', 'Đã cập nhật trạng thái.');
    }
}
