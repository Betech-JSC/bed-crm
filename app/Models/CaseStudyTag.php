<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class CaseStudyTag extends Model
{
    protected $fillable = [
        'account_id', 'name', 'slug', 'type', 'color',
    ];

    public const TYPES = [
        'industry' => 'Industry',
        'client_type' => 'Client Type',
        'technology' => 'Technology',
        'custom' => 'Custom',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function caseStudies(): BelongsToMany
    {
        return $this->belongsToMany(CaseStudy::class, 'case_study_tag_pivot');
    }
}
