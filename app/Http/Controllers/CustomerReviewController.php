<?php

namespace App\Http\Controllers;

use App\Models\CustomerReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CustomerReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;
        $filters = $request->only('search', 'status', 'platform', 'rating');

        $reviews = CustomerReview::where('account_id', $accountId)
            ->filter($filters)
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($r) => [
                'id' => $r->id,
                'customer_name' => $r->customer_name,
                'customer_company' => $r->customer_company,
                'customer_role' => $r->customer_role,
                'review_text' => $r->review_text,
                'rating' => $r->rating,
                'platform' => $r->platform,
                'service_type' => $r->service_type,
                'status' => $r->status,
                'video_url' => $r->video_url,
                'review_date' => $r->review_date?->format('d/m/Y'),
                'created_at' => $r->created_at->format('d/m/Y'),
            ]);

        $stats = [
            'total' => CustomerReview::where('account_id', $accountId)->count(),
            'approved' => CustomerReview::where('account_id', $accountId)->where('status', 'approved')->count(),
            'featured' => CustomerReview::where('account_id', $accountId)->where('status', 'featured')->count(),
            'pending' => CustomerReview::where('account_id', $accountId)->where('status', 'pending')->count(),
            'avg_rating' => round(CustomerReview::where('account_id', $accountId)->avg('rating') ?? 0, 1),
        ];

        return Inertia::render('CustomerReviews/Index', [
            'reviews' => $reviews,
            'stats' => $stats,
            'platforms' => CustomerReview::getPlatforms(),
            'serviceTypes' => CustomerReview::getServiceTypes(),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_company' => 'nullable|string|max:255',
            'customer_role' => 'nullable|string|max:255',
            'review_text' => 'required|string',
            'rating' => 'integer|min:1|max:5',
            'platform' => 'nullable|string',
            'service_type' => 'nullable|string',
            'status' => 'nullable|in:pending,approved,featured,rejected',
            'video_url' => 'nullable|url',
            'review_date' => 'nullable|date',
        ]);

        CustomerReview::create([
            'account_id' => Auth::user()->account_id,
            ...$validated,
        ]);

        return redirect()->back()->with('success', 'Đã thêm review!');
    }

    public function update(Request $request, CustomerReview $customerReview): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_company' => 'nullable|string|max:255',
            'customer_role' => 'nullable|string|max:255',
            'review_text' => 'required|string',
            'rating' => 'integer|min:1|max:5',
            'platform' => 'nullable|string',
            'service_type' => 'nullable|string',
            'status' => 'nullable|in:pending,approved,featured,rejected',
            'video_url' => 'nullable|url',
        ]);

        $customerReview->update($validated);
        return redirect()->back()->with('success', 'Đã cập nhật!');
    }

    public function destroy(CustomerReview $customerReview): \Illuminate\Http\RedirectResponse
    {
        $customerReview->delete();
        return redirect()->back()->with('success', 'Đã xóa.');
    }

    public function updateStatus(Request $request, CustomerReview $customerReview): \Illuminate\Http\RedirectResponse
    {
        $customerReview->update(['status' => $request->input('status')]);
        return redirect()->back()->with('success', 'Đã cập nhật status.');
    }
}
