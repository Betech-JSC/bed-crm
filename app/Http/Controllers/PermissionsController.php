<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class PermissionsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Permissions/Index', [
            'permissions' => Auth::user()->account->permissions()
                ->orderBy('group')
                ->orderBy('name')
                ->get()
                ->map(fn ($permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'slug' => $permission->slug,
                    'group' => $permission->group,
                    'description' => $permission->description,
                    'is_active' => $permission->is_active,
                    'roles_count' => $permission->roles()->count(),
                    'created_at' => $permission->created_at->format('Y-m-d H:i'),
                ]),
            'groups' => Auth::user()->account->permissions()
                ->whereNotNull('group')
                ->distinct()
                ->pluck('group')
                ->sort()
                ->values(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Permissions/Create', [
            'groups' => $this->getDefaultGroups(),
        ]);
    }

    public function store(): RedirectResponse
    {
        $validated = Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:permissions,slug'],
            'group' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['account_id'] = Auth::user()->account_id;
        $validated['slug'] = strtolower(str_replace(' ', '-', $validated['slug']));

        Permission::create($validated);

        return Redirect::route('permissions.index')->with('success', 'Permission created.');
    }

    public function edit(Permission $permission): Response
    {
        // Ensure permission belongs to user's account
        if ($permission->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        return Inertia::render('Permissions/Edit', [
            'permission' => [
                'id' => $permission->id,
                'name' => $permission->name,
                'slug' => $permission->slug,
                'group' => $permission->group,
                'description' => $permission->description,
                'is_active' => $permission->is_active,
            ],
            'groups' => $this->getDefaultGroups(),
        ]);
    }

    public function update(Permission $permission): RedirectResponse
    {
        // Ensure permission belongs to user's account
        if ($permission->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $validated = Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:permissions,slug,' . $permission->id],
            'group' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['slug'] = strtolower(str_replace(' ', '-', $validated['slug']));

        $permission->update($validated);

        return Redirect::route('permissions.index')->with('success', 'Permission updated.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        // Ensure permission belongs to user's account
        if ($permission->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $permission->delete();

        return Redirect::route('permissions.index')->with('success', 'Permission deleted.');
    }

    private function getDefaultGroups(): array
    {
        return [
            'leads',
            'deals',
            'contacts',
            'organizations',
            'proposals',
            'sales-playbooks',
            'reports',
            'icps',
            'workflows',
            'sla-settings',
            'users',
            'content-templates',
            'content-items',
            'social-accounts',
            'social-posts',
            'files',
            'chat-widgets',
            'chat-conversations',
            'email-templates',
            'email-lists',
            'email-campaigns',
            'email-automations',
            'account-settings',
            'smtp-settings',
        ];
    }
}
