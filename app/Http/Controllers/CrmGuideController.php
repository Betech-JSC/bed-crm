<?php

namespace App\Http\Controllers;

use App\Models\CrmGuide;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CrmGuideController extends Controller
{
    public function index(Request $request)
    {
        $accountId = auth()->user()->account_id;
        $query = CrmGuide::where('account_id', $accountId)->with('creator');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('category', 'like', "%{$s}%")
                  ->orWhere('summary', 'like', "%{$s}%");
            });
        }
        if ($request->filled('category')) $query->where('category', $request->category);

        $guides = $query->orderBy('sort_order')->orderBy('title')->get()->map(fn ($g) => [
            'id' => $g->id,
            'title' => $g->title,
            'category' => $g->category,
            'icon' => $g->icon,
            'summary' => $g->summary,
            'content' => $g->content,
            'is_published' => $g->is_published,
            'sort_order' => $g->sort_order,
            'creator_name' => $g->creator?->name ?? '—',
            'updated_at' => $g->updated_at->format('d/m/Y'),
        ]);

        // Group by category
        $grouped = $guides->groupBy('category')->map(fn ($items, $cat) => [
            'category' => $cat ?: 'Chung',
            'items' => $items->values(),
        ])->values();

        $categories = CrmGuide::where('account_id', $accountId)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->map(fn ($c) => ['label' => $c, 'value' => $c]);

        return Inertia::render('CrmGuides/Index', [
            'grouped' => $grouped,
            'allGuides' => $guides,
            'filters' => $request->only(['search', 'category']),
            'categories' => $categories,
            'total' => $guides->count(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'icon' => 'nullable|string|max:50',
            'summary' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'is_published' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['account_id'] = auth()->user()->account_id;
        $validated['created_by'] = auth()->id();

        CrmGuide::create($validated);

        return back()->with('success', 'Đã tạo bài hướng dẫn.');
    }

    public function update(Request $request, CrmGuide $crmGuide)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'icon' => 'nullable|string|max:50',
            'summary' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'is_published' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $crmGuide->update($validated);

        return back()->with('success', 'Đã cập nhật.');
    }

    public function destroy(CrmGuide $crmGuide)
    {
        $crmGuide->delete();
        return back()->with('success', 'Đã xóa.');
    }
}
