<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Objective extends Model
{
    use SoftDeletes;

    // ── Levels ──
    public const LEVEL_COMPANY = 'company';
    public const LEVEL_TEAM = 'team';
    public const LEVEL_INDIVIDUAL = 'individual';

    // ── Statuses ──
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_AT_RISK = 'at_risk';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'account_id', 'strategic_theme_id', 'parent_id', 'level',
        'title', 'description', 'owner_id', 'team',
        'period', 'period_start', 'period_end',
        'status', 'progress', 'confidence', 'sort_order',
    ];

    protected $casts = [
        'progress' => 'decimal:2',
        'confidence' => 'integer',
        'sort_order' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    // ── Relationships ──
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function theme(): BelongsTo { return $this->belongsTo(StrategicTheme::class, 'strategic_theme_id'); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order'); }
    public function keyResults(): HasMany { return $this->hasMany(KeyResult::class)->orderBy('weight', 'desc'); }
    public function initiatives(): HasMany { return $this->hasMany(Initiative::class); }

    // ── Scopes ──
    public function scopeCompany($query) { return $query->where('level', self::LEVEL_COMPANY); }
    public function scopeTeam($query) { return $query->where('level', self::LEVEL_TEAM); }
    public function scopeIndividual($query) { return $query->where('level', self::LEVEL_INDIVIDUAL); }
    public function scopeActive($query) { return $query->where('status', self::STATUS_ACTIVE); }
    public function scopeForPeriod($query, string $period) { return $query->where('period', $period); }
    public function scopeRoots($query) { return $query->whereNull('parent_id'); }

    /**
     * Scope for user access (scope-based access control).
     */
    public function scopeForUser($query, User $user)
    {
        if ($user->owner || $user->hasRole('admin')) {
            return $query; // Full access
        }

        return $query->where(function ($q) use ($user) {
            $q->where('level', self::LEVEL_COMPANY) // Everyone sees company
              ->orWhere('owner_id', $user->id)       // Own objectives
              ->orWhere('team', $user->team ?? '__none__'); // Team objectives
        });
    }

    // ── OKR Calculations ──

    /**
     * Recalculate progress from key results.
     */
    public function recalculateProgress(): void
    {
        $keyResults = $this->keyResults;

        if ($keyResults->isEmpty()) {
            // If no KRs, derive from children objectives
            $children = $this->children()->where('status', '!=', self::STATUS_CANCELLED)->get();
            if ($children->isNotEmpty()) {
                $this->progress = round($children->avg('progress'), 2);
                $this->confidence = (int) round($children->avg('confidence'));
            }
        } else {
            $totalWeight = $keyResults->sum('weight');
            if ($totalWeight > 0) {
                $weightedProgress = $keyResults->sum(fn ($kr) => $kr->getProgress() * $kr->weight);
                $this->progress = round($weightedProgress / $totalWeight, 2);
            }
            $this->confidence = (int) round($keyResults->avg('confidence'));
        }

        // Auto-detect status
        if ($this->progress >= 100) {
            $this->status = self::STATUS_COMPLETED;
        } elseif ($this->progress < 30 && $this->confidence < 50) {
            $this->status = self::STATUS_AT_RISK;
        }

        $this->save();

        // Cascade up
        if ($this->parent_id) {
            $this->parent->recalculateProgress();
        }
    }

    /**
     * Get full cascade tree.
     */
    public function getTree(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'level' => $this->level,
            'status' => $this->status,
            'progress' => (float) $this->progress,
            'confidence' => $this->confidence,
            'owner' => $this->owner ? $this->owner->name : null,
            'team' => $this->team,
            'period' => $this->period,
            'key_results' => $this->keyResults->map(fn ($kr) => [
                'id' => $kr->id,
                'title' => $kr->title,
                'current_value' => (float) $kr->current_value,
                'target_value' => (float) $kr->target_value,
                'progress' => $kr->getProgress(),
                'status' => $kr->status,
                'data_source' => $kr->data_source,
            ])->toArray(),
            'initiatives_count' => $this->initiatives()->count(),
            'children' => $this->children->map(fn ($child) => $child->getTree())->toArray(),
        ];
    }
}
