<?php

namespace App\Http\Controllers;

use App\Models\AiTrendItem;
use App\Models\AiTrendMonitor;
use App\Services\AiTrendService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;

class AiTrendController extends Controller
{
    public function __construct(private AiTrendService $trendService) {}

    /**
     * Main AI Trends dashboard page.
     */
    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;

        $query = AiTrendItem::where('account_id', $accountId)
            ->filter($request->only('source', 'language', 'search', 'is_read', 'is_pinned'))
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at');

        $items = $query->paginate(30)->withQueryString();

        $monitors = AiTrendMonitor::where('account_id', $accountId)
            ->with('creator')
            ->orderByDesc('created_at')
            ->get();

        $stats = $this->trendService->getDashboardStats($accountId);

        // Get unique languages from items
        $languages = AiTrendItem::where('account_id', $accountId)
            ->whereNotNull('language')
            ->distinct()
            ->pluck('language')
            ->sort()
            ->values();

        return Inertia::render('AiTrends/Index', [
            'items' => $items,
            'monitors' => $monitors,
            'stats' => $stats,
            'languages' => $languages,
            'sources' => AiTrendMonitor::getSources(),
            'frequencies' => AiTrendMonitor::getFrequencies(),
            'filters' => $request->only('source', 'language', 'search', 'is_read', 'is_pinned'),
        ]);
    }

    /**
     * AI Ecosystem Map — 13-layer overview of AI tools & platforms.
     */
    public function ecosystem(): Response
    {
        return Inertia::render('AiTrends/EcosystemMap');
    }

    /**
     * Create a new trend monitor.
     */
    public function storeMonitor(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'source' => 'required|in:github,hackernews,producthunt,devto',
            'source_config' => 'nullable|array',
            'schedule_frequency' => 'required|in:hourly,every_6h,every_12h,daily,weekly',
            'schedule_time' => 'nullable|string',
            'schedule_day' => 'nullable|string',
            'notify_in_app' => 'boolean',
            'notify_email' => 'boolean',
        ]);

        $monitor = AiTrendMonitor::create([
            'account_id' => Auth::user()->account_id,
            'created_by' => Auth::id(),
            'name' => $request->name,
            'source' => $request->source,
            'source_config' => $request->source_config ?? [],
            'schedule_frequency' => $request->schedule_frequency,
            'schedule_time' => $request->schedule_time ?? '09:00',
            'schedule_day' => $request->schedule_day,
            'notify_in_app' => $request->notify_in_app ?? true,
            'notify_email' => $request->notify_email ?? false,
            'is_active' => true,
            'next_run_at' => now(), // Run immediately on first create
        ]);

        return response()->json([
            'success' => true,
            'monitor' => $monitor->load('creator'),
        ]);
    }

    /**
     * Update a monitor.
     */
    public function updateMonitor(Request $request, AiTrendMonitor $monitor): JsonResponse
    {
        if ($monitor->account_id !== Auth::user()->account_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'string|max:255',
            'source_config' => 'nullable|array',
            'schedule_frequency' => 'in:hourly,every_6h,every_12h,daily,weekly',
            'schedule_time' => 'nullable|string',
            'schedule_day' => 'nullable|string',
            'notify_in_app' => 'boolean',
            'notify_email' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $monitor->update($request->only([
            'name', 'source_config', 'schedule_frequency',
            'schedule_time', 'schedule_day',
            'notify_in_app', 'notify_email', 'is_active',
        ]));

        return response()->json([
            'success' => true,
            'monitor' => $monitor->refresh()->load('creator'),
        ]);
    }

    /**
     * Delete a monitor and its items.
     */
    public function destroyMonitor(AiTrendMonitor $monitor): JsonResponse
    {
        if ($monitor->account_id !== Auth::user()->account_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $monitor->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Manually trigger a fetch for a monitor.
     */
    public function triggerFetch(AiTrendMonitor $monitor): JsonResponse
    {
        if ($monitor->account_id !== Auth::user()->account_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $items = $this->trendService->fetchForMonitor($monitor);
            return response()->json([
                'success' => true,
                'new_items' => count($items),
                'message' => count($items) > 0
                    ? "Đã tìm thấy " . count($items) . " mục mới!"
                    : "Không có mục mới.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Lỗi khi tải dữ liệu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle pin/unpin an item.
     */
    public function togglePin(AiTrendItem $item): JsonResponse
    {
        if ($item->account_id !== Auth::user()->account_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $item->update(['is_pinned' => !$item->is_pinned]);
        return response()->json(['success' => true, 'is_pinned' => $item->is_pinned]);
    }

    /**
     * Mark item as read.
     */
    public function markRead(AiTrendItem $item): JsonResponse
    {
        if ($item->account_id !== Auth::user()->account_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $item->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }

    /**
     * Mark all items as read.
     */
    public function markAllRead(): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        $count = AiTrendItem::where('account_id', $accountId)
            ->unread()
            ->update(['is_read' => true]);

        return response()->json(['success' => true, 'marked' => $count]);
    }

    /**
     * Delete a trend item.
     */
    public function destroyItem(AiTrendItem $item): JsonResponse
    {
        if ($item->account_id !== Auth::user()->account_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $item->delete();
        return response()->json(['success' => true]);
    }
}
