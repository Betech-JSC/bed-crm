<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use App\Services\SalesPlaybookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DealsController extends Controller
{
    public function index(): Response
    {
        $deals = Auth::user()->account->deals()
            ->with(['lead', 'assignedUser:id,first_name,last_name'])
            ->open()
            ->orderBy('stage')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by stage and format data
        $dealsByStage = [];
        foreach (Deal::getStages() as $stageKey => $stageLabel) {
            $dealsByStage[$stageKey] = $deals
                ->where('stage', $stageKey)
                ->map(fn ($deal) => [
                    'id' => $deal->id,
                    'title' => $deal->title,
                    'value' => $deal->value ? (float) $deal->value : null,
                    'expected_close_date' => $deal->expected_close_date?->format('Y-m-d'),
                    'status' => $deal->status,
                    'stage' => $deal->stage,
                    'lead' => $deal->lead ? [
                        'id' => $deal->lead->id,
                        'name' => $deal->lead->name,
                        'company' => $deal->lead->company,
                    ] : null,
                    'assigned_user' => $deal->assignedUser ? [
                        'id' => $deal->assignedUser->id,
                        'name' => $deal->assignedUser->name,
                    ] : null,
                ])
                ->values()
                ->toArray();
        }

        return Inertia::render('Deals/Index', [
            'dealsByStage' => $dealsByStage,
            'stages' => Deal::getStages(),
            'salesUsers' => Auth::user()->account->users()
                ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SALE])
                ->orderByName()
                ->get()
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Deals/Create', [
            'leads' => Auth::user()->account->leads()
                ->whereDoesntHave('deal')
                ->orderBy('name')
                ->get()
                ->map(fn ($lead) => [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'company' => $lead->company,
                ]),
            'stages' => Deal::getStages(),
            'salesUsers' => Auth::user()->account->users()
                ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SALE])
                ->orderByName()
                ->get()
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                ]),
        ]);
    }

    public function store(): RedirectResponse
    {
        $validated = Request::validate([
            'lead_id' => [
                'nullable',
                Rule::exists('leads', 'id')->where(function ($query) {
                    $query->where('account_id', Auth::user()->account_id);
                }),
            ],
            'title' => ['required', 'max:200'],
            'stage' => ['required', 'max:50'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'expected_close_date' => ['nullable', 'date'],
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('account_id', Auth::user()->account_id)
                        ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SALE]);
                }),
            ],
            'notes' => ['nullable', 'string'],
        ]);

        $deal = Auth::user()->account->deals()->create(array_merge($validated, [
            'status' => Deal::STATUS_OPEN,
        ]));

        return Redirect::route('deals')->with('success', 'Deal created.');
    }

    public function convertFromLead(Lead $lead): RedirectResponse
    {
        // Check if lead already has a deal
        if ($lead->deal) {
            return Redirect::back()->withErrors(['error' => 'This lead already has a deal.']);
        }

        $deal = Auth::user()->account->deals()->create([
            'lead_id' => $lead->id,
            'title' => $lead->company ? "Deal with {$lead->company}" : "Deal with {$lead->name}",
            'stage' => Deal::STAGE_PROSPECTING,
            'status' => Deal::STATUS_OPEN,
            'assigned_to' => $lead->assigned_to,
            'notes' => $lead->notes,
        ]);

        return Redirect::route('deals.edit', $deal)->with('success', 'Lead converted to deal.');
    }

    public function edit(Deal $deal): Response
    {
        return Inertia::render('Deals/Edit', [
            'deal' => [
                'id' => $deal->id,
                'lead_id' => $deal->lead_id,
                'title' => $deal->title,
                'stage' => $deal->stage,
                'value' => $deal->value,
                'expected_close_date' => $deal->expected_close_date?->format('Y-m-d'),
                'status' => $deal->status,
                'lost_reason' => $deal->lost_reason,
                'assigned_to' => $deal->assigned_to,
                'notes' => $deal->notes,
                'deleted_at' => $deal->deleted_at,
                'lead' => $deal->lead ? [
                    'id' => $deal->lead->id,
                    'name' => $deal->lead->name,
                    'company' => $deal->lead->company,
                ] : null,
            ],
            'activities' => $deal->activities()
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
            'stages' => Deal::getStages(),
            'statuses' => Deal::getStatuses(),
            'salesUsers' => Auth::user()->account->users()
                ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SALE])
                ->orderByName()
                ->get()
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                ]),
            'proposals' => $deal->proposals()
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($proposal) => [
                    'id' => $proposal->id,
                    'title' => $proposal->title,
                    'version' => $proposal->version,
                    'status' => $proposal->status,
                    'status_label' => \App\Models\Proposal::getStatuses()[$proposal->status] ?? $proposal->status,
                    'status_severity' => $proposal->status_severity,
                    'amount' => $proposal->amount,
                    'created_at' => $proposal->created_at->toISOString(),
                ]),
            'playbookSuggestions' => app(SalesPlaybookService::class)->getPlaybooksForDeal($deal)->map(fn ($playbook) => [
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
        ]);
    }

    public function update(Deal $deal): RedirectResponse
    {
        $validated = Request::validate([
            'title' => ['required', 'max:200'],
            'stage' => ['required', 'max:50'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'expected_close_date' => ['nullable', 'date'],
            'status' => ['required', 'in:open,won,lost'],
            'lost_reason' => ['nullable', 'required_if:status,lost', 'string'],
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('account_id', Auth::user()->account_id)
                        ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SALE]);
                }),
            ],
            'notes' => ['nullable', 'string'],
        ]);

        $deal->update($validated);

        return Redirect::back()->with('success', 'Deal updated.');
    }

    public function updateStage(Deal $deal): \Illuminate\Http\JsonResponse
    {
        $validated = Request::validate([
            'stage' => ['required', 'max:50'],
        ]);

        $deal->update(['stage' => $validated['stage']]);

        return response()->json(['success' => true]);
    }

    public function markWon(Deal $deal): RedirectResponse
    {
        $deal->update([
            'status' => Deal::STATUS_WON,
            'lost_reason' => null,
        ]);

        return Redirect::back()->with('success', 'Deal marked as won.');
    }

    public function markLost(Deal $deal): RedirectResponse
    {
        $validated = Request::validate([
            'lost_reason' => ['required', 'string', 'max:500'],
        ]);

        $deal->update([
            'status' => Deal::STATUS_LOST,
            'lost_reason' => $validated['lost_reason'],
        ]);

        return Redirect::back()->with('success', 'Deal marked as lost.');
    }

    public function destroy(Deal $deal): RedirectResponse
    {
        $deal->delete();

        return Redirect::back()->with('success', 'Deal deleted.');
    }

    public function restore(Deal $deal): RedirectResponse
    {
        $deal->restore();

        return Redirect::back()->with('success', 'Deal restored.');
    }
}
