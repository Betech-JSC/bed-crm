<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\ProjectResource;
use App\Models\ProjectTask;
use App\Models\User;
use App\Services\ProjectProfitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function __construct(private ProjectProfitService $profitService) {}

    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;

        $projects = Project::where('account_id', $accountId)
            ->with(['manager:id,first_name,last_name', 'customer:id,name', 'tasks', 'resources', 'expenses'])
            ->filter($request->only('search', 'status', 'manager_id', 'trashed'))
            ->orderByRaw("FIELD(status, 'delayed', 'in_progress', 'on_hold', 'planning', 'completed', 'cancelled')")
            ->paginate(12)
            ->withQueryString()
            ->through(function ($p) {
                $cost = $p->calculateTotalCost();
                $profit = (float) $p->revenue - $cost;
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'status' => $p->status,
                    'priority' => $p->priority,
                    'progress' => $p->progress,
                    'start_date' => $p->start_date?->format('Y-m-d'),
                    'due_date' => $p->due_date?->format('Y-m-d'),
                    'days_remaining' => $p->getDaysRemaining(),
                    'is_overdue' => $p->isOverdue(),
                    'budget' => (float) $p->budget,
                    'revenue' => (float) $p->revenue,
                    'total_cost' => $cost,
                    'profit' => $profit,
                    'margin' => (float) $p->revenue > 0 ? round(($profit / (float) $p->revenue) * 100, 1) : 0,
                    'tasks_count' => $p->tasks->count(),
                    'tasks_done' => $p->tasks->where('status', 'done')->count(),
                    'resources_count' => $p->resources->count(),
                    'manager' => $p->manager ? ['id' => $p->manager->id, 'name' => $p->manager->name] : null,
                    'customer' => $p->customer ? ['id' => $p->customer->id, 'name' => $p->customer->name] : null,
                    'deleted_at' => $p->deleted_at,
                ];
            });

        $analytics = $this->profitService->getPortfolioAnalytics($accountId);

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'analytics' => $analytics,
            'filters' => $request->only('search', 'status', 'manager_id'),
            'statuses' => Project::getStatuses(),
            'salesUsers' => $this->getSalesUsers(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Projects/Create', [
            'statuses' => Project::getStatuses(),
            'priorities' => Project::getPriorities(),
            'customers' => Auth::user()->account->customers()->orderBy('name')->get(['id', 'name']),
            'salesUsers' => $this->getSalesUsers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:5000',
            'status' => 'required|string|max:20',
            'priority' => 'required|string|max:20',
            'customer_id' => 'nullable|integer',
            'manager_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'revenue' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:5000',
        ]);

        $project = Auth::user()->account->projects()->create($validated);

        return Redirect::route('projects.edit', $project)->with('success', 'Project created.');
    }

    public function edit(Project $project): Response
    {
        $project->load(['tasks.assignedUser:id,first_name,last_name', 'resources.user:id,first_name,last_name', 'expenses']);

        $profitData = $this->profitService->calculateProfit($project);

        return Inertia::render('Projects/Edit', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'status' => $project->status,
                'priority' => $project->priority,
                'progress' => $project->progress,
                'customer_id' => $project->customer_id,
                'manager_id' => $project->manager_id,
                'start_date' => $project->start_date?->format('Y-m-d'),
                'due_date' => $project->due_date?->format('Y-m-d'),
                'budget' => (float) $project->budget,
                'revenue' => (float) $project->revenue,
                'notes' => $project->notes,
                'is_overdue' => $project->isOverdue(),
                'days_remaining' => $project->getDaysRemaining(),
                'deleted_at' => $project->deleted_at,
            ],
            'profitData' => $profitData,
            'tasks' => $project->tasks->sortBy('sort_order')->values()->map(fn ($t) => [
                'id' => $t->id, 'title' => $t->title, 'status' => $t->status, 'priority' => $t->priority,
                'due_date' => $t->due_date?->format('Y-m-d'), 'estimated_hours' => $t->estimated_hours,
                'actual_hours' => (float) $t->actual_hours, 'hourly_cost' => (float) $t->hourly_cost,
                'assigned_user' => $t->assignedUser ? ['id' => $t->assignedUser->id, 'name' => $t->assignedUser->name] : null,
            ]),
            'resources' => $project->resources->map(fn ($r) => [
                'id' => $r->id, 'role' => $r->role, 'hourly_rate' => (float) $r->hourly_rate,
                'allocated_hours' => $r->allocated_hours, 'logged_hours' => (float) $r->logged_hours,
                'cost' => $r->getCost(), 'utilization' => $r->getUtilization(),
                'user' => $r->user ? ['id' => $r->user->id, 'name' => $r->user->name] : null,
            ]),
            'expenses' => $project->expenses->sortByDesc('date')->values()->map(fn ($e) => [
                'id' => $e->id, 'description' => $e->description, 'amount' => (float) $e->amount,
                'category' => $e->category, 'date' => $e->date?->format('Y-m-d'),
            ]),
            'statuses' => Project::getStatuses(),
            'priorities' => Project::getPriorities(),
            'taskStatuses' => ProjectTask::getStatuses(),
            'resourceRoles' => ProjectResource::getRoles(),
            'expenseCategories' => ProjectExpense::getCategories(),
            'customers' => Auth::user()->account->customers()->orderBy('name')->get(['id', 'name']),
            'salesUsers' => $this->getSalesUsers(),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:5000',
            'status' => 'required|string|max:20',
            'priority' => 'required|string|max:20',
            'customer_id' => 'nullable|integer',
            'manager_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'budget' => 'nullable|numeric|min:0',
            'revenue' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:5000',
        ]);

        if ($validated['status'] === Project::STATUS_COMPLETED && !$project->completed_at) {
            $validated['completed_at'] = now();
        }

        $project->update($validated);
        $project->update(['progress' => $project->recalculateProgress()]);

        return Redirect::back()->with('success', 'Project updated.');
    }

    // Tasks
    public function storeTask(Request $request, Project $project): RedirectResponse
    {
        $v = $request->validate([
            'title' => 'required|string|max:200',
            'status' => 'string|max:20',
            'priority' => 'string|max:20',
            'assigned_to' => 'nullable|integer',
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|integer|min:0',
            'hourly_cost' => 'nullable|numeric|min:0',
        ]);
        $project->tasks()->create(array_merge($v, ['sort_order' => $project->tasks()->count()]));
        $project->update(['progress' => $project->recalculateProgress()]);
        return Redirect::back()->with('success', 'Task added.');
    }

    public function updateTask(Request $request, Project $project, ProjectTask $task): RedirectResponse
    {
        $v = $request->validate([
            'title' => 'sometimes|string|max:200',
            'status' => 'sometimes|string|max:20',
            'actual_hours' => 'sometimes|numeric|min:0',
            'assigned_to' => 'nullable|integer',
        ]);
        $task->update($v);
        $project->update(['progress' => $project->recalculateProgress()]);
        return Redirect::back()->with('success', 'Task updated.');
    }

    public function destroyTask(Project $project, ProjectTask $task): RedirectResponse
    {
        $task->delete();
        $project->update(['progress' => $project->recalculateProgress()]);
        return Redirect::back()->with('success', 'Task deleted.');
    }

    // Resources
    public function storeResource(Request $request, Project $project): RedirectResponse
    {
        $v = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'role' => 'required|string|max:50',
            'hourly_rate' => 'nullable|numeric|min:0',
            'allocated_hours' => 'nullable|integer|min:0',
        ]);
        $project->resources()->create($v);
        return Redirect::back()->with('success', 'Resource added.');
    }

    public function updateResource(Request $request, Project $project, ProjectResource $resource): RedirectResponse
    {
        $v = $request->validate([
            'logged_hours' => 'sometimes|numeric|min:0',
            'hourly_rate' => 'sometimes|numeric|min:0',
            'allocated_hours' => 'sometimes|integer|min:0',
        ]);
        $resource->update($v);
        return Redirect::back()->with('success', 'Resource updated.');
    }

    // Expenses
    public function storeExpense(Request $request, Project $project): RedirectResponse
    {
        $v = $request->validate([
            'description' => 'required|string|max:200',
            'amount' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:30',
            'date' => 'nullable|date',
        ]);
        $project->expenses()->create($v);
        return Redirect::back()->with('success', 'Expense added.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();
        return Redirect::route('projects')->with('success', 'Project deleted.');
    }

    public function restore(Project $project): RedirectResponse
    {
        $project->restore();
        return Redirect::back()->with('success', 'Project restored.');
    }

    private function getSalesUsers()
    {
        return Auth::user()->account->users()->orderByName()->get()->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);
    }
}
