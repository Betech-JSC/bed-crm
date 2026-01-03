<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SLASetting extends Model
{
    use HasFactory;

    protected $table = 'sla_settings';

    protected $fillable = [
        'account_id',
        'name',
        'description',
        'first_response_threshold',
        'warning_threshold',
        'critical_threshold',
        'business_hours',
        'include_weekends',
        'notify_assigned_user',
        'notify_managers',
        'notify_user_ids',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'business_hours' => 'array',
        'notify_user_ids' => 'array',
        'include_weekends' => 'boolean',
        'notify_assigned_user' => 'boolean',
        'notify_managers' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get the account that owns the SLA setting.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get all leads using this SLA setting.
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'sla_setting_id');
    }

    /**
     * Get active leads with pending SLA.
     */
    public function pendingLeads(): HasMany
    {
        return $this->leads()
            ->where('sla_status', 'pending')
            ->whereNotNull('sla_started_at')
            ->whereNull('first_response_at');
    }

    /**
     * Get breached leads.
     */
    public function breachedLeads(): HasMany
    {
        return $this->leads()
            ->where('sla_status', 'breached')
            ->whereNull('first_response_at');
    }

    /**
     * Scope to get default SLA for an account.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to get active SLAs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Set as default (and unset others for the same account).
     */
    public function setAsDefault(): void
    {
        // Unset other defaults for this account
        self::where('account_id', $this->account_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }
}
