<?php

namespace App\Http\Controllers;

use App\Models\LinkBioPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LinkBioController extends Controller
{
    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;

        $pages = LinkBioPage::where('account_id', $accountId)
            ->latest()
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'username' => $p->username,
                'display_name' => $p->display_name,
                'bio' => $p->bio,
                'theme' => $p->theme,
                'links_count' => count($p->links ?? []),
                'total_views' => $p->total_views,
                'total_clicks' => $p->total_clicks,
                'ctr' => $p->ctr,
                'public_url' => $p->public_url,
                'updated_at' => $p->updated_at->format('d/m/Y'),
            ]);

        return Inertia::render('LinkBio/Index', [
            'pages' => $pages,
            'themes' => LinkBioPage::getThemes(),
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:link_bio_pages,username|regex:/^[a-z0-9_-]+$/',
            'display_name' => 'required|string|max:255',
            'bio' => 'nullable|string|max:500',
            'theme' => 'nullable|string',
            'links' => 'nullable|array',
            'social_links' => 'nullable|array',
            'style_settings' => 'nullable|array',
        ]);

        $user = Auth::user();
        $page = LinkBioPage::create([
            'account_id' => $user->account_id,
            'created_by' => $user->id,
            ...$validated,
        ]);

        return redirect()->back()->with('success', 'Đã tạo Link-in-Bio!');
    }

    public function update(Request $request, LinkBioPage $linkBioPage): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'bio' => 'nullable|string|max:500',
            'theme' => 'nullable|string',
            'links' => 'nullable|array',
            'social_links' => 'nullable|array',
            'style_settings' => 'nullable|array',
        ]);

        $linkBioPage->update($validated);
        return redirect()->back()->with('success', 'Đã lưu!');
    }

    public function destroy(LinkBioPage $linkBioPage): \Illuminate\Http\RedirectResponse
    {
        $linkBioPage->delete();
        return redirect()->back()->with('success', 'Đã xóa.');
    }

    public function show(string $username)
    {
        $page = LinkBioPage::where('username', $username)->firstOrFail();
        $page->increment('total_views');

        return Inertia::render('LinkBio/Show', [
            'page' => [
                ...$page->toArray(),
                'public_url' => $page->public_url,
            ],
        ]);
    }
}
