<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SupportTicket;
use App\Models\UpsellOpportunity;
use App\Models\User;
use App\Services\CustomerHealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class CustomerSuccessController extends Controller
{
    public function __construct(
        private CustomerHealthService $healthService
    ) {}

    /**
     * Customer Success Dashboard / Index
     */
    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;

        $customers = Auth::user()->account->customers()
            ->with(['assignedUser:id,first_name,last_name', 'organization:id,name'])
            ->filter($request->only('search', 'lifecycle_status', 'assigned_to', 'health_min', 'health_max', 'trashed'))
            ->orderBy('health_score', 'asc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'lifecycle_status' => $c->lifecycle_status,
                'health_score' => $c->health_score,
                'mrr' => (float) $c->mrr,
                'arr' => (float) $c->arr,
                'contract_end' => $c->contract_end?->format('Y-m-d'),
                'renewal_status' => $c->renewal_status,
                'organization' => $c->organization ? ['id' => $c->organization->id, 'name' => $c->organization->name] : null,
                'assigned_user' => $c->assignedUser ? ['id' => $c->assignedUser->id, 'name' => $c->assignedUser->name] : null,
                'deleted_at' => $c->deleted_at,
            ]);

        $analytics = $this->healthService->getLifecycleAnalytics($accountId);
        $churnRisks = $this->healthService->detectChurnRisk($accountId);

        return Inertia::render('CustomerSuccess/Index', [
            'customers' => $customers,
            'analytics' => $analytics,
            'churnRisks' => array_slice(array_map(fn ($r) => [
                'id' => $r['customer']->id,
                'name' => $r['customer']->name,
                'health_score' => $r['customer']->health_score,
                'risk_level' => $r['risk_level'],
                'risk_factors' => $r['risk_factors'],
            ], $churnRisks), 0, 10),
            'filters' => $request->only('search', 'lifecycle_status', 'assigned_to'),
            'lifecycleStatuses' => Customer::getLifecycleStatuses(),
            'salesUsers' => $this->getSalesUsers(),
        ]);
    }

    /**
     * Create customer form
     */
    public function create(): Response
    {
        return Inertia::render('CustomerSuccess/Create', [
            'lifecycleStatuses' => Customer::getLifecycleStatuses(),
            'renewalStatuses' => Customer::getRenewalStatuses(),
            'organizations' => Auth::user()->account->organizations()->orderBy('name')->get(['id', 'name']),
            'contacts' => Auth::user()->account->contacts()->orderBy('first_name')->get()->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
            'salesUsers' => $this->getSalesUsers(),
        ]);
    }

    /**
     * Store new customer
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:30',
            'organization_id' => 'nullable|integer|exists:organizations,id',
            'contact_id' => 'nullable|integer|exists:contacts,id',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'lifecycle_status' => 'required|string|max:30',
            'start_date' => 'nullable|date',
            'mrr' => 'nullable|numeric|min:0',
            'arr' => 'nullable|numeric|min:0',
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date|after_or_equal:contract_start',
            'contract_term' => 'nullable|string|max:20',
            'auto_renew' => 'boolean',
            'notes' => 'nullable|string|max:2000',
        ]);

        $customer = Auth::user()->account->customers()->create($validated);
        $this->healthService->calculateHealthScore($customer, 'created');

        return Redirect::route('customers.edit', $customer)->with('success', 'Customer created.');
    }

    /**
     * Edit customer
     */
    public function edit(Customer $customer): Response
    {
        return Inertia::render('CustomerSuccess/Edit', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'organization_id' => $customer->organization_id,
                'contact_id' => $customer->contact_id,
                'assigned_to' => $customer->assigned_to,
                'lifecycle_status' => $customer->lifecycle_status,
                'start_date' => $customer->start_date?->format('Y-m-d'),
                'mrr' => (float) $customer->mrr,
                'arr' => (float) $customer->arr,
                'health_score' => $customer->health_score,
                'health_factors' => $customer->health_factors,
                'contract_start' => $customer->contract_start?->format('Y-m-d'),
                'contract_end' => $customer->contract_end?->format('Y-m-d'),
                'contract_term' => $customer->contract_term,
                'auto_renew' => $customer->auto_renew,
                'renewal_status' => $customer->renewal_status,
                'notes' => $customer->notes,
                'deleted_at' => $customer->deleted_at,
            ],
            'tickets' => $customer->tickets()
                ->with('assignedUser:id,first_name,last_name')
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'subject' => $t->subject,
                    'priority' => $t->priority,
                    'status' => $t->status,
                    'category' => $t->category,
                    'created_at' => $t->created_at->toISOString(),
                    'resolved_at' => $t->resolved_at?->toISOString(),
                    'assigned_user' => $t->assignedUser ? ['id' => $t->assignedUser->id, 'name' => $t->assignedUser->name] : null,
                ]),
            'upsells' => $customer->upsellOpportunities()
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'title' => $u->title,
                    'value' => (float) $u->value,
                    'status' => $u->status,
                    'type' => $u->type,
                    'target_close_date' => $u->target_close_date?->format('Y-m-d'),
                ]),
            'healthHistory' => $customer->healthLogs()
                ->orderBy('created_at', 'desc')
                ->take(30)
                ->get()
                ->map(fn ($l) => [
                    'score' => $l->score,
                    'factors' => $l->factors,
                    'date' => $l->created_at->format('M d'),
                ]),
            'lifecycleStatuses' => Customer::getLifecycleStatuses(),
            'renewalStatuses' => Customer::getRenewalStatuses(),
            'ticketStatuses' => SupportTicket::getStatuses(),
            'ticketPriorities' => SupportTicket::getPriorities(),
            'ticketCategories' => SupportTicket::getCategories(),
            'upsellStatuses' => UpsellOpportunity::getStatuses(),
            'upsellTypes' => UpsellOpportunity::getTypes(),
            'organizations' => Auth::user()->account->organizations()->orderBy('name')->get(['id', 'name']),
            'contacts' => Auth::user()->account->contacts()->orderBy('first_name')->get()->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
            'salesUsers' => $this->getSalesUsers(),
        ]);
    }

    /**
     * Update customer
     */
    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:30',
            'organization_id' => 'nullable|integer',
            'contact_id' => 'nullable|integer',
            'assigned_to' => 'nullable|integer',
            'lifecycle_status' => 'required|string|max:30',
            'start_date' => 'nullable|date',
            'mrr' => 'nullable|numeric|min:0',
            'arr' => 'nullable|numeric|min:0',
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date',
            'contract_term' => 'nullable|string|max:20',
            'auto_renew' => 'boolean',
            'renewal_status' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:2000',
        ]);

        $customer->update($validated);

        return Redirect::back()->with('success', 'Customer updated.');
    }

    /**
     * Recalculate health score
     */
    public function recalculateHealth(Customer $customer): RedirectResponse
    {
        $this->healthService->calculateHealthScore($customer, 'manual');
        return Redirect::back()->with('success', 'Health score recalculated.');
    }

    /**
     * Add support ticket
     */
    public function storeTicket(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:200',
            'description' => 'nullable|string|max:5000',
            'priority' => 'required|string|max:20',
            'category' => 'nullable|string|max:50',
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        $customer->tickets()->create(array_merge($validated, [
            'account_id' => Auth::user()->account_id,
            'created_by' => Auth::id(),
            'status' => SupportTicket::STATUS_OPEN,
        ]));

        // Recalculate health
        $this->healthService->calculateHealthScore($customer, 'ticket_created');

        return Redirect::back()->with('success', 'Ticket created.');
    }

    /**
     * Update ticket status
     */
    public function updateTicket(Request $request, Customer $customer, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|max:20',
            'assigned_to' => 'nullable|integer',
        ]);

        if ($validated['status'] === SupportTicket::STATUS_RESOLVED && !$ticket->resolved_at) {
            $validated['resolved_at'] = now();
        }

        if (!$ticket->first_response_at && in_array($validated['status'], [SupportTicket::STATUS_IN_PROGRESS, SupportTicket::STATUS_RESOLVED])) {
            $validated['first_response_at'] = now();
        }

        $ticket->update($validated);
        $this->healthService->calculateHealthScore($customer, 'ticket_updated');

        return Redirect::back()->with('success', 'Ticket updated.');
    }

    /**
     * Add upsell opportunity
     */
    public function storeUpsell(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'value' => 'nullable|numeric|min:0',
            'type' => 'required|string|max:20',
            'target_close_date' => 'nullable|date',
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        $customer->upsellOpportunities()->create(array_merge($validated, [
            'account_id' => Auth::user()->account_id,
            'status' => UpsellOpportunity::STATUS_IDENTIFIED,
        ]));

        return Redirect::back()->with('success', 'Upsell opportunity created.');
    }

    /**
     * Delete customer
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();
        return Redirect::route('customers')->with('success', 'Customer deleted.');
    }

    /**
     * Restore customer
     */
    public function restore(Customer $customer): RedirectResponse
    {
        $customer->restore();
        return Redirect::back()->with('success', 'Customer restored.');
    }

    private function getSalesUsers()
    {
        return Auth::user()->account->users()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SALE])
            ->orderByName()
            ->get()
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);
    }
}
