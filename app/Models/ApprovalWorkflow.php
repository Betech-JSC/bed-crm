<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalWorkflow extends Model
{
    protected $fillable = [
        'account_id', 'name', 'description', 'entity_type',
        'conditions', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalWorkflowStep::class)->orderBy('step_order');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class);
    }

    /**
     * Check if this workflow should be triggered for an entity
     */
    public function shouldTrigger(string $entityType, array $entityData): bool
    {
        if ($this->entity_type !== $entityType) return false;
        if (!$this->is_active) return false;
        if (empty($this->conditions)) return true;

        foreach ($this->conditions as $field => $condition) {
            $value = $entityData[$field] ?? null;
            if (str_starts_with($field, 'amount_gt') && ($value <= $condition)) return false;
            if (str_starts_with($field, 'amount_lt') && ($value >= $condition)) return false;
        }

        return true;
    }
}
