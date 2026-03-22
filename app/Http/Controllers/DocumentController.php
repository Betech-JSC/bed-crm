<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $accountId = auth()->user()->account_id;
        $query = Document::where('account_id', $accountId)->with('creator');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('category', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%");
            });
        }
        if ($request->filled('type')) $query->where('type', $request->type);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('category')) $query->where('category', $request->category);

        $documents = $query->orderBy('sort_order')->latest()->paginate(30)->through(fn ($d) => [
            'id' => $d->id,
            'title' => $d->title,
            'type' => $d->type,
            'type_label' => $d->type_label,
            'category' => $d->category,
            'description' => $d->description,
            'content' => $d->content,
            'status' => $d->status,
            'status_label' => $d->status_label,
            'version' => $d->version,
            'tags' => $d->tags,
            'creator_name' => $d->creator?->name ?? '—',
            'created_at' => $d->created_at->format('d/m/Y'),
            'updated_at' => $d->updated_at->format('d/m/Y'),
        ]);

        $stats = [
            'total' => Document::where('account_id', $accountId)->count(),
            'records' => Document::where('account_id', $accountId)->records()->count(),
            'templates' => Document::where('account_id', $accountId)->templates()->count(),
            'published' => Document::where('account_id', $accountId)->published()->count(),
        ];

        $categories = Document::where('account_id', $accountId)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->map(fn ($c) => ['label' => $c, 'value' => $c]);

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
            'filters' => $request->only(['search', 'type', 'status', 'category']),
            'stats' => $stats,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:record,template',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'version' => 'nullable|string|max:20',
            'tags' => 'nullable|array',
        ]);

        $validated['account_id'] = auth()->user()->account_id;
        $validated['created_by'] = auth()->id();

        Document::create($validated);

        return back()->with('success', 'Đã tạo biên bản/biểu mẫu.');
    }

    public function update(Request $request, Document $document)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:record,template',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'version' => 'nullable|string|max:20',
            'tags' => 'nullable|array',
        ]);

        $document->update($validated);

        return back()->with('success', 'Đã cập nhật.');
    }

    public function destroy(Document $document)
    {
        $document->delete();
        return back()->with('success', 'Đã xóa.');
    }
}
