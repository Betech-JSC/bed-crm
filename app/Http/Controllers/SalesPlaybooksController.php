<?php

namespace App\Http\Controllers;

use App\Models\SalesPlaybook;
use App\Models\Deal;
use App\Services\SalesPlaybookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class SalesPlaybooksController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('SalesPlaybooks/Index', [
            'playbooks' => Auth::user()->account->salesPlaybooks()
                ->active()
                ->orderedByPriority()
                ->get()
                ->map(fn ($playbook) => [
                    'id' => $playbook->id,
                    'name' => $playbook->name,
                    'description' => $playbook->description,
                    'industries' => $playbook->industries ?? [],
                    'deal_stages' => $playbook->deal_stages ?? [],
                    'priority' => $playbook->priority,
                    'is_active' => $playbook->is_active,
                    'created_at' => $playbook->created_at->format('Y-m-d'),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('SalesPlaybooks/Create', [
            'dealStages' => Deal::getStages(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'industries' => ['nullable', 'array'],
            'industries.*' => ['string', 'max:100'],
            'deal_stages' => ['nullable', 'array'],
            'deal_stages.*' => ['string'],
            'pain_points' => ['nullable', 'array'],
            'pain_points.*' => ['string', 'max:100'],
            'talking_points' => ['nullable', 'string'],
            'email_template_subject' => ['nullable', 'string', 'max:200'],
            'email_template_body' => ['nullable', 'string'],
            'recommended_documents' => ['nullable', 'array'],
            'recommended_documents.*' => ['string', 'max:200'],
            'objections_handling' => ['nullable', 'string'],
            'next_steps' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        Auth::user()->account->salesPlaybooks()->create($validated);

        return Redirect::route('sales-playbooks')->with('success', 'Sales playbook created.');
    }

    public function show(SalesPlaybook $playbook): Response
    {
        return Inertia::render('SalesPlaybooks/Show', [
            'playbook' => [
                'id' => $playbook->id,
                'name' => $playbook->name,
                'description' => $playbook->description,
                'industries' => $playbook->industries ?? [],
                'deal_stages' => $playbook->deal_stages ?? [],
                'pain_points' => $playbook->pain_points ?? [],
                'talking_points' => $playbook->talking_points,
                'email_template_subject' => $playbook->email_template_subject,
                'email_template_body' => $playbook->email_template_body,
                'recommended_documents' => $playbook->recommended_documents ?? [],
                'objections_handling' => $playbook->objections_handling,
                'next_steps' => $playbook->next_steps,
                'tags' => $playbook->tags ?? [],
                'priority' => $playbook->priority,
                'is_active' => $playbook->is_active,
            ],
        ]);
    }

    public function edit(SalesPlaybook $playbook): Response
    {
        return Inertia::render('SalesPlaybooks/Edit', [
            'playbook' => [
                'id' => $playbook->id,
                'name' => $playbook->name,
                'description' => $playbook->description,
                'industries' => $playbook->industries ?? [],
                'deal_stages' => $playbook->deal_stages ?? [],
                'pain_points' => $playbook->pain_points ?? [],
                'talking_points' => $playbook->talking_points,
                'email_template_subject' => $playbook->email_template_subject,
                'email_template_body' => $playbook->email_template_body,
                'recommended_documents' => $playbook->recommended_documents ?? [],
                'objections_handling' => $playbook->objections_handling,
                'next_steps' => $playbook->next_steps,
                'tags' => $playbook->tags ?? [],
                'priority' => $playbook->priority,
                'is_active' => $playbook->is_active,
            ],
            'dealStages' => Deal::getStages(),
        ]);
    }

    public function update(Request $request, SalesPlaybook $playbook): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'industries' => ['nullable', 'array'],
            'industries.*' => ['string', 'max:100'],
            'deal_stages' => ['nullable', 'array'],
            'deal_stages.*' => ['string'],
            'pain_points' => ['nullable', 'array'],
            'pain_points.*' => ['string', 'max:100'],
            'talking_points' => ['nullable', 'string'],
            'email_template_subject' => ['nullable', 'string', 'max:200'],
            'email_template_body' => ['nullable', 'string'],
            'recommended_documents' => ['nullable', 'array'],
            'recommended_documents.*' => ['string', 'max:200'],
            'objections_handling' => ['nullable', 'string'],
            'next_steps' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $playbook->update($validated);

        return Redirect::back()->with('success', 'Sales playbook updated.');
    }

    public function destroy(SalesPlaybook $playbook): RedirectResponse
    {
        $playbook->delete();

        return Redirect::back()->with('success', 'Sales playbook deleted.');
    }

    /**
     * Get playbook suggestions for a deal.
     */
    public function suggestionsForDeal(Deal $deal): Response
    {
        $service = app(SalesPlaybookService::class);
        $playbooks = $service->getPlaybooksForDeal($deal);

        return response()->json([
            'playbooks' => $playbooks->map(fn ($playbook) => [
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

    /**
     * Get playbook suggestions for a lead.
     */
    public function suggestionsForLead(\App\Models\Lead $lead): Response
    {
        $service = app(SalesPlaybookService::class);
        $playbooks = $service->getPlaybooksForLead($lead);

        return response()->json([
            'playbooks' => $playbooks->map(fn ($playbook) => [
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
}
