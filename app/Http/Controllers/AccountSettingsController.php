<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AccountSettingsController extends Controller
{

    public function index(): InertiaResponse
    {
        $account = Auth::user()->account;

        return Inertia::render('AccountSettings/Index', [
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'logo' => $account->logo ? Storage::url($account->logo) : null,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'], // 2MB max
        ]);

        $account = Auth::user()->account;

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($account->logo && Storage::exists($account->logo)) {
                Storage::delete($account->logo);
            }

            // Upload new logo
            $file = $request->file('logo');
            $path = $file->store('accounts/logos', 'public');
            $validated['logo'] = $path;
        } else {
            unset($validated['logo']);
        }

        $account->update($validated);

        return redirect()->route('account-settings.index')->with('success', 'Account settings updated successfully.');
    }
}
