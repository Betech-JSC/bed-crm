<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    public const STATUS_PLANNING = 'planning';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_ON_HOLD = 'on_hold';
    public const STATUS_DELAYED = 'delayed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'account_id', 'deal_id', 'customer_id', 'manager_id',
        'name', 'description', 'status', 'priority',
        'start_date', 'due_date', 'completed_at', 'progress',
        'budget', 'revenue', 'total_cost', 'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'date',
        'budget' => 'decimal:2',
        'revenue' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PLANNING => 'Planning',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_DELAYED => 'Delayed',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function getPriorities(): array
    {
        return ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }

    // Relationships
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function deal(): BelongsTo { return $this->belongsTo(Deal::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function manager(): BelongsTo { return $this->belongsTo(User::class, 'manager_id'); }
    public function tasks(): HasMany { return $this->hasMany(ProjectTask::class); }
    public function resources(): HasMany { return $this->hasMany(ProjectResource::class); }
    public function expenses(): HasMany { return $this->hasMany(ProjectExpense::class); }

    // Computed
    public function getProfit(): float
    {
        return (float) $this->revenue - $this->calculateTotalCost();
    }

    public function getProfitMargin(): float
    {
        $rev = (float) $this->revenue;
        return $rev > 0 ? round(($this->getProfit() / $rev) * 100, 1) : 0;
    }

    public function calculateTotalCost(): float
    {
        $laborCost = $this->resources->sum(fn ($r) => (float) $r->logged_hours * (float) $r->hourly_rate);
        $taskCost = $this->tasks->sum(fn ($t) => (float) $t->actual_hours * (float) $t->hourly_cost);
        $expensesCost = $this->expenses->sum('amount');
        return $laborCost + $taskCost + $expensesCost;
    }

    public function recalculateProgress(): int
    {
        $tasks = $this->tasks;
        if ($tasks->isEmpty()) return $this->progress;

        $total = $tasks->count();
        $done = $tasks->where('status', ProjectTask::STATUS_DONE)->count();
        return $total > 0 ? (int) round(($done / $total) * 100) : 0;
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast()
            && !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function getDaysRemaining(): ?int
    {
        if (!$this->due_date) return null;
        return (int) now()->diffInDays($this->due_date, false);
    }

    // Scopes
    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($q, $s) {
            $q->where('name', 'like', "%{$s}%");
        })
        ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
        ->when($filters['manager_id'] ?? null, fn ($q, $m) => $q->where('manager_id', $m))
        ->when($filters['trashed'] ?? null, function ($q, $t) {
            if ($t === 'with') $q->withTrashed();
            elseif ($t === 'only') $q->onlyTrashed();
        });
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PLANNING, self::STATUS_IN_PROGRESS, self::STATUS_ON_HOLD, self::STATUS_DELAYED]);
    }
}
