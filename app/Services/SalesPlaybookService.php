<?php

namespace App\Services;

use App\Models\SalesPlaybook;
use App\Models\Deal;
use App\Models\Lead;

class SalesPlaybookService
{
    /**
     * Get matching playbooks for a deal.
     */
    public function getPlaybooksForDeal(Deal $deal): \Illuminate\Support\Collection
    {
        $industry = $this->getIndustryFromDeal($deal);
        $dealStage = $deal->stage;
        $painPoints = $this->extractPainPointsFromDeal($deal);

        return $this->findMatchingPlaybooks($deal->account_id, $industry, $dealStage, $painPoints);
    }

    /**
     * Get matching playbooks for a lead.
     */
    public function getPlaybooksForLead(Lead $lead): \Illuminate\Support\Collection
    {
        $industry = $lead->industry ?? $this->getIndustryFromEnrichment($lead);
        $dealStage = null; // Leads don't have stages, but we can use status
        $painPoints = $this->extractPainPointsFromLead($lead);

        return $this->findMatchingPlaybooks($lead->account_id, $industry, $dealStage, $painPoints);
    }

    /**
     * Find matching playbooks based on criteria.
     */
    public function findMatchingPlaybooks(
        int $accountId,
        ?string $industry = null,
        ?string $dealStage = null,
        ?array $painPoints = null
    ): \Illuminate\Support\Collection {
        $playbooks = SalesPlaybook::where('account_id', $accountId)
            ->active()
            ->get();

        $matchedPlaybooks = [];

        foreach ($playbooks as $playbook) {
            if ($playbook->matches($industry, $dealStage, $painPoints)) {
                $score = $playbook->calculateMatchScore($industry, $dealStage, $painPoints);
                $matchedPlaybooks[] = [
                    'playbook' => $playbook,
                    'score' => $score,
                ];
            }
        }

        // Sort by score (descending) and priority
        usort($matchedPlaybooks, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return $b['playbook']->priority <=> $a['playbook']->priority;
            }
            return $b['score'] <=> $a['score'];
        });

        return collect($matchedPlaybooks)->map(fn ($item) => $item['playbook']);
    }

    /**
     * Get industry from deal (via lead or enrichment data).
     */
    private function getIndustryFromDeal(Deal $deal): ?string
    {
        if ($deal->lead) {
            return $deal->lead->industry ?? $this->getIndustryFromEnrichment($deal->lead);
        }
        return null;
    }

    /**
     * Get industry from lead enrichment data.
     */
    private function getIndustryFromEnrichment(Lead $lead): ?string
    {
        $enrichment = $lead->enrichment_data ?? [];
        return $enrichment['industry'] ?? null;
    }

    /**
     * Extract pain points from deal notes and activities.
     */
    private function extractPainPointsFromDeal(Deal $deal): array
    {
        $painPoints = [];

        // Extract from notes
        if ($deal->notes) {
            $painPoints = array_merge($painPoints, $this->extractKeywords($deal->notes));
        }

        // Extract from activities
        foreach ($deal->activities as $activity) {
            if ($activity->description) {
                $painPoints = array_merge($painPoints, $this->extractKeywords($activity->description));
            }
        }

        return array_unique($painPoints);
    }

    /**
     * Extract pain points from lead notes and activities.
     */
    private function extractPainPointsFromLead(Lead $lead): array
    {
        $painPoints = [];

        // Extract from notes
        if ($lead->notes) {
            $painPoints = array_merge($painPoints, $this->extractKeywords($lead->notes));
        }

        // Extract from activities
        foreach ($lead->activities as $activity) {
            if ($activity->description) {
                $painPoints = array_merge($painPoints, $this->extractKeywords($activity->description));
            }
        }

        return array_unique($painPoints);
    }

    /**
     * Extract keywords/pain points from text.
     * This is a simple implementation - can be enhanced with NLP.
     */
    private function extractKeywords(string $text): array
    {
        // Common pain point keywords
        $painPointKeywords = [
            'cost', 'expensive', 'budget', 'price',
            'time', 'slow', 'efficiency', 'productivity',
            'quality', 'reliability', 'performance',
            'support', 'service', 'maintenance',
            'scalability', 'growth', 'expansion',
            'security', 'compliance', 'risk',
            'integration', 'compatibility',
            'training', 'learning curve',
        ];

        $textLower = strtolower($text);
        $foundKeywords = [];

        foreach ($painPointKeywords as $keyword) {
            if (stripos($textLower, $keyword) !== false) {
                $foundKeywords[] = $keyword;
            }
        }

        return $foundKeywords;
    }
}

