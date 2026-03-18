<?php

namespace App\Http\Controllers;

use App\Events\ActivityLogged;
use App\Http\Requests\Activity\StoreActivityRequest;
use App\Http\Requests\Activity\UpdateActivityRequest;
use App\Models\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class ActivitiesController extends Controller
{
    public function store(StoreActivityRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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

        // Dispatch event — listeners handle SLA first response recording, etc.
        ActivityLogged::dispatch($activity);

        return Redirect::back()->with('success', 'Activity logged successfully.');
    }

    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse
    {
        $activity->update($request->validated());

        return Redirect::back()->with('success', 'Activity updated.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $activity->delete();

        return Redirect::back()->with('success', 'Activity deleted.');
    }
}
