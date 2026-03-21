<?php

namespace App\Http\Controllers;

use App\Models\WikiArticle;
use App\Models\WikiCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WikiController extends Controller
{
    /**
     * Wiki home — categories sidebar + article list
     */
    public function index(Request $request): Response
    {
        $account = Auth::user()->account;

        $articles = $account->wikiArticles()
            ->with(['category:id,name,slug,icon', 'author:id,first_name,last_name'])
            ->published()
            ->filter($request->only('search', 'category_id'))
            ->orderBy('is_pinned', 'desc')
            ->orderBy('updated_at', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'slug' => $a->slug,
                'excerpt' => $a->preview,
                'status' => $a->status,
                'is_pinned' => $a->is_pinned,
                'views_count' => $a->views_count,
                'category' => $a->category ? [
                    'id' => $a->category->id,
                    'name' => $a->category->name,
                    'icon' => $a->category->icon,
                ] : null,
                'author' => $a->author ? [
                    'id' => $a->author->id,
                    'name' => $a->author->name,
                ] : null,
                'updated_at' => $a->updated_at->diffForHumans(),
                'published_at' => $a->published_at?->diffForHumans(),
            ]);

        // Draft articles for the current user
        $drafts = $account->wikiArticles()
            ->draft()
            ->where('created_by', Auth::id())
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'slug' => $a->slug,
                'updated_at' => $a->updated_at->diffForHumans(),
            ]);

        return Inertia::render('Wiki/Index', [
            'articles' => $articles,
            'drafts' => $drafts,
            'categories' => $this->getCategoryTree(),
            'filters' => $request->only('search', 'category_id'),
            'stats' => [
                'total_articles' => $account->wikiArticles()->published()->count(),
                'total_categories' => $account->wikiCategories()->count(),
            ],
        ]);
    }

    /**
     * View a single article
     */
    public function show(WikiArticle $article): Response
    {
        $article->recordView();

        return Inertia::render('Wiki/Show', [
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'content' => $article->content,
                'excerpt' => $article->excerpt,
                'status' => $article->status,
                'is_pinned' => $article->is_pinned,
                'views_count' => $article->views_count,
                'category' => $article->category ? [
                    'id' => $article->category->id,
                    'name' => $article->category->name,
                    'slug' => $article->category->slug,
                    'icon' => $article->category->icon,
                ] : null,
                'author' => $article->author ? [
                    'id' => $article->author->id,
                    'name' => $article->author->name,
                ] : null,
                'editor' => $article->editor ? [
                    'id' => $article->editor->id,
                    'name' => $article->editor->name,
                ] : null,
                'published_at' => $article->published_at?->toISOString(),
                'created_at' => $article->created_at->toISOString(),
                'updated_at' => $article->updated_at->toISOString(),
                'deleted_at' => $article->deleted_at,
            ],
            'versions' => $article->versions()
                ->with('editor:id,first_name,last_name')
                ->limit(20)
                ->get()
                ->map(fn ($v) => [
                    'id' => $v->id,
                    'version_number' => $v->version_number,
                    'change_summary' => $v->change_summary,
                    'editor' => $v->editor ? ['id' => $v->editor->id, 'name' => $v->editor->name] : null,
                    'created_at' => $v->created_at->diffForHumans(),
                ]),
            'categories' => $this->getCategoryTree(),
            'relatedArticles' => $article->category_id
                ? WikiArticle::where('account_id', $article->account_id)
                    ->where('category_id', $article->category_id)
                    ->where('id', '!=', $article->id)
                    ->published()
                    ->limit(5)
                    ->get()
                    ->map(fn ($a) => ['id' => $a->id, 'title' => $a->title, 'slug' => $a->slug])
                : [],
        ]);
    }

    /**
     * Create article form
     */
    public function create(): Response
    {
        return Inertia::render('Wiki/Create', [
            'categories' => $this->getCategoryList(),
            'statuses' => WikiArticle::getStatuses(),
        ]);
    }

    /**
     * Store new article
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'category_id' => 'nullable|exists:wiki_categories,id',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'status' => 'required|in:draft,published',
            'is_pinned' => 'boolean',
        ]);

        $article = Auth::user()->account->wikiArticles()->create(array_merge($validated, [
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'published_at' => $validated['status'] === 'published' ? now() : null,
        ]));

        // Create initial version
        $article->createVersion('Tạo bài viết');

        return Redirect::route('wiki.show', $article)->with('success', 'Đã tạo bài viết.');
    }

    /**
     * Edit article form
     */
    public function edit(WikiArticle $article): Response
    {
        return Inertia::render('Wiki/Edit', [
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'content' => $article->content,
                'excerpt' => $article->excerpt,
                'category_id' => $article->category_id,
                'status' => $article->status,
                'is_pinned' => $article->is_pinned,
                'deleted_at' => $article->deleted_at,
            ],
            'categories' => $this->getCategoryList(),
            'statuses' => WikiArticle::getStatuses(),
        ]);
    }

    /**
     * Update article
     */
    public function update(Request $request, WikiArticle $article): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'category_id' => 'nullable|exists:wiki_categories,id',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'status' => 'required|in:draft,published',
            'is_pinned' => 'boolean',
            'change_summary' => 'nullable|string|max:500',
        ]);

        // Create version before updating
        $article->createVersion($validated['change_summary'] ?? null);

        $updateData = collect($validated)->except('change_summary')->toArray();
        $updateData['updated_by'] = Auth::id();

        // Set published_at if publishing for the first time
        if ($validated['status'] === 'published' && !$article->published_at) {
            $updateData['published_at'] = now();
        }

        $article->update($updateData);

        return Redirect::route('wiki.show', $article)->with('success', 'Đã cập nhật bài viết.');
    }

    /**
     * Toggle pin status
     */
    public function togglePin(WikiArticle $article): RedirectResponse
    {
        $article->update(['is_pinned' => !$article->is_pinned]);
        $msg = $article->is_pinned ? 'Đã ghim bài viết.' : 'Đã bỏ ghim.';
        return Redirect::back()->with('success', $msg);
    }

    /**
     * Delete article
     */
    public function destroy(WikiArticle $article): RedirectResponse
    {
        $article->delete();
        return Redirect::route('wiki')->with('success', 'Đã xóa bài viết.');
    }

    /**
     * Restore article
     */
    public function restore(WikiArticle $article): RedirectResponse
    {
        $article->restore();
        return Redirect::back()->with('success', 'Đã khôi phục bài viết.');
    }

    // ── Category Management ──

    /**
     * Store category
     */
    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:wiki_categories,id',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
        ]);

        Auth::user()->account->wikiCategories()->create($validated);

        return Redirect::back()->with('success', 'Đã tạo danh mục.');
    }

    /**
     * Update category
     */
    public function updateCategory(Request $request, WikiCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:wiki_categories,id',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
        ]);

        if (isset($validated['parent_id']) && $validated['parent_id'] == $category->id) {
            return Redirect::back()->withErrors(['parent_id' => 'Danh mục không thể là cha của chính nó.']);
        }

        $category->update(array_merge($validated, [
            'slug' => Str::slug($validated['name']),
        ]));

        return Redirect::back()->with('success', 'Đã cập nhật danh mục.');
    }

    /**
     * Delete category
     */
    public function destroyCategory(WikiCategory $category): RedirectResponse
    {
        // Move articles to uncategorized
        $category->articles()->update(['category_id' => null]);
        // Move children to parent
        $category->children()->update(['parent_id' => $category->parent_id]);
        $category->delete();

        return Redirect::back()->with('success', 'Đã xóa danh mục.');
    }

    // ── Helpers ──

    private function getCategoryTree(): array
    {
        $categories = Auth::user()->account->wikiCategories()
            ->topLevel()
            ->with('descendants')
            ->withCount(['articles' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->get();

        return $this->formatCategoryTree($categories);
    }

    private function formatCategoryTree($categories): array
    {
        return $categories->map(function ($cat) {
            $data = [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'icon' => $cat->icon,
                'description' => $cat->description,
                'articles_count' => $cat->articles_count ?? 0,
            ];
            if ($cat->relationLoaded('descendants') && $cat->descendants->count() > 0) {
                $data['children'] = $this->formatCategoryTree($cat->descendants);
            }
            return $data;
        })->toArray();
    }

    private function getCategoryList(): array
    {
        return Auth::user()->account->wikiCategories()
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'parent_id' => $c->parent_id,
            ])
            ->toArray();
    }
}
