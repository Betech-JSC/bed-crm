<?php

namespace App\Http\Controllers;

use App\Models\EmailAutomation;
use App\Services\AutomationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailAutomationsController extends Controller
{
    public function __construct(
        private AutomationService $automationService
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('EmailAutomations/Index', [
            'automations' => Auth::user()->account->emailAutomations()
                ->withCount(['steps'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($automation) => [
                    'id' => $automation->id,
                    'name' => $automation->name,
                    'trigger_type' => $automation->trigger_type,
                    'status' => $automation->status,
                    'steps_count' => $automation->steps_count,
                    'contacts_processed' => $automation->contacts_processed,
                    'emails_sent' => $automation->emails_sent,
                    'created_at' => $automation->created_at->format('Y-m-d H:i'),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('EmailAutomations/Create', [
            'triggerTypes' => [
                EmailAutomation::TRIGGER_LEAD_CREATED,
                EmailAutomation::TRIGGER_CONTACT_CREATED,
                EmailAutomation::TRIGGER_DEAL_WON,
                EmailAutomation::TRIGGER_TAG_ADDED,
            ],
        ]);
    }

    public function store(): RedirectResponse
    {
        $validated = Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'trigger_type' => ['required', 'string'],
            'trigger_config' => ['nullable', 'array'],
        ]);

        $validated['account_id'] = Auth::user()->account_id;
        $validated['created_by'] = Auth::id();
        $validated['status'] = EmailAutomation::STATUS_DRAFT;

        $automation = EmailAutomation::create($validated);

        return Redirect::route('email-automations.show', $automation)->with('success', 'Email automation created.');
    }

    public function show(EmailAutomation $emailAutomation): Response
    {
        // Ensure automation belongs to user's account
        if ($emailAutomation->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        return Inertia::render('EmailAutomations/Show', [
            'automation' => [
                'id' => $emailAutomation->id,
                'name' => $emailAutomation->name,
                'description' => $emailAutomation->description,
                'trigger_type' => $emailAutomation->trigger_type,
                'trigger_config' => $emailAutomation->trigger_config,
                'status' => $emailAutomation->status,
                'contacts_processed' => $emailAutomation->contacts_processed,
                'emails_sent' => $emailAutomation->emails_sent,
            ],
            'steps' => $emailAutomation->steps()
                ->with('emailTemplate')
                ->orderBy('step_order')
                ->get()
                ->map(fn ($step) => [
                    'id' => $step->id,
                    'step_order' => $step->step_order,
                    'step_type' => $step->step_type,
                    'step_config' => $step->step_config,
                    'email_template_id' => $step->email_template_id,
                    'email_template_name' => $step->emailTemplate?->name,
                    'wait_days' => $step->wait_days,
                    'conditions' => $step->conditions,
                    'is_active' => $step->is_active,
                ]),
            'templates' => Auth::user()->account->emailTemplates()
                ->where('is_active', true)
                ->get()
                ->map(fn ($template) => [
                    'id' => $template->id,
                    'name' => $template->name,
                ]),
        ]);
    }

    public function edit(EmailAutomation $emailAutomation): Response
    {
        // Ensure automation belongs to user's account
        if ($emailAutomation->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        return Inertia::render('EmailAutomations/Edit', [
            'automation' => [
                'id' => $emailAutomation->id,
                'name' => $emailAutomation->name,
                'description' => $emailAutomation->description,
                'trigger_type' => $emailAutomation->trigger_type,
                'trigger_config' => $emailAutomation->trigger_config,
                'status' => $emailAutomation->status,
            ],
            'triggerTypes' => [
                EmailAutomation::TRIGGER_LEAD_CREATED,
                EmailAutomation::TRIGGER_CONTACT_CREATED,
                EmailAutomation::TRIGGER_DEAL_WON,
                EmailAutomation::TRIGGER_TAG_ADDED,
            ],
        ]);
    }

    public function update(EmailAutomation $emailAutomation): RedirectResponse
    {
        // Ensure automation belongs to user's account
        if ($emailAutomation->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $validated = Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'trigger_type' => ['required', 'string'],
            'trigger_config' => ['nullable', 'array'],
        ]);

        $emailAutomation->update($validated);

        return Redirect::route('email-automations.show', $emailAutomation)->with('success', 'Email automation updated.');
    }

    public function activate(EmailAutomation $emailAutomation): RedirectResponse
    {
        // Ensure automation belongs to user's account
        if ($emailAutomation->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $emailAutomation->status = EmailAutomation::STATUS_ACTIVE;
        $emailAutomation->save();

        return Redirect::back()->with('success', 'Automation activated.');
    }

    public function pause(EmailAutomation $emailAutomation): RedirectResponse
    {
        // Ensure automation belongs to user's account
        if ($emailAutomation->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $emailAutomation->status = EmailAutomation::STATUS_PAUSED;
        $emailAutomation->save();

        return Redirect::back()->with('success', 'Automation paused.');
    }

    public function destroy(EmailAutomation $emailAutomation): RedirectResponse
    {
        // Ensure automation belongs to user's account
        if ($emailAutomation->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $emailAutomation->delete();

        return Redirect::route('email-automations.index')->with('success', 'Email automation deleted.');
    }
}
