<?php

namespace App\Http\Controllers;

use App\Events\LeadCreated;
use App\Http\Requests\Lead\AddNoteRequest;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Models\Lead;
use App\Models\User;
use App\Services\SalesPlaybookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Leads/Index', [
            'filters' => Request::all('search', 'status', 'source', 'assigned_to', 'trashed'),
            'leads' => Auth::user()->account->leads()
                ->with(['assignedUser:id,first_name,last_name'])
                ->orderByStatus()
                ->orderBy('created_at', 'desc')
                ->filter(Request::only('search', 'status', 'source', 'assigned_to', 'trashed'))
                ->paginate(15)
                ->withQueryString()
                ->through(fn ($lead) => [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'phone' => $lead->phone,
                    'email' => $lead->email,
                    'company' => $lead->company,
                    'source' => $lead->source,
                    'status' => $lead->status,
                    'assigned_to' => $lead->assigned_to,
                    'score' => $lead->score,

                    'assigned_user' => $lead->assignedUser ? [
                        'id' => $lead->assignedUser->id,
                        'name' => $lead->assignedUser->name,
                    ] : null,
                    'tags' => $lead->tags,
                    'deleted_at' => $lead->deleted_at,
                ]),
            'statuses' => Lead::getStatuses(),
            'sources' => Lead::getSources(),
            'salesUsers' => $this->getSalesUsers(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Leads/Create', [
            'statuses' => Lead::getStatuses(),
            'sources' => Lead::getSources(),
            'salesUsers' => $this->getSalesUsers(),
        ]);
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Check for duplicates
        $duplicate = Lead::findDuplicate(
            $validated['phone'] ?? null,
            $validated['email'] ?? null,
            Auth::user()->account_id
        );

        if ($duplicate) {
            return Redirect::back()->withErrors([
                'duplicate' => 'A lead with this phone or email already exists.',
                'duplicate_id' => $duplicate->id,
            ]);
        }

        $lead = Auth::user()->account->leads()->create($validated);

        // Dispatch event — listeners handle SLA tracking, workflow triggers, etc.
        LeadCreated::dispatch($lead->fresh());

        return Redirect::route('leads')->with('success', 'Lead created.');
    }

    public function edit(Lead $lead): Response
    {


        return Inertia::render('Leads/Edit', [
            'lead' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'company' => $lead->company,
                'source' => $lead->source,
                'status' => $lead->status,
                'assigned_to' => $lead->assigned_to,
                'notes' => $lead->notes,
                'tags' => $lead->tags ?? [],
                'deleted_at' => $lead->deleted_at,
                'score' => $lead->score,
                'priority' => $lead->priority ?? 'cold',
                'priority_label' => $lead->priority_label ?? 'Cold',
                'priority_severity' => $lead->priority_severity ?? 'info',
                'scoring_details' => $lead->scoring_details,
                'enrichment_data' => $lead->enrichment_data,

                'email_opens' => $lead->email_opens ?? 0,
                'email_clicks' => $lead->email_clicks ?? 0,
                'website_visits' => $lead->website_visits ?? 0,
                'page_views' => $lead->page_views ?? 0,
                'time_on_site_seconds' => $lead->time_on_site_seconds ?? 0,
                'sla_status' => $lead->sla_status ?? 'pending',
                'sla_started_at' => $lead->sla_started_at?->toISOString(),
                'first_response_at' => $lead->first_response_at?->toISOString(),
                'response_time_minutes' => $lead->response_time_minutes,
                'sla_setting' => $lead->slaSetting ? [
                    'id' => $lead->slaSetting->id,
                    'name' => $lead->slaSetting->name,
                    'first_response_threshold' => $lead->slaSetting->first_response_threshold,
                    'warning_threshold' => $lead->slaSetting->warning_threshold,
                ] : null,
                'deal' => $lead->deal ? [
                    'id' => $lead->deal->id,
                ] : null,
            ],
            'activities' => $lead->activities()
                ->with('user:id,first_name,last_name')
                ->orderBy('date', 'desc')
                ->get()
                ->map(fn ($activity) => [
                    'id' => $activity->id,
                    'type' => $activity->type,
                    'title' => $activity->title,
                    'description' => $activity->description,
                    'date' => $activity->date->toISOString(),
                    'user' => $activity->user ? [
                        'id' => $activity->user->id,
                        'name' => $activity->user->name,
                    ] : null,
                ]),
            'playbookSuggestions' => app(SalesPlaybookService::class)->getPlaybooksForLead($lead)->map(fn ($playbook) => [
                'id' => $playbook->id,
                'name' => $playbook->name,
                'description' => $playbook->description,
                'talking_points' => $playbook->talking_points,
                'email_template_subject' => $playbook->email_template_subject,
                'email_template_body' => $playbook->email_template_body,
                'recommended_documents' => $playbook->recommended_documents ?? [],
                'objections_handling' => $playbook->objections_handling,
                'next_steps' => $playbook->next_steps,
            ]),
            'statuses' => Lead::getStatuses(),
            'sources' => Lead::getSources(),
            'salesUsers' => $this->getSalesUsers(),
        ]);
    }

    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validated();

        // Check for duplicates (excluding current lead)
        $duplicate = Lead::findDuplicate(
            $validated['phone'] ?? null,
            $validated['email'] ?? null,
            Auth::user()->account_id,
            $lead->id
        );

        if ($duplicate) {
            return Redirect::back()->withErrors([
                'duplicate' => 'A lead with this phone or email already exists.',
                'duplicate_id' => $duplicate->id,
            ]);
        }

        $lead->update($validated);

        return Redirect::back()->with('success', 'Lead updated.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return Redirect::back()->with('success', 'Lead deleted.');
    }

    public function restore(Lead $lead): RedirectResponse
    {
        $lead->restore();

        return Redirect::back()->with('success', 'Lead restored.');
    }

    public function addNote(AddNoteRequest $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validated();

        $currentNotes = $lead->notes ?? '';
        $timestamp = now()->format('Y-m-d H:i:s');
        $newNote = "[{$timestamp}] " . $validated['note'] . "\n\n";

        $lead->update([
            'notes' => $newNote . $currentNotes,
        ]);

        return Redirect::back()->with('success', 'Note added.');
    }

    /**
     * Get sales users for the current account.
     */
    private function getSalesUsers(): \Illuminate\Support\Collection
    {
        return Auth::user()->account->users()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SALE])
            ->orderByName()
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]);
    }
}
