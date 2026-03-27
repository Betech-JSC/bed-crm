<?php

namespace App\Http\Controllers;

use App\Models\ContentCalendar;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ContentCalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;
        $filters = $request->only('search', 'status', 'content_type', 'channel', 'month');

        $items = ContentCalendar::where('account_id', $accountId)
            ->filter($filters)
            ->latest('planned_date')
            ->paginate(30)
            ->withQueryString()
            ->through(fn ($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'description' => $c->description,
                'content_type' => $c->content_type,
                'channel' => $c->channel,
                'status' => $c->status,
                'priority' => $c->priority,
                'planned_date' => $c->planned_date?->format('Y-m-d'),
                'planned_display' => $c->planned_date?->format('d/m'),
                'published_at' => $c->published_at?->format('d/m/Y H:i'),
                'tags' => $c->tags,
                'views_count' => $c->views_count,
                'clicks_count' => $c->clicks_count,
                'shares_count' => $c->shares_count,
                'leads_count' => $c->leads_count,
                'assigned_to' => $c->assigned_to,
            ]);

        // Calendar view data — group by date
        $calendarData = ContentCalendar::where('account_id', $accountId)
            ->whereNotNull('planned_date')
            ->whereMonth('planned_date', $request->input('cal_month', now()->month))
            ->whereYear('planned_date', $request->input('cal_year', now()->year))
            ->get()
            ->groupBy(fn ($c) => $c->planned_date->format('Y-m-d'))
            ->map(fn ($group) => $group->map(fn ($c) => [
                'id' => $c->id, 'title' => $c->title,
                'content_type' => $c->content_type, 'channel' => $c->channel,
                'status' => $c->status, 'priority' => $c->priority,
            ]));

        // Stats
        $stats = [
            'total' => ContentCalendar::where('account_id', $accountId)->count(),
            'published' => ContentCalendar::where('account_id', $accountId)->where('status', 'published')->count(),
            'in_progress' => ContentCalendar::where('account_id', $accountId)->where('status', 'in_progress')->count(),
            'planned' => ContentCalendar::where('account_id', $accountId)->where('status', 'planned')->count(),
            'this_month' => ContentCalendar::where('account_id', $accountId)->whereMonth('planned_date', now()->month)->count(),
        ];

        $users = User::where('account_id', $accountId)->select('id', 'first_name', 'last_name')->get();

        return Inertia::render('ContentCalendar/Index', [
            'items' => $items,
            'calendarData' => $calendarData,
            'stats' => $stats,
            'contentTypes' => ContentCalendar::getContentTypes(),
            'channels' => ContentCalendar::getChannels(),
            'statuses' => ContentCalendar::getStatuses(),
            'users' => $users,
            'filters' => $filters,
            'currentMonth' => (int) $request->input('cal_month', now()->month),
            'currentYear' => (int) $request->input('cal_year', now()->year),
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content_type' => 'required|string',
            'channel' => 'required|string',
            'status' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'planned_date' => 'nullable|date',
            'content_body' => 'nullable|string',
            'tags' => 'nullable|array',
            'seo_meta' => 'nullable|array',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $user = Auth::user();
        ContentCalendar::create([
            'account_id' => $user->account_id,
            'created_by' => $user->id,
            ...$validated,
        ]);

        return redirect()->back()->with('success', 'Đã tạo nội dung!');
    }

    public function update(Request $request, ContentCalendar $contentCalendar): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content_type' => 'required|string',
            'channel' => 'required|string',
            'status' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'planned_date' => 'nullable|date',
            'content_body' => 'nullable|string',
            'tags' => 'nullable|array',
            'seo_meta' => 'nullable|array',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if ($validated['status'] === 'published' && !$contentCalendar->published_at) {
            $validated['published_at'] = now();
        }

        $contentCalendar->update($validated);
        return redirect()->back()->with('success', 'Đã cập nhật!');
    }

    public function destroy(ContentCalendar $contentCalendar): \Illuminate\Http\RedirectResponse
    {
        $contentCalendar->delete();
        return redirect()->back()->with('success', 'Đã xóa.');
    }
}
