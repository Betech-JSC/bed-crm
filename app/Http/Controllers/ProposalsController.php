<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Proposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProposalsController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Auth::user()->account->proposals()
            ->with(['deal', 'creator', 'sender'])
            ->latestVersions()
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by deal
        if ($request->has('deal_id') && $request->deal_id) {
            $query->where('deal_id', $request->deal_id);
        }

        return Inertia::render('Proposals/Index', [
            'filters' => $request->only('status', 'deal_id'),
            'proposals' => $query->paginate(15)->withQueryString()->through(fn ($proposal) => [
                'id' => $proposal->id,
                'title' => $proposal->title,
                'version' => $proposal->version,
                'amount' => $proposal->amount,
                'status' => $proposal->status,
                'status_label' => Proposal::getStatuses()[$proposal->status] ?? $proposal->status,
                'status_severity' => $proposal->status_severity,
                'deal' => $proposal->deal ? [
                    'id' => $proposal->deal->id,
                    'title' => $proposal->deal->title,
                ] : null,
                'creator' => $proposal->creator ? [
                    'id' => $proposal->creator->id,
                    'name' => $proposal->creator->name,
                ] : null,
                'sent_at' => $proposal->sent_at?->toISOString(),
                'viewed_at' => $proposal->viewed_at?->toISOString(),
                'accepted_at' => $proposal->accepted_at?->toISOString(),
                'view_count' => $proposal->view_count,
                'created_at' => $proposal->created_at->toISOString(),
            ]),
            'statuses' => Proposal::getStatuses(),
            'deals' => Auth::user()->account->deals()
                ->where('status', Deal::STATUS_OPEN)
                ->orderBy('title')
                ->get()
                ->map(fn ($deal) => [
                    'id' => $deal->id,
                    'title' => $deal->title,
                ]),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Proposals/Create', [
            'deal_id' => $request->get('deal_id'),
            'deals' => Auth::user()->account->deals()
                ->where('status', Deal::STATUS_OPEN)
                ->orderBy('title')
                ->get()
                ->map(fn ($deal) => [
                    'id' => $deal->id,
                    'title' => $deal->title,
                    'value' => $deal->value,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'deal_id' => ['nullable', 'exists:deals,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'valid_until' => ['nullable', 'date', 'after:today'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'], // Max 10MB
        ]);

        // Handle file upload
        $file = $request->file('file');
        $filePath = $file->store('proposals', 'public');
        $fileSize = $file->getSize();

        $proposal = Auth::user()->account->proposals()->create([
            'deal_id' => $validated['deal_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'amount' => $validated['amount'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
            'file_path' => $filePath,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $fileSize,
            'file_type' => $file->getMimeType(),
            'status' => Proposal::STATUS_DRAFT,
            'created_by' => Auth::id(),
            'version' => 1,
        ]);

        return Redirect::route('proposals.show', $proposal)->with('success', 'Proposal created.');
    }

    public function show(Proposal $proposal): Response
    {
        $proposal->load(['deal', 'creator', 'sender', 'parent']);

        return Inertia::render('Proposals/Show', [
            'proposal' => [
                'id' => $proposal->id,
                'deal_id' => $proposal->deal_id,
                'version' => $proposal->version,
                'parent_id' => $proposal->parent_id,
                'title' => $proposal->title,
                'description' => $proposal->description,
                'amount' => $proposal->amount,
                'valid_until' => $proposal->valid_until?->format('Y-m-d'),
                'file_path' => $proposal->file_path,
                'file_name' => $proposal->file_name,
                'file_size' => $proposal->file_size,
                'file_type' => $proposal->file_type,
                'status' => $proposal->status,
                'status_label' => Proposal::getStatuses()[$proposal->status] ?? $proposal->status,
                'status_severity' => $proposal->status_severity,
                'sent_at' => $proposal->sent_at?->toISOString(),
                'viewed_at' => $proposal->viewed_at?->toISOString(),
                'accepted_at' => $proposal->accepted_at?->toISOString(),
                'rejected_at' => $proposal->rejected_at?->toISOString(),
                'rejection_reason' => $proposal->rejection_reason,
                'view_count' => $proposal->view_count,
                'last_viewed_at' => $proposal->last_viewed_at?->toISOString(),
                'can_be_edited' => $proposal->canBeEdited(),
                'can_be_sent' => $proposal->canBeSent(),
                'can_be_accepted' => $proposal->canBeAccepted(),
                'can_be_rejected' => $proposal->canBeRejected(),
                'deal' => $proposal->deal ? [
                    'id' => $proposal->deal->id,
                    'title' => $proposal->deal->title,
                ] : null,
                'creator' => $proposal->creator ? [
                    'id' => $proposal->creator->id,
                    'name' => $proposal->creator->name,
                ] : null,
                'sender' => $proposal->sender ? [
                    'id' => $proposal->sender->id,
                    'name' => $proposal->sender->name,
                ] : null,
                'created_at' => $proposal->created_at->toISOString(),
            ],
            'versions' => $proposal->allVersions()->map(fn ($v) => [
                'id' => $v->id,
                'version' => $v->version,
                'status' => $v->status,
                'created_at' => $v->created_at->toISOString(),
            ]),
        ]);
    }

    public function edit(Proposal $proposal): Response
    {
        if (!$proposal->canBeEdited()) {
            return Redirect::route('proposals.show', $proposal)
                ->with('error', 'This proposal cannot be edited in its current status.');
        }

        return Inertia::render('Proposals/Edit', [
            'proposal' => [
                'id' => $proposal->id,
                'deal_id' => $proposal->deal_id,
                'title' => $proposal->title,
                'description' => $proposal->description,
                'amount' => $proposal->amount,
                'valid_until' => $proposal->valid_until?->format('Y-m-d'),
                'file_name' => $proposal->file_name,
            ],
            'deals' => Auth::user()->account->deals()
                ->where('status', Deal::STATUS_OPEN)
                ->orderBy('title')
                ->get()
                ->map(fn ($deal) => [
                    'id' => $deal->id,
                    'title' => $deal->title,
                ]),
        ]);
    }

    public function update(Request $request, Proposal $proposal): RedirectResponse
    {
        if (!$proposal->canBeEdited()) {
            return Redirect::back()->with('error', 'This proposal cannot be edited.');
        }

        $validated = $request->validate([
            'deal_id' => ['nullable', 'exists:deals,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'valid_until' => ['nullable', 'date', 'after:today'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        $updateData = [
            'deal_id' => $validated['deal_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'amount' => $validated['amount'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
        ];

        // Handle file upload if provided
        if ($request->hasFile('file')) {
            // Delete old file
            if ($proposal->file_path) {
                Storage::disk('public')->delete($proposal->file_path);
            }

            $file = $request->file('file');
            $filePath = $file->store('proposals', 'public');

            $updateData['file_path'] = $filePath;
            $updateData['file_name'] = $file->getClientOriginalName();
            $updateData['file_size'] = $file->getSize();
            $updateData['file_type'] = $file->getMimeType();
        }

        $proposal->update($updateData);

        return Redirect::route('proposals.show', $proposal)->with('success', 'Proposal updated.');
    }

    public function destroy(Proposal $proposal): RedirectResponse
    {
        // Delete file
        if ($proposal->file_path) {
            Storage::disk('public')->delete($proposal->file_path);
        }

        $proposal->delete();

        return Redirect::route('proposals')->with('success', 'Proposal deleted.');
    }

    public function createVersion(Proposal $proposal): RedirectResponse
    {
        $newVersion = $proposal->createVersion();

        return Redirect::route('proposals.edit', $newVersion)
            ->with('success', "New version (v{$newVersion->version}) created.");
    }

    public function send(Proposal $proposal): RedirectResponse
    {
        if (!$proposal->canBeSent()) {
            return Redirect::back()->with('error', 'This proposal cannot be sent in its current status.');
        }

        $proposal->markAsSent();

        // TODO: Send email notification to client
        // TODO: Generate tracking link

        return Redirect::back()->with('success', 'Proposal sent successfully.');
    }

    public function accept(Proposal $proposal): RedirectResponse
    {
        if (!$proposal->canBeAccepted()) {
            return Redirect::back()->with('error', 'This proposal cannot be accepted in its current status.');
        }

        $proposal->markAsAccepted();

        // Update deal if linked
        if ($proposal->deal_id) {
            $proposal->deal->update(['stage' => Deal::STAGE_CLOSING]);
        }

        return Redirect::back()->with('success', 'Proposal accepted.');
    }

    public function reject(Request $request, Proposal $proposal): RedirectResponse
    {
        if (!$proposal->canBeRejected()) {
            return Redirect::back()->with('error', 'This proposal cannot be rejected in its current status.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $proposal->markAsRejected($validated['rejection_reason'] ?? null);

        return Redirect::back()->with('success', 'Proposal rejected.');
    }

    public function download(Proposal $proposal)
    {
        if (!$proposal->file_path || !Storage::disk('public')->exists($proposal->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('public')->download(
            $proposal->file_path,
            $proposal->file_name ?? 'proposal.pdf'
        );
    }

    public function trackView(Proposal $proposal): \Illuminate\Http\JsonResponse
    {
        $proposal->markAsViewed();

        return response()->json([
            'success' => true,
            'view_count' => $proposal->view_count,
            'viewed_at' => $proposal->viewed_at?->toISOString(),
        ]);
    }
}
