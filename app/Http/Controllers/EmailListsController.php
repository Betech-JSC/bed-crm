<?php

namespace App\Http\Controllers;

use App\Models\EmailList;
use App\Models\EmailListContact;
use App\Models\Contact;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailListsController extends Controller
{
    public function index(): Response
    {
        $accountId = Auth::user()->account_id;
        $baseQuery = EmailList::where('account_id', $accountId);

        // Stats
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'total_contacts' => (clone $baseQuery)->sum('contacts_count'),
            'manual' => (clone $baseQuery)->where('type', 'manual')->count(),
            'dynamic' => (clone $baseQuery)->where('type', 'dynamic')->count(),
        ];

        $lists = $baseQuery
            ->when(Request::input('search'), fn ($q, $search) =>
                $q->where('name', 'like', "%{$search}%")
            )
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (EmailList $list) => [
                'id' => $list->id,
                'name' => $list->name,
                'description' => $list->description,
                'type' => $list->type,
                'contacts_count' => $list->contacts_count,
                'is_active' => $list->is_active,
                'created_at' => $list->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('EmailLists/Index', [
            'lists' => $lists,
            'stats' => $stats,
            'filters' => Request::only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('EmailLists/Create', [
            'types' => ['manual', 'dynamic'],
        ]);
    }

    public function store(): RedirectResponse
    {
        $validated = Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:manual,dynamic'],
            'filters' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        $validated['account_id'] = Auth::user()->account_id;
        $validated['created_by'] = Auth::id();
        $validated['contacts_count'] = 0;

        $list = EmailList::create($validated);

        return Redirect::route('email-lists.show', $list)->with('success', 'Danh sách đã tạo thành công.');
    }

    public function show(EmailList $emailList): Response
    {
        if ($emailList->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $contacts = $emailList->listContacts()
            ->whereNull('unsubscribed_at')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($listContact) => [
                'id' => $listContact->id,
                'email' => $listContact->email,
                'name' => $listContact->name,
                'contact_type' => $listContact->contact_type,
                'contact_id' => $listContact->contact_id,
                'subscribed_at' => $listContact->subscribed_at?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('EmailLists/Show', [
            'list' => [
                'id' => $emailList->id,
                'name' => $emailList->name,
                'description' => $emailList->description,
                'type' => $emailList->type,
                'filters' => $emailList->filters,
                'contacts_count' => $emailList->contacts_count,
                'is_active' => $emailList->is_active,
            ],
            'contacts' => $contacts,
            'availableContacts' => Auth::user()->account->contacts()
                ->whereNotNull('email')
                ->orderBy('first_name')
                ->limit(200)
                ->get()
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'email' => $c->email]),
            'availableLeads' => Auth::user()->account->leads()
                ->whereNotNull('email')
                ->orderBy('name')
                ->limit(200)
                ->get()
                ->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'email' => $l->email]),
        ]);
    }

    public function edit(EmailList $emailList): Response
    {
        if ($emailList->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        return Inertia::render('EmailLists/Edit', [
            'list' => [
                'id' => $emailList->id,
                'name' => $emailList->name,
                'description' => $emailList->description,
                'type' => $emailList->type,
                'filters' => $emailList->filters,
                'is_active' => $emailList->is_active,
            ],
            'types' => ['manual', 'dynamic'],
        ]);
    }

    public function update(EmailList $emailList): RedirectResponse
    {
        if ($emailList->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $validated = Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:manual,dynamic'],
            'filters' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        $emailList->update($validated);

        if ($emailList->type === 'dynamic') {
            $this->updateDynamicList($emailList);
        }

        return Redirect::route('email-lists.show', $emailList)->with('success', 'Danh sách đã cập nhật.');
    }

    public function destroy(EmailList $emailList): RedirectResponse
    {
        if ($emailList->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $emailList->delete();

        return Redirect::route('email-lists.index')->with('success', 'Danh sách đã xóa.');
    }

    public function addContact(EmailList $emailList): RedirectResponse
    {
        if ($emailList->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $validated = Request::validate([
            'contact_type' => ['required', 'string', 'in:contact,lead'],
            'contact_id' => ['required', 'integer'],
        ]);

        $contact = $validated['contact_type'] === 'contact'
            ? Contact::find($validated['contact_id'])
            : Lead::find($validated['contact_id']);

        if (!$contact || $contact->account_id !== Auth::user()->account_id || !$contact->email) {
            return Redirect::back()->with('error', 'Không tìm thấy liên hệ hoặc thiếu email.');
        }

        $existing = EmailListContact::where('email_list_id', $emailList->id)
            ->where('contact_type', $validated['contact_type'])
            ->where('contact_id', $validated['contact_id'])
            ->first();

        if ($existing) {
            if ($existing->unsubscribed_at) {
                $existing->update([
                    'unsubscribed_at' => null,
                    'unsubscribe_reason' => null,
                    'subscribed_at' => now(),
                ]);
            }
        } else {
            EmailListContact::create([
                'email_list_id' => $emailList->id,
                'contact_type' => $validated['contact_type'],
                'contact_id' => $validated['contact_id'],
                'email' => $contact->email,
                'name' => $contact->name ?? ($contact->first_name . ' ' . ($contact->last_name ?? '')),
                'subscribed_at' => now(),
            ]);
        }

        $emailList->updateContactsCount();

        return Redirect::back()->with('success', 'Đã thêm liên hệ vào danh sách.');
    }

    public function removeContact(EmailList $emailList, EmailListContact $emailListContact): RedirectResponse
    {
        if ($emailList->account_id !== Auth::user()->account_id ||
            $emailListContact->email_list_id !== $emailList->id) {
            abort(403);
        }

        $emailListContact->delete();
        $emailList->updateContactsCount();

        return Redirect::back()->with('success', 'Đã xóa liên hệ khỏi danh sách.');
    }

    private function updateDynamicList(EmailList $list): void
    {
        $list->listContacts()->delete();
        $filters = $list->filters ?? [];

        if (!empty($filters['include_contacts'])) {
            $contacts = Auth::user()->account->contacts()
                ->whereNotNull('email')
                ->get();

            foreach ($contacts as $contact) {
                EmailListContact::create([
                    'email_list_id' => $list->id,
                    'contact_type' => 'contact',
                    'contact_id' => $contact->id,
                    'email' => $contact->email,
                    'name' => $contact->name,
                    'subscribed_at' => now(),
                ]);
            }
        }

        if (!empty($filters['include_leads'])) {
            $leads = Auth::user()->account->leads()
                ->whereNotNull('email')
                ->get();

            foreach ($leads as $lead) {
                EmailListContact::create([
                    'email_list_id' => $list->id,
                    'contact_type' => 'lead',
                    'contact_id' => $lead->id,
                    'email' => $lead->email,
                    'name' => $lead->name,
                    'subscribed_at' => now(),
                ]);
            }
        }

        $list->updateContactsCount();
    }
}
