<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Team;
use App\Models\EmployeeProfile;
use App\Models\OrgPosition;
use App\Models\OrgStructureSnapshot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OrgStructureController extends Controller
{
    /**
     * GET /org-structure — Org chart view
     */
    public function index(): Response
    {
        $accountId = Auth::user()->account_id;

        $departments = Department::where('account_id', $accountId)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with([
                'children.teams.activeMembers.user',
                'teams.activeMembers.user',
                'head',
                'employees' => fn ($q) => $q->where('status', 'active')->with('user'),
            ])
            ->orderBy('sort_order')
            ->get();

        $allDepartments = Department::where('account_id', $accountId)->where('is_active', true)->get();
        $allTeams = Team::where('account_id', $accountId)->where('is_active', true)->with('department')->get();

        $stats = [
            'total_departments' => $allDepartments->count(),
            'total_teams' => $allTeams->count(),
            'total_employees' => EmployeeProfile::where('account_id', $accountId)->where('status', 'active')->count(),
            'total_positions' => OrgPosition::where('account_id', $accountId)->where('is_active', true)->count(),
        ];

        return Inertia::render('OrgStructure/Index', [
            'departments' => $departments,
            'stats' => $stats,
            'allDepartments' => $allDepartments,
            'allTeams' => $allTeams,
        ]);
    }

    /**
     * POST /departments — Create department
     */
    public function storeDepartment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:departments,id',
            'head_user_id' => 'nullable|exists:users,id',
            'color' => 'nullable|string|max:7',
            'budget_monthly' => 'nullable|numeric|min:0',
            'budget_yearly' => 'nullable|numeric|min:0',
        ]);

        $validated['account_id'] = Auth::user()->account_id;
        Department::create($validated);

        return back()->with('success', 'Department created successfully.');
    }

    /**
     * PUT /departments/{department}
     */
    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:departments,id',
            'head_user_id' => 'nullable|exists:users,id',
            'color' => 'nullable|string|max:7',
            'budget_monthly' => 'nullable|numeric|min:0',
            'budget_yearly' => 'nullable|numeric|min:0',
            'sort_order' => 'nullable|integer',
        ]);

        $department->update($validated);
        return back()->with('success', 'Department updated.');
    }

    /**
     * DELETE /departments/{department}
     */
    public function destroyDepartment(Department $department): RedirectResponse
    {
        $department->delete();
        return back()->with('success', 'Department archived.');
    }

    /**
     * POST /teams
     */
    public function storeTeam(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'description' => 'nullable|string',
            'leader_user_id' => 'nullable|exists:users,id',
            'color' => 'nullable|string|max:7',
            'capacity' => 'nullable|integer|min:1',
        ]);

        $validated['account_id'] = Auth::user()->account_id;
        Team::create($validated);

        return back()->with('success', 'Team created successfully.');
    }

    /**
     * PUT /teams/{team}
     */
    public function updateTeam(Request $request, Team $team): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'description' => 'nullable|string',
            'leader_user_id' => 'nullable|exists:users,id',
            'color' => 'nullable|string|max:7',
            'capacity' => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer',
        ]);

        $team->update($validated);
        return back()->with('success', 'Team updated.');
    }

    /**
     * DELETE /teams/{team}
     */
    public function destroyTeam(Team $team): RedirectResponse
    {
        $team->delete();
        return back()->with('success', 'Team archived.');
    }

    /**
     * PUT /employees/{employee}/assign — Assign employee to dept/team
     */
    public function assignEmployee(Request $request, EmployeeProfile $employee): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'team_id' => 'nullable|exists:teams,id',
            'org_position_id' => 'nullable|exists:org_positions,id',
            'reports_to_user_id' => 'nullable|exists:users,id',
        ]);

        $employee->update($validated);
        return back()->with('success', 'Employee assignment updated.');
    }

    /**
     * POST /org-structure/snapshot — Take org snapshot
     */
    public function takeSnapshot(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        OrgStructureSnapshot::capture(
            Auth::user()->account_id,
            $validated['name'],
            $validated['description'] ?? null
        );

        return back()->with('success', 'Org structure snapshot saved.');
    }

    /**
     * GET /org-structure/snapshots — View snapshots
     */
    public function snapshots(): Response
    {
        $snapshots = OrgStructureSnapshot::where('account_id', Auth::user()->account_id)
            ->with('createdByUser')
            ->orderByDesc('snapshot_date')
            ->paginate(20);

        return Inertia::render('OrgStructure/Snapshots', [
            'snapshots' => $snapshots,
        ]);
    }

    /**
     * POST /org-structure/reorder — Drag-and-drop reorder
     */
    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            'items.*.sort_order' => 'required|integer',
            'items.*.parent_id' => 'nullable|integer',
            'type' => 'required|in:department,team',
        ]);

        foreach ($validated['items'] as $item) {
            if ($validated['type'] === 'department') {
                Department::where('id', $item['id'])->update([
                    'sort_order' => $item['sort_order'],
                    'parent_id' => $item['parent_id'] ?? null,
                ]);
            } else {
                Team::where('id', $item['id'])->update([
                    'sort_order' => $item['sort_order'],
                ]);
            }
        }

        return back()->with('success', 'Org structure updated.');
    }
}
