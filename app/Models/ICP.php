<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ICP extends Model
{
    use SoftDeletes;

    protected $table = 'icps';

    protected $fillable = [
        'account_id', 'name', 'description',
        'company_size_min', 'company_size_max',
        'industries', 'locations',
        'job_titles', 'departments',
        'technologies', 'keywords',
        'weight_company_size', 'weight_industry', 'weight_location',
        'weight_job_title', 'weight_behavioral',
        'min_score', 'is_active',
    ];

    protected $casts = [
        'company_size_min' => 'array',
        'company_size_max' => 'array',
        'industries' => 'array',
        'locations' => 'array',
        'job_titles' => 'array',
        'departments' => 'array',
        'technologies' => 'array',
        'keywords' => 'array',
        'is_active' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
