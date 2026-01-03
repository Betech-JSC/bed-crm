<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkflowsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Workflows/Index', [
            'workflows' => Auth::user()->account->workflows()
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($workflow) => [
                    'id' => $workflow->id,
                    'name' => $workflow->name,
                    'description' => $workflow->description,
                    'trigger_event' => $workflow->trigger['event'] ?? null,
                    'actions_count' => count($workflow->actions ?? []),
                    'is_active' => $workflow->is_active,
                    'execution_count' => $workflow->execution_count,
                    'created_at' => $workflow->created_at->format('Y-m-d'),
                ]),
            'triggerEvents' => [
                Workflow::TRIGGER_LEAD_CREATED => 'Lead Created',
                Workflow::TRIGGER_LEAD_SCORED => 'Lead Scored',
                Workflow::TRIGGER_LEAD_STATUS_CHANGED => 'Lead Status Changed',
                Workflow::TRIGGER_DEAL_CREATED => 'Deal Created',
                Workflow::TRIGGER_DEAL_STAGE_CHANGED => 'Deal Stage Changed',
                Workflow::TRIGGER_ACTIVITY_CREATED => 'Activity Created',
            ],
            'actionTypes' => [
                Workflow::ACTION_ASSIGN_USER => 'Assign User',
                Workflow::ACTION_SEND_EMAIL => 'Send Email',
                Workflow::ACTION_CREATE_TASK => 'Create Task',
                Workflow::ACTION_UPDATE_FIELD => 'Update Field',
                Workflow::ACTION_ADD_TAG => 'Add Tag',
                Workflow::ACTION_CREATE_DEAL => 'Create Deal',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Workflows/Create', [
            'triggerEvents' => [
                Workflow::TRIGGER_LEAD_CREATED => 'Lead Created',
                Workflow::TRIGGER_LEAD_SCORED => 'Lead Scored',
                Workflow::TRIGGER_LEAD_STATUS_CHANGED => 'Lead Status Changed',
                Workflow::TRIGGER_DEAL_CREATED => 'Deal Created',
                Workflow::TRIGGER_DEAL_STAGE_CHANGED => 'Deal Stage Changed',
                Workflow::TRIGGER_ACTIVITY_CREATED => 'Activity Created',
            ],
            'actionTypes' => [
                Workflow::ACTION_ASSIGN_USER => 'Assign User',
                Workflow::ACTION_SEND_EMAIL => 'Send Email',
                Workflow::ACTION_CREATE_TASK => 'Create Task',
                Workflow::ACTION_UPDATE_FIELD => 'Update Field',
                Workflow::ACTION_ADD_TAG => 'Add Tag',
                Workflow::ACTION_CREATE_DEAL => 'Create Deal',
            ],
            'salesUsers' => Auth::user()->account->users()
                ->whereIn('role', [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SALE])
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
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'trigger' => ['required', 'array'],
            'trigger.event' => ['required', 'string'],
            'trigger.conditions' => ['nullable', 'array'],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*.type' => ['required', 'string'],
            'is_active' => ['boolean'],
        ]);

        Auth::user()->account->workflows()->create($validated);

        return Redirect::route('workflows')->with('success', 'Workflow created.');
    }

    public function edit(Workflow $workflow): Response
    {
        return Inertia::render('Workflows/Edit', [
            'workflow' => [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'description' => $workflow->description,
                'trigger' => $workflow->trigger,
                'actions' => $workflow->actions,
                'is_active' => $workflow->is_active,
            ],
            'triggerEvents' => [
                Workflow::TRIGGER_LEAD_CREATED => 'Lead Created',
                Workflow::TRIGGER_LEAD_SCORED => 'Lead Scored',
                Workflow::TRIGGER_LEAD_STATUS_CHANGED => 'Lead Status Changed',
                Workflow::TRIGGER_DEAL_CREATED => 'Deal Created',
                Workflow::TRIGGER_DEAL_STAGE_CHANGED => 'Deal Stage Changed',
                Workflow::TRIGGER_ACTIVITY_CREATED => 'Activity Created',
            ],
            'actionTypes' => [
                Workflow::ACTION_ASSIGN_USER => 'Assign User',
                Workflow::ACTION_SEND_EMAIL => 'Send Email',
                Workflow::ACTION_CREATE_TASK => 'Create Task',
                Workflow::ACTION_UPDATE_FIELD => 'Update Field',
                Workflow::ACTION_ADD_TAG => 'Add Tag',
                Workflow::ACTION_CREATE_DEAL => 'Create Deal',
            ],
            'salesUsers' => Auth::user()->account->users()
                ->whereIn('role', [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SALE])
                ->orderByName()
                ->get()
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                ]),
        ]);
    }

    public function update(Workflow $workflow): RedirectResponse
    {
        $validated = Request::validate([
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'trigger' => ['required', 'array'],
            'trigger.event' => ['required', 'string'],
            'trigger.conditions' => ['nullable', 'array'],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*.type' => ['required', 'string'],
            'is_active' => ['boolean'],
        ]);

        $workflow->update($validated);

        return Redirect::back()->with('success', 'Workflow updated.');
    }

    public function destroy(Workflow $workflow): RedirectResponse
    {
        $workflow->delete();

        return Redirect::back()->with('success', 'Workflow deleted.');
    }
}
