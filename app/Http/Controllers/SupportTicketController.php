<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class SupportTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;

        $tickets = SupportTicket::where('account_id', $accountId)
            ->with(['customer:id,name', 'assignedUser:id,first_name,last_name', 'createdByUser:id,first_name,last_name'])
            ->when($request->search, fn ($q, $s) => $q->where('subject', 'like', "%{$s}%"))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->priority, fn ($q, $p) => $q->where('priority', $p))
            ->when($request->category, fn ($q, $c) => $q->where('category', $c))
            ->when($request->assigned_to, fn ($q, $a) => $q->where('assigned_to', $a))
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($t) => [
                'id' => $t->id,
                'subject' => $t->subject,
                'description' => $t->description,
                'priority' => $t->priority,
                'status' => $t->status,
                'category' => $t->category,
                'customer' => $t->customer ? ['id' => $t->customer->id, 'name' => $t->customer->name] : null,
                'assigned_user' => $t->assignedUser ? ['id' => $t->assignedUser->id, 'name' => $t->assignedUser->name] : null,
                'created_by_user' => $t->createdByUser ? ['id' => $t->createdByUser->id, 'name' => $t->createdByUser->name] : null,
                'created_at' => $t->created_at->toISOString(),
                'resolved_at' => $t->resolved_at?->toISOString(),
                'first_response_at' => $t->first_response_at?->toISOString(),
            ]);

        // Analytics
        $all = SupportTicket::where('account_id', $accountId);
        $analytics = [
            'total' => $all->count(),
            'open' => (clone $all)->whereIn('status', ['open', 'in_progress'])->count(),
            'resolved_today' => (clone $all)->where('status', 'resolved')->whereDate('resolved_at', today())->count(),
            'urgent' => (clone $all)->where('priority', 'urgent')->whereIn('status', ['open', 'in_progress'])->count(),
            'avg_response_hours' => round((clone $all)->whereNotNull('first_response_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, first_response_at)) as avg_hours')
                ->value('avg_hours') ?? 0, 1),
        ];

        return Inertia::render('SupportTickets/Index', [
            'tickets' => $tickets,
            'analytics' => $analytics,
            'filters' => $request->only('search', 'status', 'priority', 'category', 'assigned_to'),
            'statuses' => SupportTicket::getStatuses(),
            'priorities' => SupportTicket::getPriorities(),
            'categories' => SupportTicket::getCategories(),
            'users' => Auth::user()->account->users()->orderByName()->get()->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('SupportTickets/Create', [
            'statuses' => SupportTicket::getStatuses(),
            'priorities' => SupportTicket::getPriorities(),
            'categories' => SupportTicket::getCategories(),
            'customers' => Auth::user()->account->customers()->orderBy('name')->get(['id', 'name']),
            'users' => Auth::user()->account->users()->orderByName()->get()->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:200',
            'description' => 'nullable|string|max:5000',
            'priority' => 'required|string|max:20',
            'status' => 'string|max:20',
            'category' => 'nullable|string|max:50',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        SupportTicket::create(array_merge($validated, [
            'account_id' => Auth::user()->account_id,
            'created_by' => Auth::id(),
            'status' => $validated['status'] ?? SupportTicket::STATUS_OPEN,
        ]));

        return Redirect::route('support-tickets')->with('success', 'Ticket đã được tạo.');
    }

    public function edit(SupportTicket $supportTicket): Response
    {
        return Inertia::render('SupportTickets/Edit', [
            'ticket' => [
                'id' => $supportTicket->id,
                'subject' => $supportTicket->subject,
                'description' => $supportTicket->description,
                'priority' => $supportTicket->priority,
                'status' => $supportTicket->status,
                'category' => $supportTicket->category,
                'customer_id' => $supportTicket->customer_id,
                'assigned_to' => $supportTicket->assigned_to,
                'created_by' => $supportTicket->created_by,
                'created_at' => $supportTicket->created_at->toISOString(),
                'resolved_at' => $supportTicket->resolved_at?->toISOString(),
                'first_response_at' => $supportTicket->first_response_at?->toISOString(),
                'customer' => $supportTicket->customer ? ['id' => $supportTicket->customer->id, 'name' => $supportTicket->customer->name] : null,
                'assigned_user' => $supportTicket->assignedUser ? ['id' => $supportTicket->assignedUser->id, 'name' => $supportTicket->assignedUser->name] : null,
            ],
            'statuses' => SupportTicket::getStatuses(),
            'priorities' => SupportTicket::getPriorities(),
            'categories' => SupportTicket::getCategories(),
            'customers' => Auth::user()->account->customers()->orderBy('name')->get(['id', 'name']),
            'users' => Auth::user()->account->users()->orderByName()->get()->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]),
        ]);
    }

    public function update(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:200',
            'description' => 'nullable|string|max:5000',
            'priority' => 'required|string|max:20',
            'status' => 'required|string|max:20',
            'category' => 'nullable|string|max:50',
            'customer_id' => 'nullable|integer',
            'assigned_to' => 'nullable|integer',
        ]);

        // Auto-set resolved_at
        if ($validated['status'] === SupportTicket::STATUS_RESOLVED && !$supportTicket->resolved_at) {
            $validated['resolved_at'] = now();
        }
        if (!$supportTicket->first_response_at && in_array($validated['status'], [SupportTicket::STATUS_IN_PROGRESS, SupportTicket::STATUS_RESOLVED])) {
            $validated['first_response_at'] = now();
        }

        $supportTicket->update($validated);

        return Redirect::back()->with('success', 'Ticket đã được cập nhật.');
    }

    public function destroy(SupportTicket $supportTicket): RedirectResponse
    {
        $supportTicket->delete();
        return Redirect::route('support-tickets')->with('success', 'Ticket đã bị xóa.');
    }
}
