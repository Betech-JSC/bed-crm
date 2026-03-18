<?php

namespace App\Services;

use App\Models\EmailSegment;
use App\Models\EmailSegmentContact;
use App\Models\Lead;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;

class EmailSegmentService
{
    /**
     * Compute dynamic segment membership based on rules engine.
     * Returns the count of matching contacts.
     */
    public function computeSegment(EmailSegment $segment): int
    {
        $rules = $segment->rules;
        if (!$rules || empty($rules['conditions'])) {
            return 0;
        }

        $accountId = $segment->account_id;
        $matchType = $rules['match'] ?? 'all'; // all = AND, any = OR

        // Build query for leads
        $query = Lead::where('account_id', $accountId);

        foreach ($rules['conditions'] as $condition) {
            $method = $matchType === 'all' ? 'where' : 'orWhere';
            $query = $this->applyCondition($query, $condition, $method);
        }

        $leads = $query->get(['id', 'email', 'name']);

        // Clear existing dynamic entries and re-sync
        EmailSegmentContact::where('email_segment_id', $segment->id)
            ->where('source', 'dynamic')
            ->delete();

        $inserts = [];
        foreach ($leads as $lead) {
            if (!$lead->email) continue;
            $inserts[] = [
                'email_segment_id' => $segment->id,
                'contact_type' => 'lead',
                'contact_id' => $lead->id,
                'email' => $lead->email,
                'source' => 'dynamic',
                'subscribed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($inserts)) {
            DB::table('email_segment_contacts')->insert($inserts);
        }

        return count($inserts);
    }

    /**
     * Apply a single condition to the query builder
     */
    private function applyCondition($query, array $condition, string $method)
    {
        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'];

        return match ($field) {
            'lead.status' => $query->$method('status', $this->mapOperator($operator), $value),
            'lead.source' => $query->$method('source', $this->mapOperator($operator), $value),
            'lead.score' => $query->$method('score', $this->mapOperator($operator), $value),
            'deal.value' => $query->$method(function ($q) use ($operator, $value) {
                $q->whereHas('deals', fn ($d) => $d->where('value', $this->mapOperator($operator), $value));
            }),
            'tag' => $query->$method('tags', 'LIKE', "%{$value}%"),
            'engagement_level' => $query->$method(function ($q) use ($operator, $value) {
                $q->whereHas('emailScore', fn ($s) => $s->where('engagement_level',
                    is_array($value) ? 'IN' : $this->mapOperator($operator),
                    $value
                ));
            }),
            'behavior.last_opened' => $query->$method(function ($q) use ($value) {
                $q->whereHas('emailScore', fn ($s) => $s->where('last_engaged_at', '>=', now()->subDays((int) $value)));
            }),
            default => $query,
        };
    }

    private function mapOperator(string $operator): string
    {
        return match ($operator) {
            '=', 'equals' => '=',
            '!=', 'not_equals' => '!=',
            '>', 'greater_than' => '>',
            '<', 'less_than' => '<',
            '>=', 'gte' => '>=',
            '<=', 'lte' => '<=',
            'contains' => 'LIKE',
            'in' => 'IN',
            default => '=',
        };
    }

    /**
     * Get all deliverable emails from a segment (excluding suppressed)
     */
    public function getDeliverableContacts(EmailSegment $segment): array
    {
        return EmailSegmentContact::where('email_segment_id', $segment->id)
            ->whereNull('unsubscribed_at')
            ->whereNotIn('email', function ($q) use ($segment) {
                $q->select('email')
                    ->from('email_suppressions')
                    ->where('account_id', $segment->account_id);
            })
            ->get()
            ->toArray();
    }
}
