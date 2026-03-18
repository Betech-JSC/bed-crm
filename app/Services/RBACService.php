<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RBACService
{
    // ════════════════════════════════════════════
    //  PERMISSION DEFINITIONS (resource.action format)
    // ════════════════════════════════════════════

    /**
     * All granular permissions — resource.action format.
     * Each entry: [slug, name_vi, name_en, group]
     */
    public static function getPermissionDefinitions(): array
    {
        return [
            // Leads
            ['leads.view',   'Xem leads',         'View Leads',         'leads'],
            ['leads.create', 'Tạo lead',          'Create Lead',        'leads'],
            ['leads.edit',   'Sửa lead',          'Edit Lead',          'leads'],
            ['leads.delete', 'Xoá lead',          'Delete Lead',        'leads'],
            ['leads.export', 'Xuất leads',        'Export Leads',       'leads'],
            ['leads.import', 'Nhập leads',        'Import Leads',       'leads'],
            ['leads.assign', 'Phân công lead',    'Assign Lead',        'leads'],

            // Deals
            ['deals.view',   'Xem deals',         'View Deals',         'deals'],
            ['deals.create', 'Tạo deal',          'Create Deal',        'deals'],
            ['deals.edit',   'Sửa deal',          'Edit Deal',          'deals'],
            ['deals.delete', 'Xoá deal',          'Delete Deal',        'deals'],
            ['deals.close',  'Đóng deal',         'Close Deal',         'deals'],

            // Customers
            ['customers.view',       'Xem khách hàng',       'View Customers',       'customers'],
            ['customers.create',     'Tạo khách hàng',       'Create Customer',      'customers'],
            ['customers.edit',       'Sửa khách hàng',       'Edit Customer',        'customers'],
            ['customers.delete',     'Xoá khách hàng',       'Delete Customer',      'customers'],

            // Projects
            ['projects.view',        'Xem dự án',            'View Projects',        'projects'],
            ['projects.create',      'Tạo dự án',            'Create Project',       'projects'],
            ['projects.edit',        'Sửa dự án',            'Edit Project',         'projects'],
            ['projects.delete',      'Xoá dự án',            'Delete Project',       'projects'],
            ['projects.manage',      'Quản lý dự án',        'Manage Project',       'projects'],

            // Finance
            ['finance.view',         'Xem tài chính',        'View Finance',         'finance'],
            ['finance.create',       'Tạo giao dịch',        'Create Transaction',   'finance'],
            ['finance.edit',         'Sửa giao dịch',        'Edit Transaction',     'finance'],
            ['finance.delete',       'Xoá giao dịch',        'Delete Transaction',   'finance'],
            ['finance.reports',      'Xem báo cáo tài chính','View Financial Reports','finance'],

            // HR
            ['hr.view',              'Xem nhân sự',          'View HR',              'hr'],
            ['hr.manage',            'Quản lý nhân sự',      'Manage HR',            'hr'],
            ['hr.reviews',           'Đánh giá nhân viên',   'Employee Reviews',     'hr'],

            // Reports
            ['reports.view',         'Xem báo cáo',          'View Reports',         'reports'],
            ['reports.export',       'Xuất báo cáo',         'Export Reports',       'reports'],
            ['reports.intelligence', 'AI phân tích',         'AI Intelligence',      'reports'],

            // Marketing
            ['marketing.view',       'Xem marketing',        'View Marketing',       'marketing'],
            ['marketing.campaigns',  'Quản lý chiến dịch',   'Manage Campaigns',     'marketing'],
            ['marketing.email',      'Gửi email',            'Send Email',           'marketing'],

            // Notifications
            ['notifications.view',   'Xem thông báo',        'View Notifications',   'notifications'],
            ['notifications.manage', 'Quản lý thông báo',    'Manage Notifications', 'notifications'],

            // Strategy & OKR
            ['strategy.view',        'Xem chiến lược',       'View Strategy',        'strategy'],
            ['strategy.manage',      'Quản lý chiến lược',   'Manage Strategy',      'strategy'],
            ['okr.view',             'Xem OKR',              'View OKRs',            'strategy'],
            ['okr.create',           'Tạo OKR',              'Create OKR',           'strategy'],
            ['okr.edit',             'Sửa OKR',              'Edit OKR',             'strategy'],
            ['okr.cascade',          'Cascade OKR',          'Cascade OKR',          'strategy'],
            ['initiative.view',      'Xem sáng kiến',        'View Initiatives',     'strategy'],
            ['initiative.manage',    'Quản lý sáng kiến',    'Manage Initiatives',   'strategy'],

            // Settings / Admin
            ['settings.view',        'Xem cài đặt',          'View Settings',        'settings'],
            ['settings.manage',      'Quản lý cài đặt',      'Manage Settings',      'settings'],
            ['roles.view',           'Xem vai trò',          'View Roles',           'settings'],
            ['roles.manage',         'Quản lý vai trò',      'Manage Roles',         'settings'],
            ['users.view',           'Xem người dùng',       'View Users',           'settings'],
            ['users.manage',         'Quản lý người dùng',   'Manage Users',         'settings'],
        ];
    }

    /**
     * Default role templates with their permissions.
     */
    public static function getDefaultRoles(): array
    {
        return [
            'admin' => [
                'name_vi' => 'Quản trị viên',
                'name_en' => 'Administrator',
                'description_vi' => 'Toàn quyền truy cập hệ thống',
                'description_en' => 'Full system access',
                'permissions' => ['*'], // All permissions
            ],
            'sales_manager' => [
                'name_vi' => 'Quản lý kinh doanh',
                'name_en' => 'Sales Manager',
                'description_vi' => 'Quản lý lead, deal và khách hàng',
                'description_en' => 'Manage leads, deals, and customers',
                'permissions' => [
                    'leads.*', 'deals.*', 'customers.*', 'projects.view',
                    'reports.view', 'reports.export', 'finance.view',
                ],
            ],
            'sales' => [
                'name_vi' => 'Nhân viên kinh doanh',
                'name_en' => 'Sales Representative',
                'description_vi' => 'Xem và quản lý lead, deal được phân công',
                'description_en' => 'View and manage assigned leads and deals',
                'permissions' => [
                    'leads.view', 'leads.create', 'leads.edit',
                    'deals.view', 'deals.create', 'deals.edit',
                    'customers.view', 'reports.view',
                ],
            ],
            'marketing' => [
                'name_vi' => 'Nhân viên marketing',
                'name_en' => 'Marketing Specialist',
                'description_vi' => 'Quản lý chiến dịch marketing và nội dung',
                'description_en' => 'Manage marketing campaigns and content',
                'permissions' => [
                    'leads.view', 'marketing.*',
                    'reports.view', 'reports.export',
                ],
            ],
            'csm' => [
                'name_vi' => 'CSKH',
                'name_en' => 'Customer Success',
                'description_vi' => 'Quản lý khách hàng và hỗ trợ',
                'description_en' => 'Customer management and support',
                'permissions' => [
                    'customers.*', 'projects.view',
                    'leads.view', 'deals.view', 'reports.view',
                ],
            ],
            'viewer' => [
                'name_vi' => 'Chỉ xem',
                'name_en' => 'Viewer',
                'description_vi' => 'Chỉ quyền xem dữ liệu',
                'description_en' => 'Read-only access',
                'permissions' => [
                    'leads.view', 'deals.view', 'customers.view',
                    'projects.view', 'reports.view',
                    'strategy.view', 'okr.view', 'initiative.view',
                ],
            ],
        ];
    }

    /**
     * Permission group labels (đa ngữ).
     */
    public static function getGroupLabels(): array
    {
        return [
            'leads' => ['vi' => 'Leads', 'en' => 'Leads'],
            'deals' => ['vi' => 'Deals', 'en' => 'Deals'],
            'customers' => ['vi' => 'Khách hàng', 'en' => 'Customers'],
            'projects' => ['vi' => 'Dự án', 'en' => 'Projects'],
            'finance' => ['vi' => 'Tài chính', 'en' => 'Finance'],
            'hr' => ['vi' => 'Nhân sự', 'en' => 'HR'],
            'reports' => ['vi' => 'Báo cáo', 'en' => 'Reports'],
            'marketing' => ['vi' => 'Marketing', 'en' => 'Marketing'],
            'notifications' => ['vi' => 'Thông báo', 'en' => 'Notifications'],
            'strategy' => ['vi' => 'Chiến lược & OKR', 'en' => 'Strategy & OKR'],
            'settings' => ['vi' => 'Cài đặt', 'en' => 'Settings'],
        ];
    }

    // ════════════════════════════════════════════
    //  SEEDING — Initialize permissions & roles
    // ════════════════════════════════════════════

    /**
     * Seed all granular permissions for an account.
     */
    public function seedPermissions(int $accountId): int
    {
        $definitions = self::getPermissionDefinitions();
        $created = 0;

        foreach ($definitions as [$slug, $nameVi, $nameEn, $group]) {
            $permission = Permission::firstOrCreate(
                ['account_id' => $accountId, 'slug' => $slug],
                [
                    'name' => $nameVi, // Default to Vietnamese
                    'group' => $group,
                    'description' => $nameEn,
                    'is_active' => true,
                ]
            );

            if ($permission->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Seed default roles with permissions for an account.
     */
    public function seedDefaultRoles(int $accountId): array
    {
        $this->seedPermissions($accountId);

        $roles = self::getDefaultRoles();
        $created = [];

        foreach ($roles as $slug => $config) {
            $role = Role::firstOrCreate(
                ['account_id' => $accountId, 'slug' => $slug],
                [
                    'name' => $config['name_vi'],
                    'description' => $config['description_vi'],
                    'is_system' => in_array($slug, ['admin', 'viewer']),
                    'is_active' => true,
                ]
            );

            // Attach permissions
            $permSlugs = $config['permissions'];
            $permissionIds = $this->resolvePermissionIds($accountId, $permSlugs);
            $role->permissions()->syncWithoutDetaching($permissionIds);

            $created[] = $role;
        }

        return $created;
    }

    /**
     * Resolve permission slugs (supports wildcards like 'leads.*').
     */
    private function resolvePermissionIds(int $accountId, array $permSlugs): array
    {
        $ids = [];

        foreach ($permSlugs as $slug) {
            if ($slug === '*') {
                // All permissions
                return Permission::where('account_id', $accountId)
                    ->where('is_active', true)
                    ->pluck('id')
                    ->toArray();
            }

            if (str_ends_with($slug, '.*')) {
                // Wildcard: all permissions for a group
                $group = str_replace('.*', '', $slug);
                $groupIds = Permission::where('account_id', $accountId)
                    ->where('group', $group)
                    ->where('is_active', true)
                    ->pluck('id')
                    ->toArray();
                $ids = array_merge($ids, $groupIds);
            } else {
                // Exact slug
                $perm = Permission::where('account_id', $accountId)
                    ->where('slug', $slug)
                    ->where('is_active', true)
                    ->first();
                if ($perm) {
                    $ids[] = $perm->id;
                }
            }
        }

        return array_unique($ids);
    }

    // ════════════════════════════════════════════
    //  ROLE MANAGEMENT API
    // ════════════════════════════════════════════

    /**
     * Create a new role.
     */
    public function createRole(int $accountId, array $data): Role
    {
        return Role::create([
            'account_id' => $accountId,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'is_system' => false,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Update a role.
     */
    public function updateRole(Role $role, array $data): Role
    {
        if ($role->is_system) {
            // System roles can only change description
            $role->update(['description' => $data['description'] ?? $role->description]);
        } else {
            $role->update([
                'name' => $data['name'] ?? $role->name,
                'slug' => isset($data['name']) ? Str::slug($data['name']) : $role->slug,
                'description' => $data['description'] ?? $role->description,
                'is_active' => $data['is_active'] ?? $role->is_active,
            ]);
        }

        return $role;
    }

    /**
     * Delete a role (soft delete). System roles cannot be deleted.
     */
    public function deleteRole(Role $role): bool
    {
        if ($role->is_system) {
            return false;
        }

        // Detach all users first
        $role->users()->detach();
        $role->permissions()->detach();
        $role->delete();

        return true;
    }

    // ════════════════════════════════════════════
    //  PERMISSION ATTACHMENT
    // ════════════════════════════════════════════

    /**
     * Attach permissions to a role (by permission IDs or slugs).
     */
    public function attachPermissions(Role $role, array $permissionSlugs): void
    {
        $ids = $this->resolvePermissionIds($role->account_id, $permissionSlugs);
        $role->permissions()->syncWithoutDetaching($ids);

        Log::info("[RBAC] Attached " . count($ids) . " permissions to role '{$role->slug}'");
    }

    /**
     * Set exact permissions for a role (replaces existing).
     */
    public function syncPermissions(Role $role, array $permissionSlugs): void
    {
        $ids = $this->resolvePermissionIds($role->account_id, $permissionSlugs);
        $role->permissions()->sync($ids);

        Log::info("[RBAC] Synced " . count($ids) . " permissions to role '{$role->slug}'");
    }

    /**
     * Detach permissions from a role.
     */
    public function detachPermissions(Role $role, array $permissionSlugs): void
    {
        $ids = $this->resolvePermissionIds($role->account_id, $permissionSlugs);
        $role->permissions()->detach($ids);
    }

    // ════════════════════════════════════════════
    //  USER-ROLE ASSIGNMENT
    // ════════════════════════════════════════════

    /**
     * Assign a role to a user.
     */
    public function assignRole(User $user, string $roleSlug): void
    {
        $role = Role::where('account_id', $user->account_id)
            ->where('slug', $roleSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $user->roles()->syncWithoutDetaching([$role->id]);
        $role->updateUsersCount();

        Log::info("[RBAC] Assigned role '{$roleSlug}' to user #{$user->id}");
    }

    /**
     * Remove a role from a user.
     */
    public function removeRole(User $user, string $roleSlug): void
    {
        $role = Role::where('account_id', $user->account_id)
            ->where('slug', $roleSlug)
            ->first();

        if ($role) {
            $user->roles()->detach($role->id);
            $role->updateUsersCount();
        }
    }

    /**
     * Set exact roles for a user (replaces existing).
     */
    public function syncRoles(User $user, array $roleSlugs): void
    {
        $roleIds = Role::where('account_id', $user->account_id)
            ->whereIn('slug', $roleSlugs)
            ->where('is_active', true)
            ->pluck('id');

        $user->roles()->sync($roleIds);

        // Update counts for all affected roles
        Role::where('account_id', $user->account_id)->each(fn ($r) => $r->updateUsersCount());
    }

    // ════════════════════════════════════════════
    //  QUERY HELPERS
    // ════════════════════════════════════════════

    /**
     * Get all permissions grouped by resource (for UI).
     */
    public function getPermissionsByGroup(int $accountId, string $locale = 'vi'): array
    {
        $permissions = Permission::where('account_id', $accountId)
            ->where('is_active', true)
            ->orderBy('group')
            ->orderBy('slug')
            ->get();

        $definitions = collect(self::getPermissionDefinitions())->keyBy(fn ($d) => $d[0]);
        $groupLabels = self::getGroupLabels();

        $grouped = [];
        foreach ($permissions as $perm) {
            $group = $perm->group ?? 'other';
            if (!isset($grouped[$group])) {
                $grouped[$group] = [
                    'group' => $group,
                    'label' => $groupLabels[$group][$locale] ?? $group,
                    'permissions' => [],
                ];
            }

            $def = $definitions->get($perm->slug);
            $grouped[$group]['permissions'][] = [
                'id' => $perm->id,
                'slug' => $perm->slug,
                'name' => $locale === 'vi' ? ($def[1] ?? $perm->name) : ($def[2] ?? $perm->description ?? $perm->name),
                'group' => $group,
            ];
        }

        return array_values($grouped);
    }
}
