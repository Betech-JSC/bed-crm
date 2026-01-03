<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Workflow;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;

class LeadScoringController extends Controller
{
    /**
     * Score a single lead using comprehensive scoring system
     */
    public function score(Lead $lead): RedirectResponse
    {
        $scoringService = app(\App\Services\LeadScoringService::class);
        $result = $scoringService->calculateScore($lead);

        // Update lead with new score and details
        $lead->update([
            'score' => $result['total_score'],
            'scoring_details' => $result,
            'last_scored_at' => now(),
        ]);

        // Trigger workflow for lead scored
        app(WorkflowService::class)->trigger(Workflow::TRIGGER_LEAD_SCORED, $lead->fresh(), [
            'score' => $result['total_score'],
            'priority' => $result['priority'],
        ]);

        $priorityLabel = ucfirst($result['priority']);
        return Redirect::back()->with('success', "Lead scored: {$result['total_score']}/100 ({$priorityLabel} Lead)");
    }

    /**
     * Score all leads using comprehensive scoring system
     */
    public function scoreAll(): RedirectResponse
    {
        $leads = Auth::user()->account->leads()->get();
        $scored = 0;
        $scoringService = app(\App\Services\LeadScoringService::class);

        foreach ($leads as $lead) {
            $result = $scoringService->calculateScore($lead);
            $lead->update([
                'score' => $result['total_score'],
                'scoring_details' => $result,
                'last_scored_at' => now(),
            ]);
            $scored++;
        }

        return Redirect::back()->with('success', "Scored {$scored} leads.");
    }

    /**
     * Enrich lead data using Data Enrichment Service
     */
    public function enrich(Lead $lead): JsonResponse
    {
        $enrichmentService = app(DataEnrichmentService::class);
        
        $enrichmentData = $enrichmentService->enrich(
            $lead->email ?? '',
            $lead->company
        );

        $lead->update([
            'enrichment_data' => $enrichmentData,
        ]);

        // Auto-score after enrichment
        $result = $lead->scoreAgainstICPs($enrichmentData);

        return response()->json([
            'success' => true,
            'enrichment_data' => $enrichmentData,
            'score' => $lead->fresh()->score,
            'icp_match' => $result ? [
                'icp_name' => $result['icp_name'],
                'is_match' => $result['is_match'],
            ] : null,
        ]);
    }
}
