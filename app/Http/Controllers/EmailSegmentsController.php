<?php

namespace App\Http\Controllers;

use App\Models\EmailSegment;
use App\Models\EmailSegmentContact;
use App\Services\EmailSegmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailSegmentsController extends Controller
{
    public function __construct(
        private EmailSegmentService $segmentService
    ) {}

    public function index(): Response
    {
        return Inertia::render('EmailSegments/Index', [
            'segments' => Auth::user()->account->emailSegments()
                ->withCount('activeContacts')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($seg) => [
                    'id' => $seg->id,
                    'name' => $seg->name,
                    'type' => $seg->type,
                    'contacts_count' => $seg->contacts_count,
                    'active_contacts_count' => $seg->active_contacts_count,
                    'is_active' => $seg->is_active,
                    'last_computed_at' => $seg->last_computed_at?->diffForHumans(),
                    'created_at' => $seg->created_at->format('Y-m-d'),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('EmailSegments/Create', [
            'ruleFields' => $this->getAvailableRuleFields(),
        ]);
    }

    public function store(): RedirectResponse
    {
        $validated = Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:static,dynamic'],
            'rules' => ['nullable', 'array'],
            'rules.match' => ['nullable', 'in:all,any'],
            'rules.conditions' => ['nullable', 'array'],
        ]);

        $validated['account_id'] = Auth::user()->account_id;
        $validated['created_by'] = Auth::id();

        $segment = EmailSegment::create($validated);

        // Auto-compute if dynamic
        if ($segment->type === 'dynamic' && $segment->rules) {
            $segment->recompute();
        }

        return Redirect::route('email-segments.show', $segment)->with('success', 'Segment created successfully.');
    }

    public function show(EmailSegment $emailSegment): Response
    {
        $this->authorize($emailSegment);

        return Inertia::render('EmailSegments/Show', [
            'segment' => [
                'id' => $emailSegment->id,
                'name' => $emailSegment->name,
                'description' => $emailSegment->description,
                'type' => $emailSegment->type,
                'rules' => $emailSegment->rules,
                'contacts_count' => $emailSegment->contacts_count,
                'is_active' => $emailSegment->is_active,
                'last_computed_at' => $emailSegment->last_computed_at?->format('Y-m-d H:i'),
                'created_at' => $emailSegment->created_at->format('Y-m-d H:i'),
            ],
            'contacts' => $emailSegment->segmentContacts()
                ->whereNull('unsubscribed_at')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'email' => $c->email,
                    'contact_type' => $c->contact_type,
                    'source' => $c->source,
                    'tags' => $c->tags,
                    'subscribed_at' => $c->subscribed_at?->format('Y-m-d'),
                ]),
        ]);
    }

    public function edit(EmailSegment $emailSegment): Response
    {
        $this->authorize($emailSegment);

        return Inertia::render('EmailSegments/Edit', [
            'segment' => $emailSegment->only(['id', 'name', 'description', 'type', 'rules', 'is_active']),
            'ruleFields' => $this->getAvailableRuleFields(),
        ]);
    }

    public function update(EmailSegment $emailSegment): RedirectResponse
    {
        $this->authorize($emailSegment);

        $validated = Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:static,dynamic'],
            'rules' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        $emailSegment->update($validated);

        if ($emailSegment->type === 'dynamic') {
            $emailSegment->recompute();
        }

        return Redirect::route('email-segments.show', $emailSegment)->with('success', 'Segment updated.');
    }

    public function destroy(EmailSegment $emailSegment): RedirectResponse
    {
        $this->authorize($emailSegment);
        $emailSegment->delete();
        return Redirect::route('email-segments.index')->with('success', 'Segment deleted.');
    }

    public function recompute(EmailSegment $emailSegment): RedirectResponse
    {
        $this->authorize($emailSegment);
        $count = $emailSegment->recompute();
        return Redirect::back()->with('success', "Segment recomputed: {$count} contacts matched.");
    }

    public function addContact(EmailSegment $emailSegment): RedirectResponse
    {
        $this->authorize($emailSegment);

        $validated = Request::validate([
            'email' => ['required', 'email'],
            'contact_type' => ['required', 'string'],
            'contact_id' => ['required', 'integer'],
        ]);

        EmailSegmentContact::firstOrCreate(
            [
                'email_segment_id' => $emailSegment->id,
                'contact_type' => $validated['contact_type'],
                'contact_id' => $validated['contact_id'],
            ],
            [
                'email' => $validated['email'],
                'source' => 'manual',
                'subscribed_at' => now(),
            ]
        );

        $emailSegment->increment('contacts_count');

        return Redirect::back()->with('success', 'Contact added to segment.');
    }

    public function removeContact(EmailSegment $emailSegment, EmailSegmentContact $segmentContact): RedirectResponse
    {
        $this->authorize($emailSegment);
        $segmentContact->update(['unsubscribed_at' => now()]);
        $emailSegment->decrement('contacts_count');
        return Redirect::back()->with('success', 'Contact removed from segment.');
    }

    private function authorize(EmailSegment $segment): void
    {
        if ($segment->account_id !== Auth::user()->account_id) {
            abort(403);
        }
    }

    private function getAvailableRuleFields(): array
    {
        return [
            ['value' => 'lead.status', 'label' => 'Lead Status', 'type' => 'select', 'options' => ['new', 'contacted', 'qualified', 'won', 'lost']],
            ['value' => 'lead.source', 'label' => 'Lead Source', 'type' => 'text'],
            ['value' => 'lead.score', 'label' => 'Lead Score', 'type' => 'number'],
            ['value' => 'deal.value', 'label' => 'Deal Value', 'type' => 'number'],
            ['value' => 'tag', 'label' => 'Tag', 'type' => 'text'],
            ['value' => 'engagement_level', 'label' => 'Engagement Level', 'type' => 'select', 'options' => ['hot', 'warm', 'cold', 'inactive']],
            ['value' => 'behavior.last_opened', 'label' => 'Opened Email Within (days)', 'type' => 'number'],
        ];
    }
}
