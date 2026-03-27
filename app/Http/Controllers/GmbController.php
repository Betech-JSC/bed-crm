<?php

namespace App\Http\Controllers;

use App\Models\GmbLocation;
use App\Models\GmbPost;
use App\Models\GmbReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class GmbController extends Controller
{
    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;

        $locations = GmbLocation::where('account_id', $accountId)
            ->withCount(['reviews', 'posts'])
            ->latest()
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'location_name' => $l->location_name,
                'address' => $l->address,
                'phone' => $l->phone,
                'website' => $l->website,
                'category' => $l->category,
                'avg_rating' => $l->avg_rating,
                'review_count' => $l->review_count,
                'total_views' => $l->total_views,
                'total_searches' => $l->total_searches,
                'total_actions' => $l->total_actions,
                'reviews_count' => $l->reviews_count,
                'posts_count' => $l->posts_count,
                'status' => $l->status,
                'last_synced_at' => $l->last_synced_at?->format('d/m/Y H:i'),
            ]);

        $selectedId = $request->input('location_id', $locations->first()['id'] ?? null);
        $reviews = [];
        $posts = [];

        if ($selectedId) {
            $reviews = GmbReview::where('gmb_location_id', $selectedId)
                ->latest('review_time')
                ->limit(20)
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'reviewer_name' => $r->reviewer_name,
                    'rating' => $r->rating,
                    'comment' => $r->comment,
                    'reply' => $r->reply,
                    'replied_at' => $r->replied_at?->format('d/m/Y'),
                    'review_time' => $r->review_time?->format('d/m/Y'),
                ]);

            $posts = GmbPost::where('gmb_location_id', $selectedId)
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'post_type' => $p->post_type,
                    'content' => $p->content,
                    'cta_type' => $p->cta_type,
                    'status' => $p->status,
                    'published_at' => $p->published_at?->format('d/m/Y'),
                ]);
        }

        $stats = [
            'total_locations' => $locations->count(),
            'total_reviews' => GmbReview::whereHas('location', fn ($q) => $q->where('account_id', $accountId))->count(),
            'avg_rating' => round(GmbLocation::where('account_id', $accountId)->avg('avg_rating') ?? 0, 1),
            'total_views' => GmbLocation::where('account_id', $accountId)->sum('total_views'),
        ];

        return Inertia::render('GmbDashboard/Index', [
            'locations' => $locations,
            'reviews' => $reviews,
            'posts' => $posts,
            'stats' => $stats,
            'postTypes' => GmbPost::getPostTypes(),
            'selectedLocationId' => $selectedId,
        ]);
    }

    public function storeLocation(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'location_name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url',
            'category' => 'nullable|string|max:255',
            'google_location_id' => 'nullable|string',
            'place_id' => 'nullable|string',
        ]);

        GmbLocation::create(['account_id' => Auth::user()->account_id, ...$validated]);
        return redirect()->back()->with('success', 'Đã thêm location!');
    }

    public function replyReview(Request $request, GmbReview $gmbReview): \Illuminate\Http\RedirectResponse
    {
        $gmbReview->update([
            'reply' => $request->input('reply'),
            'replied_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Đã trả lời review.');
    }

    public function storePost(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'gmb_location_id' => 'required|exists:gmb_locations,id',
            'post_type' => 'in:update,event,offer',
            'content' => 'required|string',
            'cta_type' => 'nullable|in:learn_more,book,order,call',
            'cta_url' => 'nullable|url',
        ]);

        GmbPost::create([...$validated, 'status' => 'draft']);
        return redirect()->back()->with('success', 'Đã tạo bài đăng.');
    }

    public function deleteLocation(GmbLocation $gmbLocation): \Illuminate\Http\RedirectResponse
    {
        $gmbLocation->delete();
        return redirect()->back()->with('success', 'Đã xóa.');
    }
}
