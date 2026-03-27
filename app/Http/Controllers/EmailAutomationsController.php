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
        $accountId = Auth::user()->account_id;
        $baseQuery = EmailAutomation::where('account_id', $accountId);

        // Stats
        $allAutomations = (clone $baseQuery)->get();
        $stats = [
            'total' => $allAutomations->count(),
            'active' => $allAutomations->where('status', 'active')->count(),
            'total_processed' => $allAutomations->sum('contacts_processed'),
            'total_emails' => $allAutomations->sum('emails_sent'),
            'by_status' => $allAutomations->groupBy('status')->map->count(),
        ];

        $automations = $baseQuery
            ->withCount(['steps'])
            ->when(Request::input('search'), fn ($q, $search) =>
                $q->where('name', 'like', "%{$search}%")
            )
            ->when(Request::input('status'), fn ($q, $status) =>
                $q->where('status', $status)
            )
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (EmailAutomation $a) => [
                'id' => $a->id,
                'name' => $a->name,
                'trigger_type' => $a->trigger_type,
                'status' => $a->status,
                'steps_count' => $a->steps_count,
                'contacts_processed' => $a->contacts_processed,
                'emails_sent' => $a->emails_sent,
                'created_at' => $a->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('EmailAutomations/Index', [
            'automations' => $automations,
            'stats' => $stats,
            'filters' => Request::only('search', 'status'),
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

        return Redirect::route('email-automations.show', $automation)->with('success', 'Automation đã tạo thành công.');
    }

    public function show(EmailAutomation $emailAutomation): Response
    {
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
                ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name]),
        ]);
    }

    public function edit(EmailAutomation $emailAutomation): Response
    {
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

        return Redirect::route('email-automations.show', $emailAutomation)->with('success', 'Automation đã cập nhật.');
    }

    public function activate(EmailAutomation $emailAutomation): RedirectResponse
    {
        if ($emailAutomation->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $emailAutomation->status = EmailAutomation::STATUS_ACTIVE;
        $emailAutomation->save();

        return Redirect::back()->with('success', 'Automation đã kích hoạt.');
    }

    public function pause(EmailAutomation $emailAutomation): RedirectResponse
    {
        if ($emailAutomation->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $emailAutomation->status = EmailAutomation::STATUS_PAUSED;
        $emailAutomation->save();

        return Redirect::back()->with('success', 'Automation đã tạm dừng.');
    }

    public function destroy(EmailAutomation $emailAutomation): RedirectResponse
    {
        if ($emailAutomation->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $emailAutomation->delete();

        return Redirect::route('email-automations.index')->with('success', 'Automation đã xóa.');
    }
}
