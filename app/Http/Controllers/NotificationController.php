<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    /**
     * Notification Center page.
     */
    public function index(Request $request): Response
    {
        $userId = Auth::id();
        $accountId = Auth::user()->account_id;

        $query = Notification::forUser($userId)
            ->filter($request->only('event_type', 'severity', 'read'))
            ->orderByDesc('created_at');

        $notifications = $query->paginate(30)->withQueryString();
        $unreadCount = Notification::forUser($userId)->unread()->count();

        // Event type options for filter
        $eventTypes = collect(Notification::getEventTypes())->map(fn ($meta, $key) => [
            'value' => $key,
            'label_vi' => $meta['label_vi'],
            'label_en' => $meta['label_en'],
            'icon' => $meta['icon'],
            'severity' => $meta['severity'],
        ])->values()->toArray();

        // User preferences
        $preferences = NotificationPreference::where('user_id', $userId)
            ->get()
            ->keyBy('event_type')
            ->toArray();

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'event_types' => $eventTypes,
            'preferences' => $preferences,
            'filters' => $request->only('event_type', 'severity', 'read'),
        ]);
    }

    /**
     * API: Get recent notifications (for header bell).
     */
    public function recent(Request $request): JsonResponse
    {
        $data = $this->notificationService->getUserNotifications(Auth::id(), 15);
        return response()->json($data);
    }

    /**
     * API: Mark single notification as read.
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->markAsRead();
        return response()->json(['success' => true]);
    }

    /**
     * API: Mark all as read.
     */
    public function markAllAsRead(): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead(Auth::id());
        return response()->json(['marked' => $count]);
    }

    /**
     * API: Update notification preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $request->validate([
            'preferences' => 'required|array',
            'preferences.*.event_type' => 'required|string',
            'preferences.*.in_app' => 'required|boolean',
            'preferences.*.email' => 'required|boolean',
        ]);

        $userId = Auth::id();
        $accountId = Auth::user()->account_id;

        foreach ($request->preferences as $pref) {
            NotificationPreference::updateOrCreate(
                ['user_id' => $userId, 'event_type' => $pref['event_type']],
                ['account_id' => $accountId, 'in_app' => $pref['in_app'], 'email' => $pref['email']]
            );
        }

        return response()->json(['success' => true]);
    }

    /**
     * API: Delete a notification.
     */
    public function destroy(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Admin: Notification logs.
     */
    public function logs(Request $request): Response
    {
        $accountId = Auth::user()->account_id;

        $logs = NotificationLog::where('account_id', $accountId)
            ->with('recipientUser')
            ->orderByDesc('created_at')
            ->paginate(50);

        return Inertia::render('Notifications/Logs', [
            'logs' => $logs,
        ]);
    }
}
