<?php

namespace App\Services\AI;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Activity;
use App\Models\FinancialTransaction;
use App\Models\SalesTarget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * ContextBuilder
 * ──────────────
 * Builds rich CRM context for AI requests.
 * Turns raw CRM data into structured context the AI can understand.
 */
class ContextBuilder
{
    /**
     * Build full context for the current user.
     */
    public function forCurrentUser(): array
    {
        $user = Auth::user();
        if (!$user) return [];

        return [
            'user' => $this->userContext($user),
            'crm_summary' => $this->crmSummary($user->account_id),
            'recent_activity' => $this->recentActivity($user->account_id),
            'current_date' => now()->format('Y-m-d H:i'),
            'timezone' => 'Asia/Ho_Chi_Minh',
        ];
    }

    /**
     * Build context around a specific entity (lead, deal, contact).
     */
    public function forEntity(string $type, int $id): array
    {
        $base = $this->forCurrentUser();

        $base['focus_entity'] = match ($type) {
            'lead' => $this->leadContext($id),
            'deal' => $this->dealContext($id),
            'contact' => $this->contactContext($id),
            default => null,
        };

        return $base;
    }

    /**
     * Build lightweight context (less tokens, faster).
     */
    public function lightweight(): array
    {
        $user = Auth::user();
        if (!$user) return [];

        return [
            'user_name' => $user->name,
            'role' => $user->role ?? 'user',
            'account_id' => $user->account_id,
            'date' => now()->format('Y-m-d'),
        ];
    }

    // ── Private builders ──

    private function userContext($user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ?? 'user',
            'account_id' => $user->account_id,
        ];
    }

    private function crmSummary(int $accountId): array
    {
        return [
            'leads' => [
                'total' => Lead::where('account_id', $accountId)->count(),
                'new_today' => Lead::where('account_id', $accountId)->whereDate('created_at', today())->count(),
                'new_this_week' => Lead::where('account_id', $accountId)->whereBetween('created_at', [now()->startOfWeek(), now()])->count(),
                'by_status' => Lead::where('account_id', $accountId)
                    ->select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),
            ],
            'deals' => [
                'total_open' => Deal::where('account_id', $accountId)->where('status', 'open')->count(),
                'total_value' => (float) Deal::where('account_id', $accountId)->where('status', 'open')->sum('value'),
                'won_this_month' => Deal::where('account_id', $accountId)
                    ->where('status', 'won')
                    ->whereMonth('updated_at', now()->month)
                    ->count(),
                'won_value_this_month' => (float) Deal::where('account_id', $accountId)
                    ->where('status', 'won')
                    ->whereMonth('updated_at', now()->month)
                    ->sum('value'),
                'by_stage' => Deal::where('account_id', $accountId)
                    ->where('status', 'open')
                    ->select('stage', DB::raw('count(*) as count'), DB::raw('sum(value) as total_value'))
                    ->groupBy('stage')
                    ->get()
                    ->keyBy('stage')
                    ->toArray(),
            ],
            'contacts' => [
                'total' => Contact::count(), // contacts are not scoped by account in base model
            ],
        ];
    }

    private function recentActivity(int $accountId): array
    {
        try {
            $activities = Activity::whereHas('subject', function ($q) use ($accountId) {
                // Activities might not have account_id directly
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($a) => [
                'type' => $a->type ?? 'activity',
                'description' => $a->description ?? '',
                'date' => $a->date?->format('Y-m-d H:i') ?? $a->created_at->format('Y-m-d H:i'),
            ])
            ->toArray();

            return $activities;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function leadContext(int $id): array
    {
        $lead = Lead::find($id);
        if (!$lead) return ['error' => 'Lead not found'];

        return [
            'type' => 'lead',
            'id' => $lead->id,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'company' => $lead->company,
            'industry' => $lead->industry,
            'source' => $lead->source,
            'status' => $lead->status,
            'score' => $lead->score,
            'notes' => $lead->notes,
            'tags' => $lead->tags,
            'created_at' => $lead->created_at?->format('Y-m-d'),
        ];
    }

    private function dealContext(int $id): array
    {
        $deal = Deal::with('lead')->find($id);
        if (!$deal) return ['error' => 'Deal not found'];

        return [
            'type' => 'deal',
            'id' => $deal->id,
            'title' => $deal->title,
            'stage' => $deal->stage,
            'status' => $deal->status,
            'value' => (float) $deal->value,
            'expected_close_date' => $deal->expected_close_date?->format('Y-m-d'),
            'win_probability' => $deal->win_probability,
            'days_in_stage' => $deal->days_in_stage,
            'health_score' => $deal->health_score,
            'at_risk' => $deal->at_risk,
            'next_steps' => $deal->next_steps,
            'notes' => $deal->notes,
            'lead_name' => $deal->lead?->name,
            'lead_company' => $deal->lead?->company,
        ];
    }

    private function contactContext(int $id): array
    {
        $contact = Contact::with('organization')->find($id);
        if (!$contact) return ['error' => 'Contact not found'];

        return [
            'type' => 'contact',
            'id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email ?? null,
            'phone' => $contact->phone ?? null,
            'organization' => $contact->organization?->name,
        ];
    }
}
