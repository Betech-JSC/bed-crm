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
        return Inertia::render('EmailTemplates/Index', [
            'templates' => Auth::user()->account->emailTemplates()
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($template) => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'subject' => $template->subject,
                    'type' => $template->type,
                    'is_active' => $template->is_active,
                    'created_at' => $template->created_at->format('Y-m-d H:i'),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('EmailTemplates/Create', [
            'types' => ['campaign', 'automation', 'transactional'],
            'variables' => ['name', 'email', 'first_name', 'unsubscribe_url'],
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

        return Redirect::route('email-templates.index')->with('success', 'Email template created.');
    }

    public function edit(EmailTemplate $emailTemplate): Response
    {
        // Ensure template belongs to user's account
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
            'variables' => ['name', 'email', 'first_name', 'unsubscribe_url'],
        ]);
    }

    public function update(EmailTemplate $emailTemplate): RedirectResponse
    {
        // Ensure template belongs to user's account
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

        return Redirect::route('email-templates.index')->with('success', 'Email template updated.');
    }

    public function destroy(EmailTemplate $emailTemplate): RedirectResponse
    {
        // Ensure template belongs to user's account
        if ($emailTemplate->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $emailTemplate->delete();

        return Redirect::route('email-templates.index')->with('success', 'Email template deleted.');
    }
}
