<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Role constants
     */
    public const ROLE_ADMIN = 'admin';
    public const ROLE_SALE = 'sale';
    public const ROLE_MARKETING = 'marketing';
    public const ROLE_CSKH = 'cskh';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'owner' => 'boolean',
            'email_verified_at' => 'datetime',
        ];
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    /**
     * Check if user has role
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()
            ->where('slug', $roleSlug)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if user has permission (through roles)
     */
    public function hasPermission(string $permissionSlug): bool
    {
        // Owner has all permissions
        if ($this->owner) {
            return true;
        }

        return $this->roles()
            ->where('is_active', true)
            ->whereHas('permissions', function ($query) use ($permissionSlug) {
                $query->where('slug', $permissionSlug)
                    ->where('is_active', true);
            })
            ->exists();
    }

    /**
     * Get all permissions for user (through roles)
     */
    public function getPermissions(): \Illuminate\Support\Collection
    {
        if ($this->owner) {
            // Owner has all permissions
            return Permission::where('account_id', $this->account_id)
                ->where('is_active', true)
                ->get();
        }

        return $this->roles()
            ->where('is_active', true)
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id');
    }

    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = Hash::needsRehash($password) ? Hash::make($password) : $password;
    }

    public function isDemoUser()
    {
        return $this->email === 'johndoe@example.com';
    }

    public function scopeOrderByName($query)
    {
        $query->orderBy('last_name')->orderBy('first_name');
    }

    public function scopeWhereRole($query, $role)
    {
        switch ($role) {
            case 'user':
                return $query->where('owner', false);
            case 'owner':
                return $query->where('owner', true);
        }
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /**
     * Get all available roles
     */
    public static function getRoles(): array
    {
        return [
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_SALE => 'Sale',
            self::ROLE_MARKETING => 'Marketing',
            self::ROLE_CSKH => 'CSKH',
        ];
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        })->when($filters['role'] ?? null, function ($query, $role) {
            $query->whereRole($role);
        })->when($filters['trashed'] ?? null, function ($query, $trashed) {
            if ($trashed === 'with') {
                $query->withTrashed();
            } elseif ($trashed === 'only') {
                $query->onlyTrashed();
            }
        });
    }
}
