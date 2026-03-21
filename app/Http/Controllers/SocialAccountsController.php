<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Models\SocialPost;
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
        $user = Auth::user();
        $accountId = $user->account_id;

        $socialAccounts = $user->account->socialAccounts()
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
                'is_token_expired' => $account->isTokenExpired(),
                'token_expires_at' => $account->token_expires_at?->format('Y-m-d H:i'),
                'last_sync_at' => $account->last_sync_at?->diffForHumans() ?? null,
                'posts_count' => $account->posts()->count(),
                'published_count' => $account->posts()->where('status', 'published')->count(),
                'created_at' => $account->created_at->diffForHumans(),
            ]);

        // Stats
        $stats = [
            'total' => SocialAccount::where('account_id', $accountId)->count(),
            'connected' => SocialAccount::where('account_id', $accountId)->where('is_connected', true)->count(),
            'expired' => SocialAccount::where('account_id', $accountId)->get()->filter(fn ($a) => $a->isTokenExpired())->count(),
            'total_posts' => SocialPost::where('account_id', $accountId)->where('status', 'published')->count(),
        ];

        // Platform metadata
        $platformsMeta = [
            'facebook' => ['label' => 'Facebook', 'icon' => 'pi pi-facebook', 'color' => '#1877F2', 'gradient' => 'linear-gradient(135deg, #1877F2, #0d65d9)'],
            'instagram' => ['label' => 'Instagram', 'icon' => 'pi pi-instagram', 'color' => '#E4405F', 'gradient' => 'linear-gradient(135deg, #E4405F, #fd1d1d, #F77737)'],
            'linkedin' => ['label' => 'LinkedIn', 'icon' => 'pi pi-linkedin', 'color' => '#0A66C2', 'gradient' => 'linear-gradient(135deg, #0A66C2, #004182)'],
            'twitter' => ['label' => 'Twitter / X', 'icon' => 'pi pi-twitter', 'color' => '#1DA1F2', 'gradient' => 'linear-gradient(135deg, #1DA1F2, #0d8ecf)'],
        ];

        return Inertia::render('SocialAccounts/Index', [
            'socialAccounts' => $socialAccounts,
            'stats' => $stats,
            'platformsMeta' => $platformsMeta,
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

            return Redirect::route('social-accounts')->with('success', 'Kết nối tài khoản thành công!');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'Không thể kết nối: ' . $e->getMessage());
        }
    }

    public function destroy(SocialAccount $socialAccount): RedirectResponse
    {
        $socialAccount->delete();

        return Redirect::back()->with('success', 'Đã ngắt kết nối tài khoản.');
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

            return Redirect::back()->with('success', 'Token đã được làm mới!');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'Không thể làm mới token: ' . $e->getMessage());
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

        return Redirect::back()->with($isValid ? 'success' : 'error', $isValid ? 'Kết nối hợp lệ!' : 'Kết nối không hợp lệ.');
    }
}
