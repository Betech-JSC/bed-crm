<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ICP extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'icps';

    protected $fillable = [
        'account_id',
        'name',
        'description',
        'company_size_min',
        'company_size_max',
        'industries',
        'locations',
        'job_titles',
        'departments',
        'technologies',
        'keywords',
        'weight_company_size',
        'weight_industry',
        'weight_location',
        'weight_job_title',
        'weight_behavioral',
        'min_score',
        'is_active',
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

    /**
     * ICP belongs to Account
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * ICP has many Leads
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'icp_id');
    }

    /**
     * Score a lead against this ICP
     */
    public function scoreLead(Lead $lead, array $enrichmentData = []): array
    {
        $scores = [];
        $totalScore = 0;

        // Company Size Score
        if ($this->company_size_min || $this->company_size_max) {
            $companySizeScore = $this->scoreCompanySize($enrichmentData);
            $scores['company_size'] = $companySizeScore;
            $totalScore += ($companySizeScore * $this->weight_company_size / 100);
        }

        // Industry Score
        if ($this->industries && !empty($this->industries)) {
            $industryScore = $this->scoreIndustry($enrichmentData);
            $scores['industry'] = $industryScore;
            $totalScore += ($industryScore * $this->weight_industry / 100);
        }

        // Location Score
        if ($this->locations && !empty($this->locations)) {
            $locationScore = $this->scoreLocation($enrichmentData);
            $scores['location'] = $locationScore;
            $totalScore += ($locationScore * $this->weight_location / 100);
        }

        // Job Title Score
        if ($this->job_titles && !empty($this->job_titles)) {
            $jobTitleScore = $this->scoreJobTitle($lead, $enrichmentData);
            $scores['job_title'] = $jobTitleScore;
            $totalScore += ($jobTitleScore * $this->weight_job_title / 100);
        }

        // Behavioral Score (technologies, keywords)
        if (($this->technologies && !empty($this->technologies)) ||
            ($this->keywords && !empty($this->keywords))
        ) {
            $behavioralScore = $this->scoreBehavioral($enrichmentData);
            $scores['behavioral'] = $behavioralScore;
            $totalScore += ($behavioralScore * $this->weight_behavioral / 100);
        }

        return [
            'total_score' => (int) round($totalScore),
            'scores' => $scores,
            'is_match' => $totalScore >= $this->min_score,
            'weights' => [
                'company_size' => $this->weight_company_size,
                'industry' => $this->weight_industry,
                'location' => $this->weight_location,
                'job_title' => $this->weight_job_title,
                'behavioral' => $this->weight_behavioral,
            ],
        ];
    }

    /**
     * Score company size
     */
    private function scoreCompanySize(array $enrichmentData): int
    {
        $employees = $enrichmentData['employees'] ?? null;
        if (!$employees) {
            return 0;
        }

        $min = $this->company_size_min['employees'] ?? 0;
        $max = $this->company_size_max['employees'] ?? PHP_INT_MAX;

        if ($employees >= $min && $employees <= $max) {
            return 100; // Perfect match
        }

        // Partial match based on proximity
        if ($employees < $min) {
            $diff = $min - $employees;
            $range = $min;
            return max(0, 100 - (($diff / $range) * 100));
        }

        if ($employees > $max) {
            $diff = $employees - $max;
            $range = $max;
            return max(0, 100 - (($diff / $range) * 100));
        }

        return 0;
    }

    /**
     * Score industry
     */
    private function scoreIndustry(array $enrichmentData): int
    {
        $leadIndustry = $enrichmentData['industry'] ?? null;
        if (!$leadIndustry) {
            return 0;
        }

        $leadIndustryLower = strtolower($leadIndustry);
        foreach ($this->industries as $targetIndustry) {
            if (
                str_contains($leadIndustryLower, strtolower($targetIndustry)) ||
                str_contains(strtolower($targetIndustry), $leadIndustryLower)
            ) {
                return 100;
            }
        }

        return 0;
    }

    /**
     * Score location
     */
    private function scoreLocation(array $enrichmentData): int
    {
        $leadLocation = $enrichmentData['location'] ?? $enrichmentData['country'] ?? null;
        if (!$leadLocation) {
            return 0;
        }

        $leadLocationLower = strtolower($leadLocation);
        foreach ($this->locations as $targetLocation) {
            if (
                str_contains($leadLocationLower, strtolower($targetLocation)) ||
                str_contains(strtolower($targetLocation), $leadLocationLower)
            ) {
                return 100;
            }
        }

        return 0;
    }

    /**
     * Score job title
     */
    private function scoreJobTitle(Lead $lead, array $enrichmentData): int
    {
        $jobTitle = $enrichmentData['job_title'] ?? null;
        if (!$jobTitle) {
            return 0;
        }

        $jobTitleLower = strtolower($jobTitle);
        foreach ($this->job_titles as $targetTitle) {
            if (
                str_contains($jobTitleLower, strtolower($targetTitle)) ||
                str_contains(strtolower($targetTitle), $jobTitleLower)
            ) {
                return 100;
            }
        }

        return 0;
    }

    /**
     * Score behavioral (technologies, keywords)
     */
    private function scoreBehavioral(array $enrichmentData): int
    {
        $score = 0;
        $factors = 0;

        // Technologies match
        if ($this->technologies && !empty($this->technologies)) {
            $leadTech = $enrichmentData['technologies'] ?? [];
            if (is_string($leadTech)) {
                $leadTech = explode(',', $leadTech);
            }

            $matches = 0;
            foreach ($this->technologies as $targetTech) {
                foreach ($leadTech as $tech) {
                    if (str_contains(strtolower($tech), strtolower($targetTech))) {
                        $matches++;
                        break;
                    }
                }
            }

            if (!empty($this->technologies)) {
                $score += ($matches / count($this->technologies)) * 50;
                $factors++;
            }
        }

        // Keywords match
        if ($this->keywords && !empty($this->keywords)) {
            $leadKeywords = $enrichmentData['keywords'] ?? $enrichmentData['description'] ?? '';
            $matches = 0;
            foreach ($this->keywords as $keyword) {
                if (stripos($leadKeywords, $keyword) !== false) {
                    $matches++;
                }
            }

            if (!empty($this->keywords)) {
                $score += ($matches / count($this->keywords)) * 50;
                $factors++;
            }
        }

        return $factors > 0 ? (int) ($score / $factors) : 0;
    }
}
