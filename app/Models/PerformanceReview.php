<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReview extends Model
{
    public const RATING_EXCEPTIONAL = 'exceptional';
    public const RATING_EXCEEDS = 'exceeds';
    public const RATING_MEETS = 'meets';
    public const RATING_BELOW = 'below';
    public const RATING_UNSATISFACTORY = 'unsatisfactory';

    protected $fillable = [
        'employee_profile_id', 'reviewed_by', 'period_label', 'period_start', 'period_end',
        'overall_score', 'score_breakdown', 'rating',
        'revenue_generated', 'deals_closed_value', 'deals_closed_count', 'hours_logged',
        'strengths', 'improvements', 'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'score_breakdown' => 'array',
        'revenue_generated' => 'decimal:2',
        'deals_closed_value' => 'decimal:2',
    ];

    public static function getRatings(): array
    {
        return [
            self::RATING_EXCEPTIONAL => 'Exceptional',
            self::RATING_EXCEEDS => 'Exceeds Expectations',
            self::RATING_MEETS => 'Meets Expectations',
            self::RATING_BELOW => 'Below Expectations',
            self::RATING_UNSATISFACTORY => 'Unsatisfactory',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
