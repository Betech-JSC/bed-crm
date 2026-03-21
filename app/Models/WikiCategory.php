<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WikiCategory extends Model
{
    protected $fillable = [
        'account_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'icon',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $cat) {
            if (empty($cat->slug)) {
                $cat->slug = Str::slug($cat->name);
            }
        });
    }

    // ── Relationships ──

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(WikiCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(WikiCategory::class, 'parent_id')->orderBy('sort_order');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(WikiArticle::class, 'category_id');
    }

    /**
     * Get all descendants (recursive children)
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Scope: top-level categories only
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get article count including subcategories
     */
    public function getTotalArticlesCountAttribute(): int
    {
        $count = $this->articles()->published()->count();
        foreach ($this->children as $child) {
            $count += $child->total_articles_count;
        }
        return $count;
    }
}
