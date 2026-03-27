<?php

namespace App\Http\Controllers;

use App\Models\ContentTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContentTemplatesController extends Controller
{
    public function index(): Response
    {
        $account = Auth::user()->account;
        $accountId = $account->id;

        $query = $account->contentTemplates()->orderBy('created_at', 'desc');

        if ($search = Request::input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($category = Request::input('category')) {
            $query->where('category', $category);
        }

        // Stats
        $stats = [
            'total' => ContentTemplate::where('account_id', $accountId)->count(),
            'blog' => ContentTemplate::where('account_id', $accountId)->where('category', 'blog')->count(),
            'social' => ContentTemplate::where('account_id', $accountId)->where('category', 'social')->count(),
            'email' => ContentTemplate::where('account_id', $accountId)->where('category', 'email')->count(),
            'ad' => ContentTemplate::where('account_id', $accountId)->where('category', 'ad')->count(),
        ];

        return Inertia::render('ContentTemplates/Index', [
            'templates' => $query->paginate(15)->through(fn ($template) => [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'category' => $template->category,
                'is_active' => $template->is_active,
                'usage_count' => $template->usage_count,
                'created_at' => $template->created_at->format('d/m/Y H:i'),
            ]),
            'stats' => $stats,
            'filters' => Request::only('search', 'category'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('ContentTemplates/Create', [
            'categories' => ['blog', 'social', 'email', 'ad', 'other'],
        ]);
    }

    public function store(): RedirectResponse
    {
        $validated = Request::validate([
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'prompt_template' => ['required', 'string'],
            'variables' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        Auth::user()->account->contentTemplates()->create($validated);

        return Redirect::route('content-templates')->with('success', 'Tạo template thành công!');
    }

    public function edit(ContentTemplate $contentTemplate): Response
    {
        return Inertia::render('ContentTemplates/Edit', [
            'template' => [
                'id' => $contentTemplate->id,
                'name' => $contentTemplate->name,
                'description' => $contentTemplate->description,
                'category' => $contentTemplate->category,
                'prompt_template' => $contentTemplate->prompt_template,
                'variables' => $contentTemplate->variables ?? [],
                'settings' => $contentTemplate->settings ?? [],
                'is_active' => $contentTemplate->is_active,
            ],
            'categories' => ['blog', 'social', 'email', 'ad', 'other'],
        ]);
    }

    public function update(ContentTemplate $contentTemplate): RedirectResponse
    {
        $validated = Request::validate([
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'prompt_template' => ['required', 'string'],
            'variables' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        $contentTemplate->update($validated);

        return Redirect::back()->with('success', 'Cập nhật template thành công!');
    }

    public function destroy(ContentTemplate $contentTemplate): RedirectResponse
    {
        $contentTemplate->delete();

        return Redirect::back()->with('success', 'Đã xóa template.');
    }
}
