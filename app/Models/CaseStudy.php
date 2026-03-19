<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class CaseStudy extends Model
{
    use SoftDeletes;

    public const SERVICE_CATEGORIES = [
        'website' => 'Website',
        'marketing' => 'Marketing',
        'seo' => 'SEO',
        'branding' => 'Branding',
        'landing_page' => 'Landing Page',
        'ai_agent' => 'AI Agent',
    ];

    public const VISIBILITIES = [
        'public' => 'Public',
        'private' => 'Private',
        'unlisted' => 'Unlisted',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ];

    public const CLIENT_SIZES = [
        'startup' => 'Startup',
        'smb' => 'SMB',
        'mid_market' => 'Mid-Market',
        'enterprise' => 'Enterprise',
    ];

    protected $fillable = [
        'account_id', 'title', 'slug', 'summary', 'service_category',
        'client_name', 'client_industry', 'client_company_size',
        'client_logo_url', 'client_website',
        'problem', 'solution', 'approach', 'result', 'result_metrics',
        'testimonial_quote', 'testimonial_author', 'testimonial_role', 'testimonial_avatar_url',
        'featured_image_url', 'project_url',
        'project_start_date', 'project_end_date', 'project_duration_days',
        'visibility', 'status', 'is_featured', 'sort_order', 'view_count',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'result_metrics' => 'array',
        'project_start_date' => 'date',
        'project_end_date' => 'date',
        'is_featured' => 'boolean',
    ];

    // ─── Boot ────────────────────────────────────
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title) . '-' . Str::random(6);
            }
        });
    }

    // ─── Relationships ───────────────────────────
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function media(): HasMany
    {
        return $this->hasMany(CaseStudyMedia::class)->orderBy('sort_order');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CaseStudyTag::class, 'case_study_tag_pivot');
    }

    public function links(): HasMany
    {
        return $this->hasMany(CaseStudyLink::class);
    }

    // ─── Scopes ──────────────────────────────────
    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where('visibility', 'public');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('service_category', $category);
    }

    public function scopeByTag($query, int $tagId)
    {
        return $query->whereHas('tags', fn ($q) => $q->where('case_study_tags.id', $tagId));
    }

    // ─── Helpers ─────────────────────────────────
    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    public function getGalleryImages()
    {
        return $this->media()->where('section', 'gallery')->where('type', 'image')->get();
    }

    public function getResultImages()
    {
        return $this->media()->where('section', 'result')->get();
    }

    /**
     * Link to a CRM entity (lead, deal, campaign)
     */
    public function linkTo(string $type, int $id, ?string $context = null): CaseStudyLink
    {
        return $this->links()->firstOrCreate(
            ['linkable_type' => $type, 'linkable_id' => $id],
            ['context' => $context]
        );
    }

    /**
     * Get linked leads
     */
    public function linkedLeads()
    {
        return $this->links()->where('linkable_type', 'lead')->get()->map(function ($link) {
            return Lead::find($link->linkable_id);
        })->filter();
    }

    /**
     * Get linked deals
     */
    public function linkedDeals()
    {
        return $this->links()->where('linkable_type', 'deal')->get()->map(function ($link) {
            return Deal::find($link->linkable_id);
        })->filter();
    }
}
