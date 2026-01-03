<?php

namespace App\Services;

use App\Models\Lead;

class LeadScoringService
{
    /**
     * Scoring weights (total = 100)
     */
    private const WEIGHT_SOURCE = 15;        // Lead source quality
    private const WEIGHT_INDUSTRY = 20;      // Industry match
    private const WEIGHT_COMPANY_SIZE = 20;  // Company size fit
    private const WEIGHT_ENGAGEMENT = 25;    // Email engagement
    private const WEIGHT_WEBSITE = 20;       // Website behavior

    /**
     * Source scoring values
     */
    private const SOURCE_SCORES = [
        Lead::SOURCE_REFERRAL => 100,    // Highest quality
        Lead::SOURCE_WEBSITE => 80,       // High intent
        Lead::SOURCE_EMAIL => 60,         // Medium intent
        Lead::SOURCE_PHONE => 50,         // Medium intent
        Lead::SOURCE_SOCIAL => 40,        // Lower intent
        Lead::SOURCE_OTHER => 30,         // Unknown quality
    ];

    /**
     * Industry scoring (can be customized per account)
     */
    private const INDUSTRY_SCORES = [
        'Technology' => 100,
        'SaaS' => 100,
        'E-commerce' => 90,
        'Finance' => 85,
        'Healthcare' => 80,
        'Education' => 75,
        'Manufacturing' => 70,
        'Retail' => 65,
        'Other' => 50,
    ];

    /**
     * Company size scoring
     */
    private const COMPANY_SIZE_SCORES = [
        'enterprise' => 100,  // 1000+ employees
        'large' => 90,        // 500-999 employees
        'medium' => 80,       // 100-499 employees
        'small' => 60,        // 10-99 employees
        'startup' => 40,      // 1-9 employees
    ];

    /**
     * Calculate comprehensive lead score
     *
     * @param Lead $lead
     * @return array
     */
    public function calculateScore(Lead $lead): array
    {
        $scores = [];
        $totalScore = 0;
        $details = [];

        // 1. Source Score (15 points)
        $sourceScore = $this->scoreSource($lead);
        $scores['source'] = $sourceScore;
        $details['source'] = [
            'score' => $sourceScore,
            'max_score' => 100,
            'weight' => self::WEIGHT_SOURCE,
            'weighted_score' => round($sourceScore * self::WEIGHT_SOURCE / 100, 2),
            'value' => $lead->source,
            'explanation' => $this->getSourceExplanation($lead->source),
        ];
        $totalScore += $sourceScore * self::WEIGHT_SOURCE / 100;

        // 2. Industry Score (20 points)
        $industryScore = $this->scoreIndustry($lead);
        $scores['industry'] = $industryScore;
        $details['industry'] = [
            'score' => $industryScore,
            'max_score' => 100,
            'weight' => self::WEIGHT_INDUSTRY,
            'weighted_score' => round($industryScore * self::WEIGHT_INDUSTRY / 100, 2),
            'value' => $lead->industry ?? $this->getIndustryFromEnrichment($lead),
            'explanation' => $this->getIndustryExplanation($lead),
        ];
        $totalScore += $industryScore * self::WEIGHT_INDUSTRY / 100;

        // 3. Company Size Score (20 points)
        $companySizeScore = $this->scoreCompanySize($lead);
        $scores['company_size'] = $companySizeScore;
        $details['company_size'] = [
            'score' => $companySizeScore,
            'max_score' => 100,
            'weight' => self::WEIGHT_COMPANY_SIZE,
            'weighted_score' => round($companySizeScore * self::WEIGHT_COMPANY_SIZE / 100, 2),
            'value' => $this->getCompanySizeFromEnrichment($lead),
            'explanation' => $this->getCompanySizeExplanation($lead),
        ];
        $totalScore += $companySizeScore * self::WEIGHT_COMPANY_SIZE / 100;

        // 4. Engagement Score (25 points)
        $engagementScore = $this->scoreEngagement($lead);
        $scores['engagement'] = $engagementScore;
        $details['engagement'] = [
            'score' => $engagementScore,
            'max_score' => 100,
            'weight' => self::WEIGHT_ENGAGEMENT,
            'weighted_score' => round($engagementScore * self::WEIGHT_ENGAGEMENT / 100, 2),
            'value' => [
                'email_opens' => $lead->email_opens ?? 0,
                'email_clicks' => $lead->email_clicks ?? 0,
            ],
            'explanation' => $this->getEngagementExplanation($lead),
        ];
        $totalScore += $engagementScore * self::WEIGHT_ENGAGEMENT / 100;

        // 5. Website Behavior Score (20 points)
        $websiteScore = $this->scoreWebsiteBehavior($lead);
        $scores['website'] = $websiteScore;
        $details['website'] = [
            'score' => $websiteScore,
            'max_score' => 100,
            'weight' => self::WEIGHT_WEBSITE,
            'weighted_score' => round($websiteScore * self::WEIGHT_WEBSITE / 100, 2),
            'value' => [
                'visits' => $lead->website_visits ?? 0,
                'page_views' => $lead->page_views ?? 0,
                'time_on_site' => $lead->time_on_site_seconds ?? 0,
            ],
            'explanation' => $this->getWebsiteExplanation($lead),
        ];
        $totalScore += $websiteScore * self::WEIGHT_WEBSITE / 100;

        $finalScore = (int) round($totalScore);
        $finalScore = min(100, max(0, $finalScore)); // Ensure 0-100 range

        return [
            'total_score' => $finalScore,
            'priority' => $this->getPriority($finalScore),
            'scores' => $scores,
            'details' => $details,
            'formula' => $this->getFormulaExplanation($details, $finalScore),
            'suggested_action' => $this->getSuggestedAction($finalScore, $lead),
        ];
    }

    /**
     * Score based on lead source
     */
    private function scoreSource(Lead $lead): int
    {
        if (!$lead->source) {
            return 30; // Default for unknown source
        }

        return self::SOURCE_SCORES[$lead->source] ?? 30;
    }

    /**
     * Score based on industry
     */
    private function scoreIndustry(Lead $lead): int
    {
        $industry = $lead->industry ?? $this->getIndustryFromEnrichment($lead);
        
        if (!$industry) {
            return 50; // Neutral score if no industry data
        }

        // Normalize industry name
        $industry = ucfirst(strtolower(trim($industry)));

        // Check exact match
        if (isset(self::INDUSTRY_SCORES[$industry])) {
            return self::INDUSTRY_SCORES[$industry];
        }

        // Check partial match
        foreach (self::INDUSTRY_SCORES as $key => $score) {
            if (stripos($industry, $key) !== false || stripos($key, $industry) !== false) {
                return $score;
            }
        }

        return 50; // Default for unknown industry
    }

    /**
     * Score based on company size
     */
    private function scoreCompanySize(Lead $lead): int
    {
        $employees = $this->getEmployeesFromEnrichment($lead);
        
        if (!$employees) {
            return 50; // Neutral if no data
        }

        if ($employees >= 1000) {
            return self::COMPANY_SIZE_SCORES['enterprise'];
        } elseif ($employees >= 500) {
            return self::COMPANY_SIZE_SCORES['large'];
        } elseif ($employees >= 100) {
            return self::COMPANY_SIZE_SCORES['medium'];
        } elseif ($employees >= 10) {
            return self::COMPANY_SIZE_SCORES['small'];
        } else {
            return self::COMPANY_SIZE_SCORES['startup'];
        }
    }

    /**
     * Score based on email engagement
     */
    private function scoreEngagement(Lead $lead): int
    {
        $opens = $lead->email_opens ?? 0;
        $clicks = $lead->email_clicks ?? 0;

        // Scoring logic:
        // - Opens: 0 = 0, 1-2 = 30, 3-5 = 60, 6+ = 100
        // - Clicks: 0 = 0, 1 = 40, 2-3 = 70, 4+ = 100
        // Combined: (opens_score * 0.4) + (clicks_score * 0.6)

        $opensScore = 0;
        if ($opens >= 6) {
            $opensScore = 100;
        } elseif ($opens >= 3) {
            $opensScore = 60;
        } elseif ($opens >= 1) {
            $opensScore = 30;
        }

        $clicksScore = 0;
        if ($clicks >= 4) {
            $clicksScore = 100;
        } elseif ($clicks >= 2) {
            $clicksScore = 70;
        } elseif ($clicks >= 1) {
            $clicksScore = 40;
        }

        return (int) round(($opensScore * 0.4) + ($clicksScore * 0.6));
    }

    /**
     * Score based on website behavior
     */
    private function scoreWebsiteBehavior(Lead $lead): int
    {
        $visits = $lead->website_visits ?? 0;
        $pageViews = $lead->page_views ?? 0;
        $timeOnSite = $lead->time_on_site_seconds ?? 0;

        // Scoring logic:
        // - Visits: 0 = 0, 1 = 20, 2-3 = 50, 4+ = 100
        // - Page views: 0 = 0, 1-2 = 30, 3-5 = 60, 6+ = 100
        // - Time on site: 0 = 0, <30s = 20, 30-120s = 50, 120-300s = 80, 300s+ = 100
        // Combined: (visits_score * 0.4) + (page_views_score * 0.3) + (time_score * 0.3)

        $visitsScore = 0;
        if ($visits >= 4) {
            $visitsScore = 100;
        } elseif ($visits >= 2) {
            $visitsScore = 50;
        } elseif ($visits >= 1) {
            $visitsScore = 20;
        }

        $pageViewsScore = 0;
        if ($pageViews >= 6) {
            $pageViewsScore = 100;
        } elseif ($pageViews >= 3) {
            $pageViewsScore = 60;
        } elseif ($pageViews >= 1) {
            $pageViewsScore = 30;
        }

        $timeScore = 0;
        if ($timeOnSite >= 300) {
            $timeScore = 100;
        } elseif ($timeOnSite >= 120) {
            $timeScore = 80;
        } elseif ($timeOnSite >= 30) {
            $timeScore = 50;
        } elseif ($timeOnSite > 0) {
            $timeScore = 20;
        }

        return (int) round(($visitsScore * 0.4) + ($pageViewsScore * 0.3) + ($timeScore * 0.3));
    }

    /**
     * Get priority label (Hot/Warm/Cold)
     */
    private function getPriority(int $score): string
    {
        if ($score >= 70) {
            return 'hot';
        } elseif ($score >= 40) {
            return 'warm';
        } else {
            return 'cold';
        }
    }

    /**
     * Get suggested sales action based on score and lead status
     */
    private function getSuggestedAction(int $score, Lead $lead): array
    {
        $priority = $this->getPriority($score);

        if ($priority === 'hot') {
            if ($lead->status === Lead::STATUS_NEW) {
                return [
                    'action' => 'immediate_contact',
                    'label' => 'Contact Immediately',
                    'description' => 'High-quality lead. Call or email within 1 hour.',
                    'icon' => 'pi pi-phone',
                    'urgency' => 'high',
                ];
            } elseif ($lead->status === Lead::STATUS_CONTACTED) {
                return [
                    'action' => 'follow_up',
                    'label' => 'Follow Up Today',
                    'description' => 'Continue engagement. Schedule a demo or meeting.',
                    'icon' => 'pi pi-calendar',
                    'urgency' => 'high',
                ];
            } else {
                return [
                    'action' => 'nurture',
                    'label' => 'Continue Nurturing',
                    'description' => 'Maintain engagement with personalized content.',
                    'icon' => 'pi pi-heart',
                    'urgency' => 'medium',
                ];
            }
        } elseif ($priority === 'warm') {
            if ($lead->status === Lead::STATUS_NEW) {
                return [
                    'action' => 'contact_soon',
                    'label' => 'Contact Within 24 Hours',
                    'description' => 'Good quality lead. Reach out within 24 hours.',
                    'icon' => 'pi pi-envelope',
                    'urgency' => 'medium',
                ];
            } else {
                return [
                    'action' => 'nurture',
                    'label' => 'Nurture with Content',
                    'description' => 'Send relevant content and educational materials.',
                    'icon' => 'pi pi-book',
                    'urgency' => 'medium',
                ];
            }
        } else {
            // Cold leads
            return [
                'action' => 'automated_nurture',
                'label' => 'Automated Nurture Campaign',
                'description' => 'Add to automated email sequence. Re-engage when score improves.',
                'icon' => 'pi pi-send',
                'urgency' => 'low',
            ];
        }
    }

    /**
     * Get formula explanation
     */
    private function getFormulaExplanation(array $details, int $totalScore): string
    {
        $parts = [];
        foreach ($details as $key => $detail) {
            $parts[] = sprintf(
                '%s (%d%% × %d = %.1f)',
                ucfirst(str_replace('_', ' ', $key)),
                $detail['score'],
                $detail['weight'],
                $detail['weighted_score']
            );
        }
        $parts[] = sprintf('Total = %d/100', $totalScore);
        
        return implode(' + ', $parts);
    }

    /**
     * Get explanation for each scoring component
     */
    private function getSourceExplanation(?string $source): string
    {
        if (!$source) {
            return 'No source specified';
        }

        $explanations = [
            Lead::SOURCE_REFERRAL => 'Referral leads are highest quality (100 points)',
            Lead::SOURCE_WEBSITE => 'Website leads show high intent (80 points)',
            Lead::SOURCE_EMAIL => 'Email campaign leads show medium intent (60 points)',
            Lead::SOURCE_PHONE => 'Phone leads show medium intent (50 points)',
            Lead::SOURCE_SOCIAL => 'Social media leads show lower intent (40 points)',
            Lead::SOURCE_OTHER => 'Unknown source quality (30 points)',
        ];

        return $explanations[$source] ?? 'Unknown source';
    }

    private function getIndustryExplanation(Lead $lead): string
    {
        $industry = $lead->industry ?? $this->getIndustryFromEnrichment($lead);
        
        if (!$industry) {
            return 'No industry data available';
        }

        return sprintf('Industry: %s. Score based on target industry match.', $industry);
    }

    private function getCompanySizeExplanation(Lead $lead): string
    {
        $employees = $this->getEmployeesFromEnrichment($lead);
        
        if (!$employees) {
            return 'No company size data available';
        }

        $size = $this->getCompanySizeFromEnrichment($lead);
        return sprintf('Company size: %s (%d employees). Larger companies score higher.', $size, $employees);
    }

    private function getEngagementExplanation(Lead $lead): string
    {
        $opens = $lead->email_opens ?? 0;
        $clicks = $lead->email_clicks ?? 0;

        if ($opens === 0 && $clicks === 0) {
            return 'No email engagement yet. Score will improve with opens and clicks.';
        }

        return sprintf(
            'Email engagement: %d opens, %d clicks. Higher engagement = higher score.',
            $opens,
            $clicks
        );
    }

    private function getWebsiteExplanation(Lead $lead): string
    {
        $visits = $lead->website_visits ?? 0;
        $pageViews = $lead->page_views ?? 0;
        $timeOnSite = $lead->time_on_site_seconds ?? 0;

        if ($visits === 0) {
            return 'No website visits tracked yet. Score will improve with visits and engagement.';
        }

        return sprintf(
            'Website behavior: %d visits, %d page views, %s on site. More engagement = higher score.',
            $visits,
            $pageViews,
            $this->formatTime($timeOnSite)
        );
    }

    /**
     * Helper methods to extract data from enrichment_data
     */
    private function getIndustryFromEnrichment(Lead $lead): ?string
    {
        $enrichment = $lead->enrichment_data ?? [];
        return $enrichment['industry'] ?? null;
    }

    private function getEmployeesFromEnrichment(Lead $lead): ?int
    {
        $enrichment = $lead->enrichment_data ?? [];
        return $enrichment['employees'] ?? null;
    }

    private function getCompanySizeFromEnrichment(Lead $lead): string
    {
        $employees = $this->getEmployeesFromEnrichment($lead);
        
        if (!$employees) {
            return 'Unknown';
        }

        if ($employees >= 1000) {
            return 'Enterprise';
        } elseif ($employees >= 500) {
            return 'Large';
        } elseif ($employees >= 100) {
            return 'Medium';
        } elseif ($employees >= 10) {
            return 'Small';
        } else {
            return 'Startup';
        }
    }

    private function formatTime(int $seconds): string
    {
        if ($seconds < 60) {
            return sprintf('%ds', $seconds);
        } elseif ($seconds < 3600) {
            return sprintf('%dm %ds', floor($seconds / 60), $seconds % 60);
        } else {
            return sprintf('%dh %dm', floor($seconds / 3600), floor(($seconds % 3600) / 60));
        }
    }
}

