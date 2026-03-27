<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SeoKeyword extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'keyword', 'url', 'current_rank', 'previous_rank', 'best_rank',
        'search_engine', 'search_volume', 'difficulty', 'status', 'last_checked_at',
    ];

    protected $casts = ['last_checked_at' => 'date'];

    public function getRankChangeAttribute(): int
    {
        if (!$this->previous_rank || !$this->current_rank) return 0;
        return $this->previous_rank - $this->current_rank; // positive = improved
    }

    public function getRankTrendAttribute(): string
    {
        $change = $this->rank_change;
        return $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable');
    }

    public function scopeFilter($q, array $filters)
    {
        if (!empty($filters['search'])) {
            $q->where(fn ($q2) => $q2->where('keyword', 'like', "%{$filters['search']}%")
                ->orWhere('url', 'like', "%{$filters['search']}%"));
        }
        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        if (!empty($filters['difficulty'])) $q->where('difficulty', $filters['difficulty']);
        return $q;
    }

    public function rankHistory(): HasMany { return $this->hasMany(SeoRankHistory::class); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
}
