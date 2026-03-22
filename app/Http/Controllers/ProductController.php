<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::where('account_id', auth()->user()->account_id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $products = $query->orderBy('sort_order')->orderBy('name')->paginate(30)->through(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'type' => $p->type,
            'category' => $p->category,
            'unit' => $p->unit,
            'unit_price' => $p->unit_price,
            'cost_price' => $p->cost_price,
            'currency' => $p->currency,
            'tax_rate' => $p->tax_rate,
            'is_active' => $p->is_active,
            'formatted_price' => $p->formatted_price,
            'margin' => $p->margin,
            'description' => $p->description,
        ]);

        // Stats
        $accountId = auth()->user()->account_id;
        $stats = [
            'total' => Product::where('account_id', $accountId)->count(),
            'active' => Product::where('account_id', $accountId)->active()->count(),
            'products' => Product::where('account_id', $accountId)->products()->count(),
            'services' => Product::where('account_id', $accountId)->services()->count(),
        ];

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => $request->only(['search', 'type', 'status']),
            'stats' => $stats,
        ]);
    }



    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:products',
            'type' => 'required|in:product,service',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'unit_price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $validated['account_id'] = auth()->user()->account_id;
        Product::create($validated);

        return back()->with('success', 'Đã tạo sản phẩm/dịch vụ.');
    }



    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:products,sku,' . $product->id,
            'type' => 'required|in:product,service',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'unit_price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $product->update($validated);

        return back()->with('success', 'Đã cập nhật sản phẩm/dịch vụ.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return back()->with('success', 'Đã xóa sản phẩm/dịch vụ.');
    }
}
