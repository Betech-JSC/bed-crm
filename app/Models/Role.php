<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id',
        'name',
        'slug',
        'description',
        'is_active',
        'is_system',
        'users_count',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'users_count' => 'integer',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role');
    }

    /**
     * Update users count
     */
    public function updateUsersCount(): void
    {
        $this->users_count = $this->users()->count();
        $this->save();
    }

    /**
     * Check if role has permission
     */
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->permissions()
            ->where('slug', $permissionSlug)
            ->where('is_active', true)
            ->exists();
    }
}
