<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailTemplatesController extends Controller
{
    public function index(): Response
    {
        $accountId = Auth::user()->account_id;
        $query = EmailTemplate::where('account_id', $accountId);

        // Stats (before filtering)
        $stats = [
            'total' => (clone $query)->count(),
            'campaign' => (clone $query)->where('type', 'campaign')->count(),
            'automation' => (clone $query)->where('type', 'automation')->count(),
            'transactional' => (clone $query)->where('type', 'transactional')->count(),
        ];

        // Apply filters
        $templates = $query
            ->when(Request::input('search'), function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->when(Request::input('type'), fn ($q, $type) => $q->where('type', $type))
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (EmailTemplate $tpl) => [
                'id' => $tpl->id,
                'name' => $tpl->name,
                'subject' => $tpl->subject,
                'type' => $tpl->type,
                'is_active' => $tpl->is_active,
                'created_at' => $tpl->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('EmailTemplates/Index', [
            'templates' => $templates,
            'stats' => $stats,
            'filters' => Request::only('search', 'type'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('EmailTemplates/Create', [
            'types' => ['campaign', 'automation', 'transactional'],
            'variables' => ['name', 'email', 'first_name', 'unsubscribe_url', 'company'],
        ]);
    }

    public function store(): RedirectResponse
    {
        $validated = Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:500'],
            'body_html' => ['nullable', 'string'],
            'body_text' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:campaign,automation,transactional'],
            'variables' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        $validated['account_id'] = Auth::user()->account_id;
        $validated['created_by'] = Auth::id();

        EmailTemplate::create($validated);

        return Redirect::route('email-templates.index')->with('success', 'Template email đã tạo thành công.');
    }

    public function edit(EmailTemplate $emailTemplate): Response
    {
        if ($emailTemplate->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        return Inertia::render('EmailTemplates/Edit', [
            'template' => [
                'id' => $emailTemplate->id,
                'name' => $emailTemplate->name,
                'subject' => $emailTemplate->subject,
                'body_html' => $emailTemplate->body_html,
                'body_text' => $emailTemplate->body_text,
                'type' => $emailTemplate->type,
                'variables' => $emailTemplate->variables,
                'is_active' => $emailTemplate->is_active,
            ],
            'types' => ['campaign', 'automation', 'transactional'],
            'variables' => ['name', 'email', 'first_name', 'unsubscribe_url', 'company'],
        ]);
    }

    public function update(EmailTemplate $emailTemplate): RedirectResponse
    {
        if ($emailTemplate->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $validated = Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:500'],
            'body_html' => ['nullable', 'string'],
            'body_text' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:campaign,automation,transactional'],
            'variables' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        $emailTemplate->update($validated);

        return Redirect::route('email-templates.index')->with('success', 'Template email đã cập nhật.');
    }

    public function destroy(EmailTemplate $emailTemplate): RedirectResponse
    {
        if ($emailTemplate->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $emailTemplate->delete();

        return Redirect::route('email-templates.index')->with('success', 'Template email đã xóa.');
    }
}
