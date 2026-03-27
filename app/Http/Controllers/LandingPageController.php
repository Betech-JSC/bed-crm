<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\WebForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LandingPageController extends Controller
{
    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;
        $filters = $request->only('search', 'status');

        $pages = LandingPage::where('account_id', $accountId)
            ->filter($filters)
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'status' => $p->status,
                'visits_count' => $p->visits_count,
                'conversions_count' => $p->conversions_count,
                'conversion_rate' => $p->conversion_rate,
                'public_url' => $p->public_url,
                'blocks_count' => count($p->blocks ?? []),
                'updated_at' => $p->updated_at->format('d/m/Y H:i'),
            ]);

        $stats = [
            'total' => LandingPage::where('account_id', $accountId)->count(),
            'published' => LandingPage::where('account_id', $accountId)->where('status', 'published')->count(),
            'total_visits' => LandingPage::where('account_id', $accountId)->sum('visits_count'),
            'total_conversions' => LandingPage::where('account_id', $accountId)->sum('conversions_count'),
        ];

        return Inertia::render('LandingPages/Index', compact('pages', 'stats', 'filters'));
    }

    public function create(): Response
    {
        $accountId = Auth::user()->account_id;
        $webForms = WebForm::where('account_id', $accountId)->where('status', 'active')
            ->select('id', 'name')->get();

        return Inertia::render('LandingPages/Create', [
            'blockTypes' => LandingPage::getBlockTypes(),
            'webForms' => $webForms,
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:draft,published,archived',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'blocks' => 'nullable|array',
            'style_settings' => 'nullable|array',
            'web_form_id' => 'nullable|exists:web_forms,id',
        ]);

        $user = Auth::user();
        $page = LandingPage::create([
            'account_id' => $user->account_id,
            'created_by' => $user->id,
            ...$validated,
        ]);

        return redirect()->route('landing-pages.edit', $page)->with('success', 'Đã tạo landing page!');
    }

    public function edit(LandingPage $landingPage): Response
    {
        $accountId = Auth::user()->account_id;
        $webForms = WebForm::where('account_id', $accountId)->where('status', 'active')
            ->select('id', 'name')->get();

        return Inertia::render('LandingPages/Edit', [
            'page' => [
                ...$landingPage->toArray(),
                'conversion_rate' => $landingPage->conversion_rate,
                'public_url' => $landingPage->public_url,
            ],
            'blockTypes' => LandingPage::getBlockTypes(),
            'webForms' => $webForms,
        ]);
    }

    public function update(Request $request, LandingPage $landingPage): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:draft,published,archived',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'blocks' => 'nullable|array',
            'style_settings' => 'nullable|array',
            'web_form_id' => 'nullable|exists:web_forms,id',
        ]);

        $landingPage->update($validated);

        return redirect()->back()->with('success', 'Đã lưu!');
    }

    public function destroy(LandingPage $landingPage): \Illuminate\Http\RedirectResponse
    {
        $landingPage->delete();
        return redirect()->route('landing-pages')->with('success', 'Đã xóa.');
    }

    /**
     * Public render landing page
     */
    public function show(string $slug)
    {
        $page = LandingPage::where('slug', $slug)->where('status', 'published')->firstOrFail();
        $page->increment('visits_count');

        $webForm = null;
        if ($page->web_form_id) {
            $webForm = WebForm::with('fields')->find($page->web_form_id);
        }

        return Inertia::render('LandingPages/Show', [
            'page' => [
                ...$page->toArray(),
                'public_url' => $page->public_url,
            ],
            'webForm' => $webForm,
        ]);
    }
}
