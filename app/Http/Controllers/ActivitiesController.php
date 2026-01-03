<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Lead;
use App\Services\SLATrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivitiesController extends Controller
{
    public function store(): RedirectResponse
    {
        $validated = Request::validate([
            'subject_type' => ['required', 'string', 'in:App\\Models\\Lead,App\\Models\\Deal,App\\Models\\Contact'],
            'subject_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'in:call,email,meeting,note'],
            'title' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'date' => ['required', 'date'],
        ]);

        // Verify subject belongs to user's account
        $subjectClass = $validated['subject_type'];
        $subject = $subjectClass::where('account_id', Auth::user()->account_id)
            ->findOrFail($validated['subject_id']);

        $activity = Auth::user()->account->activities()->create([
            'subject_type' => $validated['subject_type'],
            'subject_id' => $validated['subject_id'],
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'date' => $validated['date'],
            'user_id' => Auth::id(),
        ]);

        // Check if this is first response for a lead (record SLA)
        if ($validated['subject_type'] === 'App\\Models\\Lead') {
            $lead = Lead::find($validated['subject_id']);
            if ($lead && !$lead->first_response_at) {
                app(SLATrackingService::class)->recordFirstResponse($lead);
            }
        }

        return Redirect::back()->with('success', 'Activity logged successfully.');
    }

    public function update(Activity $activity): RedirectResponse
    {
        $validated = Request::validate([
            'type' => ['required', 'string', 'in:call,email,meeting,note'],
            'title' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'date' => ['required', 'date'],
        ]);

        $activity->update($validated);

        return Redirect::back()->with('success', 'Activity updated.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $activity->delete();

        return Redirect::back()->with('success', 'Activity deleted.');
    }
}
