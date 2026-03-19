<?php

namespace App\Http\Controllers;

use App\Models\CaseStudy;
use App\Models\CaseStudyMedia;
use App\Models\CaseStudyTag;
use App\Models\CaseStudyLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CaseStudyController extends Controller
{
    /**
     * GET /case-studies — Index with search & filter
     */
    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;

        $query = CaseStudy::where('account_id', $accountId)
            ->with(['creator', 'tags', 'media' => fn ($q) => $q->where('section', 'gallery')->limit(1)])
            ->withCount('media', 'links');

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhere('client_industry', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($category = $request->get('category')) {
            $query->where('service_category', $category);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($visibility = $request->get('visibility')) {
            $query->where('visibility', $visibility);
        }
        if ($tagId = $request->get('tag')) {
            $query->byTag($tagId);
        }
        if ($request->get('featured')) {
            $query->featured();
        }

        $caseStudies = $query->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        $tags = CaseStudyTag::where('account_id', $accountId)->orderBy('name')->get();

        $stats = [
            'total' => CaseStudy::where('account_id', $accountId)->count(),
            'published' => CaseStudy::where('account_id', $accountId)->where('status', 'published')->count(),
            'draft' => CaseStudy::where('account_id', $accountId)->where('status', 'draft')->count(),
            'featured' => CaseStudy::where('account_id', $accountId)->where('is_featured', true)->count(),
        ];

        $categoryCounts = CaseStudy::where('account_id', $accountId)
            ->selectRaw('service_category, COUNT(*) as count')
            ->groupBy('service_category')
            ->pluck('count', 'service_category');

        return Inertia::render('CaseStudies/Index', [
            'caseStudies' => $caseStudies,
            'tags' => $tags,
            'stats' => $stats,
            'categoryCounts' => $categoryCounts,
            'filters' => $request->only(['search', 'category', 'status', 'visibility', 'tag', 'featured']),
            'serviceCategories' => CaseStudy::SERVICE_CATEGORIES,
        ]);
    }

    /**
     * GET /case-studies/create
     */
    public function create(): Response
    {
        $accountId = Auth::user()->account_id;
        $tags = CaseStudyTag::where('account_id', $accountId)->orderBy('name')->get();

        return Inertia::render('CaseStudies/Create', [
            'tags' => $tags,
            'serviceCategories' => CaseStudy::SERVICE_CATEGORIES,
            'clientSizes' => CaseStudy::CLIENT_SIZES,
        ]);
    }

    /**
     * POST /case-studies
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string|max:500',
            'service_category' => 'required|in:website,marketing,seo,branding,landing_page,ai_agent',
            'client_name' => 'required|string|max:255',
            'client_industry' => 'nullable|string|max:100',
            'client_company_size' => 'nullable|string|max:50',
            'client_logo_url' => 'nullable|string|max:500',
            'client_website' => 'nullable|string|max:500',
            'problem' => 'nullable|string',
            'solution' => 'nullable|string',
            'approach' => 'nullable|string',
            'result' => 'nullable|string',
            'result_metrics' => 'nullable|array',
            'result_metrics.*.label' => 'required|string',
            'result_metrics.*.value' => 'required|string',
            'testimonial_quote' => 'nullable|string',
            'testimonial_author' => 'nullable|string|max:255',
            'testimonial_role' => 'nullable|string|max:255',
            'featured_image_url' => 'nullable|string|max:500',
            'project_url' => 'nullable|string|max:500',
            'project_start_date' => 'nullable|date',
            'project_end_date' => 'nullable|date|after_or_equal:project_start_date',
            'visibility' => 'nullable|in:public,private,unlisted',
            'status' => 'nullable|in:draft,published,archived',
            'is_featured' => 'nullable|boolean',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:case_study_tags,id',
        ]);

        $validated['account_id'] = Auth::user()->account_id;
        $validated['created_by'] = Auth::id();
        $validated['slug'] = Str::slug($validated['title']) . '-' . Str::random(6);

        // Calculate duration
        if (!empty($validated['project_start_date']) && !empty($validated['project_end_date'])) {
            $validated['project_duration_days'] = \Carbon\Carbon::parse($validated['project_start_date'])
                ->diffInDays(\Carbon\Carbon::parse($validated['project_end_date']));
        }

        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $caseStudy = CaseStudy::create($validated);

        if (!empty($tagIds)) {
            $caseStudy->tags()->attach($tagIds);
        }

        return redirect()->route('case-studies.edit', $caseStudy)
            ->with('success', 'Case study created.');
    }

    /**
     * GET /case-studies/{caseStudy}
     */
    public function show(CaseStudy $caseStudy): Response
    {
        $caseStudy->load(['creator', 'tags', 'media', 'links']);
        $caseStudy->incrementViews();

        return Inertia::render('CaseStudies/Show', [
            'caseStudy' => $caseStudy,
            'linkedLeads' => $caseStudy->linkedLeads(),
            'linkedDeals' => $caseStudy->linkedDeals(),
        ]);
    }

    /**
     * GET /case-studies/{caseStudy}/edit
     */
    public function edit(CaseStudy $caseStudy): Response
    {
        $accountId = Auth::user()->account_id;
        $caseStudy->load(['tags', 'media', 'links']);
        $tags = CaseStudyTag::where('account_id', $accountId)->orderBy('name')->get();

        return Inertia::render('CaseStudies/Edit', [
            'caseStudy' => $caseStudy,
            'tags' => $tags,
            'serviceCategories' => CaseStudy::SERVICE_CATEGORIES,
            'clientSizes' => CaseStudy::CLIENT_SIZES,
        ]);
    }

    /**
     * PUT /case-studies/{caseStudy}
     */
    public function update(Request $request, CaseStudy $caseStudy): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string|max:500',
            'service_category' => 'required|in:website,marketing,seo,branding,landing_page,ai_agent',
            'client_name' => 'required|string|max:255',
            'client_industry' => 'nullable|string|max:100',
            'client_company_size' => 'nullable|string|max:50',
            'client_logo_url' => 'nullable|string|max:500',
            'client_website' => 'nullable|string|max:500',
            'problem' => 'nullable|string',
            'solution' => 'nullable|string',
            'approach' => 'nullable|string',
            'result' => 'nullable|string',
            'result_metrics' => 'nullable|array',
            'testimonial_quote' => 'nullable|string',
            'testimonial_author' => 'nullable|string|max:255',
            'testimonial_role' => 'nullable|string|max:255',
            'featured_image_url' => 'nullable|string|max:500',
            'project_url' => 'nullable|string|max:500',
            'project_start_date' => 'nullable|date',
            'project_end_date' => 'nullable|date|after_or_equal:project_start_date',
            'visibility' => 'nullable|in:public,private,unlisted',
            'status' => 'nullable|in:draft,published,archived',
            'is_featured' => 'nullable|boolean',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:case_study_tags,id',
        ]);

        $validated['updated_by'] = Auth::id();

        if (!empty($validated['project_start_date']) && !empty($validated['project_end_date'])) {
            $validated['project_duration_days'] = \Carbon\Carbon::parse($validated['project_start_date'])
                ->diffInDays(\Carbon\Carbon::parse($validated['project_end_date']));
        }

        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $caseStudy->update($validated);
        $caseStudy->tags()->sync($tagIds);

        return back()->with('success', 'Case study updated.');
    }

    /**
     * DELETE /case-studies/{caseStudy}
     */
    public function destroy(CaseStudy $caseStudy): RedirectResponse
    {
        $caseStudy->delete();
        return redirect()->route('case-studies')->with('success', 'Case study deleted.');
    }

    // ─── Media Management ────────────────────────

    /**
     * POST /case-studies/{caseStudy}/media
     */
    public function storeMedia(Request $request, CaseStudy $caseStudy): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:image,video,document,link',
            'title' => 'nullable|string|max:255',
            'url' => 'required|string|max:1000',
            'thumbnail_url' => 'nullable|string|max:1000',
            'caption' => 'nullable|string|max:500',
            'section' => 'nullable|in:gallery,before_after,process,result',
        ]);

        $validated['sort_order'] = $caseStudy->media()->max('sort_order') + 1;
        $caseStudy->media()->create($validated);

        return back()->with('success', 'Media added.');
    }

    /**
     * DELETE /case-study-media/{media}
     */
    public function destroyMedia(CaseStudyMedia $media): RedirectResponse
    {
        $media->delete();
        return back()->with('success', 'Media removed.');
    }

    // ─── Tags Management ─────────────────────────

    /**
     * POST /case-study-tags
     */
    public function storeTag(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'nullable|in:industry,client_type,technology,custom',
            'color' => 'nullable|string|max:7',
        ]);

        $validated['account_id'] = Auth::user()->account_id;
        CaseStudyTag::create($validated);

        return back()->with('success', 'Tag created.');
    }

    /**
     * DELETE /case-study-tags/{tag}
     */
    public function destroyTag(CaseStudyTag $tag): RedirectResponse
    {
        $tag->delete();
        return back()->with('success', 'Tag deleted.');
    }

    // ─── CRM Entity Linking ──────────────────────

    /**
     * POST /case-studies/{caseStudy}/link
     */
    public function linkEntity(Request $request, CaseStudy $caseStudy): RedirectResponse
    {
        $validated = $request->validate([
            'linkable_type' => 'required|in:lead,deal,email_campaign',
            'linkable_id' => 'required|integer',
            'context' => 'nullable|string|max:255',
        ]);

        $caseStudy->linkTo($validated['linkable_type'], $validated['linkable_id'], $validated['context'] ?? null);

        return back()->with('success', 'Entity linked.');
    }

    /**
     * DELETE /case-study-links/{link}
     */
    public function unlinkEntity(CaseStudyLink $link): RedirectResponse
    {
        $link->delete();
        return back()->with('success', 'Link removed.');
    }

    // ─── Toggle Status / Feature ─────────────────

    /**
     * POST /case-studies/{caseStudy}/publish
     */
    public function publish(CaseStudy $caseStudy): RedirectResponse
    {
        $caseStudy->update(['status' => 'published', 'updated_by' => Auth::id()]);
        return back()->with('success', 'Case study published.');
    }

    /**
     * POST /case-studies/{caseStudy}/toggle-featured
     */
    public function toggleFeatured(CaseStudy $caseStudy): RedirectResponse
    {
        $caseStudy->update(['is_featured' => !$caseStudy->is_featured]);
        return back()->with('success', $caseStudy->is_featured ? 'Case study featured.' : 'Removed from featured.');
    }
}
