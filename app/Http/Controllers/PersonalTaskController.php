<?php

namespace App\Http\Controllers;

use App\Models\PersonalTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PersonalTaskController extends Controller
{
    /**
     * Main dashboard with Kanban + List view
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();

        $tasks = PersonalTask::forUser($user->id)
            ->where('account_id', $user->account_id)
            ->filter($request->only('search', 'status', 'priority', 'category', 'due'))
            ->orderByDesc('is_pinned')
            ->orderByRaw("CASE WHEN status = 'in_progress' THEN 0 WHEN status = 'todo' THEN 1 WHEN status = 'done' THEN 2 ELSE 3 END")
            ->orderByRaw("CASE WHEN priority = 'urgent' THEN 0 WHEN priority = 'high' THEN 1 WHEN priority = 'medium' THEN 2 ELSE 3 END")
            ->orderBy('due_date')
            ->orderByDesc('updated_at')
            ->paginate(50)
            ->withQueryString()
            ->through(fn ($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'description' => $t->description ? substr($t->description, 0, 150) : null,
                'status' => $t->status,
                'status_info' => $t->status_info,
                'priority' => $t->priority,
                'priority_info' => $t->priority_info,
                'category' => $t->category,
                'category_info' => PersonalTask::getCategories()[$t->category] ?? null,
                'color' => $t->color,
                'due_date' => $t->due_date?->format('d/m/Y'),
                'due_date_iso' => $t->due_date?->toDateString(),
                'is_overdue' => $t->is_overdue,
                'completed_at' => $t->completed_at?->format('d/m/Y H:i'),
                'checklist' => $t->checklist ?? [],
                'checklist_progress' => $t->checklist_progress,
                'tags' => $t->tags ?? [],
                'is_pinned' => $t->is_pinned,
                'related_type' => $t->related_type,
                'related_id' => $t->related_id,
                'created_at' => $t->created_at->diffForHumans(),
                'updated_at' => $t->updated_at->diffForHumans(),
            ]);

        // Stats
        $base = PersonalTask::forUser($user->id)->where('account_id', $user->account_id);
        $stats = [
            'total' => (clone $base)->count(),
            'todo' => (clone $base)->where('status', 'todo')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'done' => (clone $base)->where('status', 'done')->count(),
            'overdue' => (clone $base)->where('status', '!=', 'done')->whereDate('due_date', '<', today())->count(),
            'today' => (clone $base)->where('status', '!=', 'done')->whereDate('due_date', today())->count(),
            'this_week' => (clone $base)->where('status', '!=', 'done')
                ->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];

        return Inertia::render('PersonalTasks/Index', [
            'tasks' => $tasks,
            'stats' => $stats,
            'statuses' => PersonalTask::getStatuses(),
            'priorities' => PersonalTask::getPriorities(),
            'categories' => PersonalTask::getCategories(),
            'filters' => $request->only('search', 'status', 'priority', 'category', 'due'),
        ]);
    }

    /**
     * Store new task
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'status' => 'nullable|in:todo,in_progress,done,cancelled',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'category' => 'nullable|in:work,personal,meeting,follow_up,other',
            'color' => 'nullable|string|max:20',
            'due_date' => 'nullable|date',
            'reminder_at' => 'nullable|date',
            'related_type' => 'nullable|string',
            'related_id' => 'nullable|integer',
            'checklist' => 'nullable|array',
            'tags' => 'nullable|array',
        ]);

        $user = Auth::user();

        $task = PersonalTask::create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'todo',
            'priority' => $validated['priority'] ?? 'medium',
            'category' => $validated['category'] ?? 'work',
            'color' => $validated['color'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'reminder_at' => $validated['reminder_at'] ?? null,
            'related_type' => $validated['related_type'] ?? null,
            'related_id' => $validated['related_id'] ?? null,
            'checklist' => $validated['checklist'] ?? [],
            'tags' => $validated['tags'] ?? [],
        ]);

        return response()->json([
            'success' => true,
            'task' => $this->formatTask($task),
        ]);
    }

    /**
     * Update task
     */
    public function update(Request $request, PersonalTask $personalTask): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'status' => 'nullable|in:todo,in_progress,done,cancelled',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'category' => 'nullable|in:work,personal,meeting,follow_up,other',
            'color' => 'nullable|string|max:20',
            'due_date' => 'nullable|date',
            'reminder_at' => 'nullable|date',
            'related_type' => 'nullable|string',
            'related_id' => 'nullable|integer',
            'checklist' => 'nullable|array',
            'tags' => 'nullable|array',
            'is_pinned' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        // Auto-set completed_at
        if (isset($validated['status']) && $validated['status'] === 'done' && !$personalTask->completed_at) {
            $validated['completed_at'] = now();
        } elseif (isset($validated['status']) && $validated['status'] !== 'done') {
            $validated['completed_at'] = null;
        }

        $personalTask->update($validated);

        return response()->json([
            'success' => true,
            'task' => $this->formatTask($personalTask->fresh()),
        ]);
    }

    /**
     * Quick toggle status
     */
    public function toggleStatus(PersonalTask $personalTask): JsonResponse
    {
        $newStatus = $personalTask->status === 'done' ? 'todo' : 'done';
        $personalTask->update([
            'status' => $newStatus,
            'completed_at' => $newStatus === 'done' ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'status' => $newStatus,
        ]);
    }

    /**
     * Toggle pin
     */
    public function togglePin(PersonalTask $personalTask): JsonResponse
    {
        $personalTask->update(['is_pinned' => !$personalTask->is_pinned]);
        return response()->json(['success' => true, 'is_pinned' => $personalTask->is_pinned]);
    }

    /**
     * Delete task
     */
    public function destroy(PersonalTask $personalTask): JsonResponse
    {
        $personalTask->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Bulk update (for drag-drop reorder, bulk complete)
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'action' => 'required|in:complete,delete,priority,status',
            'value' => 'nullable|string',
        ]);

        $user = Auth::user();
        $query = PersonalTask::whereIn('id', $validated['ids'])->where('user_id', $user->id);

        switch ($validated['action']) {
            case 'complete':
                $query->update(['status' => 'done', 'completed_at' => now()]);
                break;
            case 'delete':
                $query->delete();
                break;
            case 'priority':
                $query->update(['priority' => $validated['value']]);
                break;
            case 'status':
                $data = ['status' => $validated['value']];
                if ($validated['value'] === 'done') $data['completed_at'] = now();
                else $data['completed_at'] = null;
                $query->update($data);
                break;
        }

        return response()->json(['success' => true]);
    }

    // ── Private ──
    private function formatTask(PersonalTask $t): array
    {
        return [
            'id' => $t->id,
            'title' => $t->title,
            'description' => $t->description,
            'status' => $t->status,
            'status_info' => $t->status_info,
            'priority' => $t->priority,
            'priority_info' => $t->priority_info,
            'category' => $t->category,
            'category_info' => PersonalTask::getCategories()[$t->category] ?? null,
            'color' => $t->color,
            'due_date' => $t->due_date?->format('d/m/Y'),
            'due_date_iso' => $t->due_date?->toDateString(),
            'is_overdue' => $t->is_overdue,
            'completed_at' => $t->completed_at?->format('d/m/Y H:i'),
            'checklist' => $t->checklist ?? [],
            'checklist_progress' => $t->checklist_progress,
            'tags' => $t->tags ?? [],
            'is_pinned' => $t->is_pinned,
            'created_at' => $t->created_at->diffForHumans(),
            'updated_at' => $t->updated_at->diffForHumans(),
        ];
    }
}
