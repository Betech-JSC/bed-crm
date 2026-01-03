<?php

namespace App\Http\Controllers;

use App\Models\SLASetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class SLASettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('SLASettings/Index', [
            'slaSettings' => Auth::user()->account->slaSettings()
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($sla) => [
                    'id' => $sla->id,
                    'name' => $sla->name,
                    'description' => $sla->description,
                    'first_response_threshold' => $sla->first_response_threshold,
                    'warning_threshold' => $sla->warning_threshold,
                    'is_active' => $sla->is_active,
                    'is_default' => $sla->is_default,
                    'pending_leads_count' => $sla->pendingLeads()->count(),
                    'breached_leads_count' => $sla->breachedLeads()->count(),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('SLASettings/Create');
    }

    public function store(): RedirectResponse
    {
        $validated = Request::validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'first_response_threshold' => ['required', 'integer', 'min:1', 'max:1440'], // Max 24 hours
            'warning_threshold' => ['required', 'integer', 'min:1', 'max:1440'],
            'critical_threshold' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'notify_assigned_user' => ['boolean'],
            'notify_managers' => ['boolean'],
            'notify_user_ids' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ]);

        $slaSetting = Auth::user()->account->slaSettings()->create($validated);

        // If set as default, unset others
        if ($validated['is_default'] ?? false) {
            $slaSetting->setAsDefault();
        }

        return Redirect::route('sla-settings')->with('success', 'SLA setting created.');
    }

    public function edit(SLASetting $slaSetting): Response
    {
        return Inertia::render('SLASettings/Edit', [
            'slaSetting' => [
                'id' => $slaSetting->id,
                'name' => $slaSetting->name,
                'description' => $slaSetting->description,
                'first_response_threshold' => $slaSetting->first_response_threshold,
                'warning_threshold' => $slaSetting->warning_threshold,
                'critical_threshold' => $slaSetting->critical_threshold,
                'notify_assigned_user' => $slaSetting->notify_assigned_user,
                'notify_managers' => $slaSetting->notify_managers,
                'notify_user_ids' => $slaSetting->notify_user_ids ?? [],
                'is_active' => $slaSetting->is_active,
                'is_default' => $slaSetting->is_default,
            ],
        ]);
    }

    public function update(SLASetting $slaSetting): RedirectResponse
    {
        $validated = Request::validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'first_response_threshold' => ['required', 'integer', 'min:1', 'max:1440'],
            'warning_threshold' => ['required', 'integer', 'min:1', 'max:1440'],
            'critical_threshold' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'notify_assigned_user' => ['boolean'],
            'notify_managers' => ['boolean'],
            'notify_user_ids' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ]);

        $slaSetting->update($validated);

        // If set as default, unset others
        if ($validated['is_default'] ?? false) {
            $slaSetting->setAsDefault();
        }

        return Redirect::back()->with('success', 'SLA setting updated.');
    }

    public function destroy(SLASetting $slaSetting): RedirectResponse
    {
        $slaSetting->delete();

        return Redirect::back()->with('success', 'SLA setting deleted.');
    }
}
