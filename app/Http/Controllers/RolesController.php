<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class RolesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Roles/Index', [
            'roles' => Auth::user()->account->roles()
                ->withCount(['users', 'permissions'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'description' => $role->description,
                    'is_active' => $role->is_active,
                    'is_system' => $role->is_system,
                    'users_count' => $role->users_count,
                    'permissions_count' => $role->permissions_count,
                    'created_at' => $role->created_at->format('Y-m-d H:i'),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Roles/Create', [
            'permissions' => Auth::user()->account->permissions()
                ->where('is_active', true)
                ->orderBy('group')
                ->orderBy('name')
                ->get()
                ->groupBy('group')
                ->map(fn ($permissions, $group) => [
                    'group' => $group ?? 'Other',
                    'permissions' => $permissions->map(fn ($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'slug' => $p->slug,
                    ]),
                ])
                ->values(),
        ]);
    }

    public function store(): RedirectResponse
    {
        $validated = Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $validated['account_id'] = Auth::user()->account_id;
        $validated['created_by'] = Auth::id();
        $validated['slug'] = strtolower(str_replace(' ', '-', $validated['slug']));
        $validated['users_count'] = 0;

        $permissions = $validated['permissions'] ?? [];
        unset($validated['permissions']);

        $role = Role::create($validated);

        if (!empty($permissions)) {
            $role->permissions()->sync($permissions);
        }

        return Redirect::route('roles.show', $role)->with('success', 'Role created.');
    }

    public function show(Role $role): Response
    {
        // Ensure role belongs to user's account
        if ($role->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $role->load(['permissions', 'users']);

        return Inertia::render('Roles/Show', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'is_active' => $role->is_active,
                'is_system' => $role->is_system,
                'users_count' => $role->users_count,
            ],
            'permissions' => $role->permissions->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'group' => $p->group,
            ]),
            'users' => $role->users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ]),
            'availablePermissions' => Auth::user()->account->permissions()
                ->where('is_active', true)
                ->orderBy('group')
                ->orderBy('name')
                ->get()
                ->groupBy('group')
                ->map(fn ($permissions, $group) => [
                    'group' => $group ?? 'Other',
                    'permissions' => $permissions->map(fn ($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'slug' => $p->slug,
                    ]),
                ])
                ->values(),
        ]);
    }

    public function edit(Role $role): Response
    {
        // Ensure role belongs to user's account
        if ($role->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $role->load('permissions');

        return Inertia::render('Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'is_active' => $role->is_active,
                'is_system' => $role->is_system,
            ],
            'selectedPermissions' => $role->permissions->pluck('id')->toArray(),
            'permissions' => Auth::user()->account->permissions()
                ->where('is_active', true)
                ->orderBy('group')
                ->orderBy('name')
                ->get()
                ->groupBy('group')
                ->map(fn ($permissions, $group) => [
                    'group' => $group ?? 'Other',
                    'permissions' => $permissions->map(fn ($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'slug' => $p->slug,
                    ]),
                ])
                ->values(),
        ]);
    }

    public function update(Role $role): RedirectResponse
    {
        // Ensure role belongs to user's account
        if ($role->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        // Can't edit system roles
        if ($role->is_system) {
            return Redirect::back()->with('error', 'Cannot edit system roles.');
        }

        $validated = Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $validated['slug'] = strtolower(str_replace(' ', '-', $validated['slug']));

        $permissions = $validated['permissions'] ?? [];
        unset($validated['permissions']);

        $role->update($validated);

        if (isset($permissions)) {
            $role->permissions()->sync($permissions);
        }

        return Redirect::route('roles.show', $role)->with('success', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        // Ensure role belongs to user's account
        if ($role->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        // Can't delete system roles
        if ($role->is_system) {
            return Redirect::back()->with('error', 'Cannot delete system roles.');
        }

        $role->delete();

        return Redirect::route('roles.index')->with('success', 'Role deleted.');
    }
}
