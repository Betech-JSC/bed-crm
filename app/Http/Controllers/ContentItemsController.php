<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use App\Models\ContentTemplate;
use App\Services\ContentManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContentItemsController extends Controller
{
    public function __construct(
        private ContentManagementService $contentService
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('ContentItems/Index', [
            'contentItems' => Auth::user()->account->contentItems()
                ->with(['creator', 'template'])
                ->orderBy('created_at', 'desc')
                ->paginate(20)
                ->through(fn ($item) => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'type' => $item->type,
                    'content' => substr($item->content, 0, 150) . '...',
                    'status' => $item->status,
                    'ai_model' => $item->ai_model,
                    'usage_count' => $item->usage_count,
                    'creator' => $item->creator ? $item->creator->first_name . ' ' . $item->creator->last_name : null,
                    'template' => $item->template ? $item->template->name : null,
                    'created_at' => $item->created_at->format('Y-m-d H:i'),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('ContentItems/Create', [
            'templates' => Auth::user()->account->contentTemplates()
                ->active()
                ->get()
                ->map(fn ($template) => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'category' => $template->category,
                ]),
            'types' => ContentItem::getTypes(),
            'statuses' => ContentItem::getStatuses(),
        ]);
    }

    public function store(): RedirectResponse
    {
        $validated = Request::validate([
            'template_id' => ['required', 'exists:content_templates,id'],
            'type' => ['required', 'string'],
            'title' => ['nullable', 'string', 'max:255'],
            'variables' => ['nullable', 'array'],
            'options' => ['nullable', 'array'],
        ]);

        $template = ContentTemplate::findOrFail($validated['template_id']);
        $variables = $validated['variables'] ?? [];
        $options = array_merge($validated['options'] ?? [], [
            'type' => $validated['type'],
            'title' => $validated['title'],
        ]);

        $contentItem = $this->contentService->generateFromTemplate($template, $variables, $options);

        return Redirect::route('content-items.show', $contentItem->id)->with('success', 'Content generated successfully.');
    }

    public function show(ContentItem $contentItem): Response
    {
        return Inertia::render('ContentItems/Show', [
            'contentItem' => [
                'id' => $contentItem->id,
                'title' => $contentItem->title,
                'type' => $contentItem->type,
                'content' => $contentItem->content,
                'metadata' => $contentItem->metadata,
                'ai_model' => $contentItem->ai_model,
                'ai_metadata' => $contentItem->ai_metadata,
                'status' => $contentItem->status,
                'tags' => $contentItem->tags,
                'usage_count' => $contentItem->usage_count,
                'creator' => $contentItem->creator ? [
                    'id' => $contentItem->creator->id,
                    'name' => $contentItem->creator->first_name . ' ' . $contentItem->creator->last_name,
                ] : null,
                'template' => $contentItem->template ? [
                    'id' => $contentItem->template->id,
                    'name' => $contentItem->template->name,
                ] : null,
                'created_at' => $contentItem->created_at->format('Y-m-d H:i'),
            ],
            'socialAccounts' => Auth::user()->account->socialAccounts()
                ->active()
                ->get()
                ->map(fn ($account) => [
                    'id' => $account->id,
                    'platform' => $account->platform,
                    'name' => $account->name,
                ]),
        ]);
    }

    public function edit(ContentItem $contentItem): Response
    {
        return Inertia::render('ContentItems/Edit', [
            'contentItem' => [
                'id' => $contentItem->id,
                'title' => $contentItem->title,
                'type' => $contentItem->type,
                'content' => $contentItem->content,
                'metadata' => $contentItem->metadata,
                'status' => $contentItem->status,
                'tags' => $contentItem->tags,
            ],
            'types' => ContentItem::getTypes(),
            'statuses' => ContentItem::getStatuses(),
        ]);
    }

    public function update(ContentItem $contentItem): RedirectResponse
    {
        $validated = Request::validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'metadata' => ['nullable', 'array'],
            'status' => ['required', 'string'],
            'tags' => ['nullable', 'array'],
        ]);

        $contentItem->update($validated);

        return Redirect::back()->with('success', 'Content updated.');
    }

    public function destroy(ContentItem $contentItem): RedirectResponse
    {
        $contentItem->delete();

        return Redirect::route('content-items')->with('success', 'Content deleted.');
    }

    public function generateVariations(ContentItem $contentItem): RedirectResponse
    {
        $validated = Request::validate([
            'count' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        if (!$contentItem->template) {
            return Redirect::back()->with('error', 'Content item must have a template to generate variations.');
        }

        $variations = $this->contentService->generateVariations(
            $contentItem->template,
            [],
            $validated['count'],
            ['type' => $contentItem->type]
        );

        return Redirect::route('content-items')->with('success', count($variations) . ' variations generated.');
    }

    public function optimizeForPlatform(ContentItem $contentItem): RedirectResponse
    {
        $validated = Request::validate([
            'platform' => ['required', 'string'],
        ]);

        $optimized = $this->contentService->optimizeForPlatform($contentItem, $validated['platform']);

        return Redirect::route('content-items.show', $optimized->id)->with('success', 'Content optimized for ' . $validated['platform']);
    }
}
