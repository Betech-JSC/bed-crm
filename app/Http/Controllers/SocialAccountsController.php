<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Providers\SocialServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class SocialAccountsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('SocialAccounts/Index', [
            'socialAccounts' => Auth::user()->account->socialAccounts()
                ->orderBy('platform')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($account) => [
                    'id' => $account->id,
                    'platform' => $account->platform,
                    'name' => $account->name,
                    'username' => $account->username,
                    'is_active' => $account->is_active,
                    'is_connected' => $account->is_connected,
                    'last_sync_at' => $account->last_sync_at?->format('Y-m-d H:i'),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('SocialAccounts/Create', [
            'platforms' => [
                'linkedin' => 'LinkedIn',
                'twitter' => 'Twitter',
                'facebook' => 'Facebook',
                'instagram' => 'Instagram',
            ],
        ]);
    }

    public function store(): RedirectResponse
    {
        $validated = Request::validate([
            'platform' => ['required', 'string', 'in:linkedin,twitter,facebook,instagram'],
            'code' => ['required', 'string'],
        ]);

        $platformService = SocialServiceProvider::getPlatformService($validated['platform']);

        if (!$platformService) {
            return Redirect::back()->with('error', 'Platform service not available.');
        }

        try {
            $accountData = $platformService->connectAccount([
                'code' => $validated['code'],
            ]);

            SocialAccount::create([
                'account_id' => Auth::user()->account_id,
                'platform' => $validated['platform'],
                'platform_account_id' => $accountData['account_id'],
                'name' => $accountData['metadata']['name'],
                'username' => $accountData['metadata']['username'] ?? null,
                'access_token' => $accountData['access_token'],
                'refresh_token' => $accountData['refresh_token'] ?? null,
                'token_expires_at' => $accountData['expires_at'],
                'platform_metadata' => $accountData['metadata'],
                'is_active' => true,
                'is_connected' => true,
            ]);

            return Redirect::route('social-accounts')->with('success', 'Social account connected successfully.');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'Failed to connect account: ' . $e->getMessage());
        }
    }

    public function destroy(SocialAccount $socialAccount): RedirectResponse
    {
        $socialAccount->delete();

        return Redirect::back()->with('success', 'Social account disconnected.');
    }

    public function refresh(SocialAccount $socialAccount): RedirectResponse
    {
        $platformService = SocialServiceProvider::getPlatformService($socialAccount->platform);

        if (!$platformService) {
            return Redirect::back()->with('error', 'Platform service not available.');
        }

        try {
            $refreshed = $platformService->refreshToken($socialAccount);
            $socialAccount->update([
                'access_token' => $refreshed['access_token'],
                'token_expires_at' => $refreshed['expires_at'],
            ]);

            return Redirect::back()->with('success', 'Token refreshed successfully.');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'Failed to refresh token: ' . $e->getMessage());
        }
    }

    public function validateConnection(SocialAccount $socialAccount): RedirectResponse
    {
        $platformService = SocialServiceProvider::getPlatformService($socialAccount->platform);

        if (!$platformService) {
            return Redirect::back()->with('error', 'Platform service not available.');
        }

        $isValid = $platformService->validateConnection($socialAccount);

        $socialAccount->update([
            'is_connected' => $isValid,
        ]);

        return Redirect::back()->with($isValid ? 'success' : 'error', $isValid ? 'Connection is valid.' : 'Connection is invalid.');
    }
}
