<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RBACService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RBACController extends Controller
{
    public function __construct(private RBACService $rbac) {}

    // ════════════════════════════════════════════
    //  ROLE MANAGEMENT
    // ════════════════════════════════════════════

    /**
     * Roles & Permissions management page.
     */
    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;
        $locale = app()->getLocale();

        $roles = Role::where('account_id', $accountId)
            ->with('permissions')
            ->withCount('users')
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'description' => $role->description,
                    'is_active' => $role->is_active,
                    'is_system' => $role->is_system,
                    'users_count' => $role->users_count,
                    'permissions' => $role->permissions->pluck('slug')->toArray(),
                    'permissions_count' => $role->permissions->count(),
                ];
            });

        $permissionGroups = $this->rbac->getPermissionsByGroup($accountId, $locale);

        $users = User::where('account_id', $accountId)
            ->with('roles:id,name,slug')
            ->orderByName()
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'owner' => $u->owner,
                'roles' => $u->roles->pluck('slug')->toArray(),
                'role_names' => $u->roles->pluck('name')->toArray(),
            ]);

        return Inertia::render('Settings/RBAC', [
            'roles' => $roles,
            'permission_groups' => $permissionGroups,
            'users' => $users,
        ]);
    }

    /**
     * Create a new role.
     */
    public function createRole(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $role = $this->rbac->createRole(Auth::user()->account_id, $request->only('name', 'description'));

        return response()->json(['role' => $role], 201);
    }

    /**
     * Update a role.
     */
    public function updateRole(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $role = $this->rbac->updateRole($role, $request->only('name', 'description', 'is_active'));

        return response()->json(['role' => $role]);
    }

    /**
     * Delete a role.
     */
    public function deleteRole(Role $role): JsonResponse
    {
        if (!$this->rbac->deleteRole($role)) {
            return response()->json(['error' => 'System roles cannot be deleted'], 422);
        }

        return response()->json(['success' => true]);
    }

    // ════════════════════════════════════════════
    //  PERMISSION ATTACHMENT
    // ════════════════════════════════════════════

    /**
     * Sync permissions for a role (replaces existing).
     */
    public function syncPermissions(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string',
        ]);

        $this->rbac->syncPermissions($role, $request->permissions);
        $role->load('permissions');

        return response()->json([
            'success' => true,
            'permissions' => $role->permissions->pluck('slug')->toArray(),
        ]);
    }

    // ════════════════════════════════════════════
    //  USER-ROLE ASSIGNMENT
    // ════════════════════════════════════════════

    /**
     * Assign roles to a user.
     */
    public function assignRoles(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string',
        ]);

        $this->rbac->syncRoles($user, $request->roles);
        $user->load('roles');

        return response()->json([
            'success' => true,
            'roles' => $user->roles->pluck('slug')->toArray(),
        ]);
    }

    /**
     * Remove a role from a user.
     */
    public function removeRole(Request $request, User $user): JsonResponse
    {
        $request->validate(['role' => 'required|string']);
        $this->rbac->removeRole($user, $request->role);

        return response()->json(['success' => true]);
    }

    // ════════════════════════════════════════════
    //  SEEDING (Admin only)
    // ════════════════════════════════════════════

    /**
     * Seed default roles and permissions.
     */
    public function seed(): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        $roles = $this->rbac->seedDefaultRoles($accountId);

        return response()->json([
            'success' => true,
            'roles_created' => count($roles),
        ]);
    }
}
