<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesPlaybook extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sales_playbooks';

    protected $fillable = [
        'account_id',
        'name',
        'description',
        'industries',
        'deal_stages',
        'pain_points',
        'talking_points',
        'email_template_subject',
        'email_template_body',
        'recommended_documents',
        'objections_handling',
        'next_steps',
        'tags',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'industries' => 'array',
        'deal_stages' => 'array',
        'pain_points' => 'array',
        'recommended_documents' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the account that owns the playbook.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Scope to get active playbooks.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by priority.
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('name');
    }

    /**
     * Check if playbook matches given criteria.
     */
    public function matches(?string $industry = null, ?string $dealStage = null, ?array $painPoints = null): bool
    {
        // Check industry match
        if ($industry && $this->industries && !empty($this->industries)) {
            $industryLower = strtolower($industry);
            $matchesIndustry = false;
            foreach ($this->industries as $playbookIndustry) {
                if (
                    stripos($industryLower, strtolower($playbookIndustry)) !== false ||
                    stripos(strtolower($playbookIndustry), $industryLower) !== false
                ) {
                    $matchesIndustry = true;
                    break;
                }
            }
            if (!$matchesIndustry) {
                return false;
            }
        }

        // Check deal stage match
        if ($dealStage && $this->deal_stages && !empty($this->deal_stages)) {
            if (!in_array($dealStage, $this->deal_stages)) {
                return false;
            }
        }

        // Check pain points match
        if ($painPoints && !empty($painPoints) && $this->pain_points && !empty($this->pain_points)) {
            $matchesPainPoint = false;
            foreach ($painPoints as $painPoint) {
                $painPointLower = strtolower($painPoint);
                foreach ($this->pain_points as $playbookPainPoint) {
                    if (
                        stripos($painPointLower, strtolower($playbookPainPoint)) !== false ||
                        stripos(strtolower($playbookPainPoint), $painPointLower) !== false
                    ) {
                        $matchesPainPoint = true;
                        break 2;
                    }
                }
            }
            if (!$matchesPainPoint) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate match score for ranking.
     */
    public function calculateMatchScore(?string $industry = null, ?string $dealStage = null, ?array $painPoints = null): int
    {
        $score = 0;

        // Industry match (40 points)
        if ($industry && $this->industries && !empty($this->industries)) {
            $industryLower = strtolower($industry);
            foreach ($this->industries as $playbookIndustry) {
                if (
                    stripos($industryLower, strtolower($playbookIndustry)) !== false ||
                    stripos(strtolower($playbookIndustry), $industryLower) !== false
                ) {
                    $score += 40;
                    break;
                }
            }
        } elseif (!$industry || empty($this->industries)) {
            $score += 20; // Partial match if no industry specified or playbook doesn't require it
        }

        // Deal stage match (30 points)
        if ($dealStage && $this->deal_stages && !empty($this->deal_stages)) {
            if (in_array($dealStage, $this->deal_stages)) {
                $score += 30;
            }
        } elseif (!$dealStage || empty($this->deal_stages)) {
            $score += 15; // Partial match
        }

        // Pain points match (30 points)
        if ($painPoints && !empty($painPoints) && $this->pain_points && !empty($this->pain_points)) {
            $matchedCount = 0;
            foreach ($painPoints as $painPoint) {
                $painPointLower = strtolower($painPoint);
                foreach ($this->pain_points as $playbookPainPoint) {
                    if (
                        stripos($painPointLower, strtolower($playbookPainPoint)) !== false ||
                        stripos(strtolower($playbookPainPoint), $painPointLower) !== false
                    ) {
                        $matchedCount++;
                        break;
                    }
                }
            }
            if ($matchedCount > 0) {
                $score += min(30, ($matchedCount / count($this->pain_points)) * 30);
            }
        } elseif (!$painPoints || empty($painPoints) || empty($this->pain_points)) {
            $score += 15; // Partial match
        }

        // Add priority boost
        $score += $this->priority;

        return $score;
    }

    /**
     * Replace placeholders in email template.
     */
    public function getPersonalizedEmail(?string $customerName = null, ?string $companyName = null, ?string $dealTitle = null): array
    {
        $subject = $this->email_template_subject ?? '';
        $body = $this->email_template_body ?? '';

        $replacements = [
            '{{customer_name}}' => $customerName ?? 'Valued Customer',
            '{{company_name}}' => $companyName ?? 'your company',
            '{{deal_title}}' => $dealTitle ?? 'our proposal',
            '{{date}}' => now()->format('F j, Y'),
        ];

        foreach ($replacements as $placeholder => $value) {
            $subject = str_replace($placeholder, $value, $subject);
            $body = str_replace($placeholder, $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }
}
