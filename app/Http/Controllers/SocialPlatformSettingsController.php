<?php

namespace App\Http\Controllers;

use App\Models\SocialPlatformSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class SocialPlatformSettingsController extends Controller
{
    /**
     * Platform metadata.
     */
    private function platformsMeta(): array
    {
        return [
            'facebook' => [
                'label' => 'Facebook',
                'icon' => 'pi pi-facebook',
                'color' => '#1877F2',
                'gradient' => 'linear-gradient(135deg, #1877F2, #0d65d9)',
                'docs_url' => 'https://developers.facebook.com/apps',
                'description' => 'Đăng bài lên Facebook Pages & Groups',
                'default_scopes' => ['pages_manage_posts', 'pages_read_engagement', 'pages_show_list'],
                'setup_steps' => [
                    'Truy cập developers.facebook.com → My Apps → Create App',
                    'Chọn "Business" → Đặt tên app',
                    'Vào Settings → Basic để lấy App ID và App Secret',
                    'Vào Facebook Login → Settings, thêm Redirect URI',
                ],
            ],
            'instagram' => [
                'label' => 'Instagram',
                'icon' => 'pi pi-instagram',
                'color' => '#E4405F',
                'gradient' => 'linear-gradient(135deg, #E4405F, #fd1d1d, #F77737)',
                'docs_url' => 'https://developers.facebook.com/apps',
                'description' => 'Chia sẻ ảnh, story, reels lên Instagram',
                'default_scopes' => ['user_profile', 'user_media'],
                'setup_steps' => [
                    'Instagram API sử dụng Facebook App',
                    'Truy cập developers.facebook.com → My Apps',
                    'Thêm Instagram Basic Display vào app',
                    'Lấy Instagram App ID và Secret',
                ],
            ],
            'linkedin' => [
                'label' => 'LinkedIn',
                'icon' => 'pi pi-linkedin',
                'color' => '#0A66C2',
                'gradient' => 'linear-gradient(135deg, #0A66C2, #004182)',
                'docs_url' => 'https://www.linkedin.com/developers/apps',
                'description' => 'Chia sẻ nội dung chuyên nghiệp',
                'default_scopes' => ['r_liteprofile', 'r_emailaddress', 'w_member_social'],
                'setup_steps' => [
                    'Truy cập linkedin.com/developers → My Apps → Create App',
                    'Điền thông tin app, chọn LinkedIn Page',
                    'Vào Auth tab để lấy Client ID và Client Secret',
                    'Thêm Redirect URL trong OAuth 2.0 settings',
                ],
            ],
            'twitter' => [
                'label' => 'Twitter / X',
                'icon' => 'pi pi-twitter',
                'color' => '#1DA1F2',
                'gradient' => 'linear-gradient(135deg, #1DA1F2, #0d8ecf)',
                'docs_url' => 'https://developer.twitter.com/en/portal/dashboard',
                'description' => 'Đăng tweet, thread, interact',
                'default_scopes' => ['tweet.read', 'tweet.write', 'users.read'],
                'setup_steps' => [
                    'Truy cập developer.twitter.com → Developer Portal',
                    'Tạo Project & App (chọn Free hoặc Basic tier)',
                    'Vào Keys and tokens để lấy API Key và Secret',
                    'Bật OAuth 2.0, thêm Redirect URI',
                ],
            ],
        ];
    }

    /**
     * Show settings page.
     */
    public function index(): Response
    {
        $accountId = Auth::user()->account_id;
        $baseUrl = config('app.url') ?: request()->getSchemeAndHttpHost();

        // Load existing settings
        $settings = SocialPlatformSetting::where('account_id', $accountId)->get();

        // Build platforms data with settings
        $platforms = [];
        foreach ($this->platformsMeta() as $key => $meta) {
            $setting = $settings->firstWhere('platform', $key);

            $defaultRedirectUri = "{$baseUrl}/social-accounts/{$key}/callback";

            $platforms[$key] = array_merge($meta, [
                'has_setting' => (bool) $setting,
                'client_id' => $setting?->client_id ?? '',
                'has_secret' => !empty($setting?->getAttributes()['client_secret'] ?? null),
                'redirect_uri' => $setting?->redirect_uri ?? $defaultRedirectUri,
                'scopes' => $setting?->scopes ?? $meta['default_scopes'],
                'is_active' => $setting?->is_active ?? false,
                'is_configured' => $setting?->isConfigured() ?? false,
                'default_redirect_uri' => $defaultRedirectUri,
            ]);
        }

        return Inertia::render('Settings/SocialPlatforms', [
            'platforms' => $platforms,
        ]);
    }

    /**
     * Save platform configuration.
     */
    public function store(): RedirectResponse
    {
        $validated = Request::validate([
            'platform' => 'required|string|in:facebook,instagram,linkedin,twitter',
            'client_id' => 'required|string|max:255',
            'client_secret' => 'required|string|max:500',
            'redirect_uri' => 'required|url|max:500',
            'scopes' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $accountId = Auth::user()->account_id;

        SocialPlatformSetting::updateOrCreate(
            ['account_id' => $accountId, 'platform' => $validated['platform']],
            [
                'client_id' => $validated['client_id'],
                'client_secret' => $validated['client_secret'],
                'redirect_uri' => $validated['redirect_uri'],
                'scopes' => $validated['scopes'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]
        );

        return Redirect::back()->with('success', 'Cấu hình đã được lưu!');
    }

    /**
     * Toggle platform active status.
     */
    public function toggle(string $platform): RedirectResponse
    {
        $accountId = Auth::user()->account_id;
        $setting = SocialPlatformSetting::where('account_id', $accountId)
            ->where('platform', $platform)
            ->first();

        if ($setting) {
            $setting->update(['is_active' => !$setting->is_active]);
        }

        return Redirect::back()->with('success', 'Đã cập nhật trạng thái.');
    }

    /**
     * Delete platform configuration.
     */
    public function destroy(string $platform): RedirectResponse
    {
        $accountId = Auth::user()->account_id;
        SocialPlatformSetting::where('account_id', $accountId)
            ->where('platform', $platform)
            ->delete();

        return Redirect::back()->with('success', 'Đã xóa cấu hình.');
    }

    /**
     * Get OAuth URL for a platform (used by connect flow).
     */
    public function getAuthUrl(string $platform): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        $setting = SocialPlatformSetting::where('account_id', $accountId)
            ->where('platform', $platform)
            ->where('is_active', true)
            ->first();

        if (!$setting || !$setting->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Nền tảng chưa được cấu hình. Vui lòng vào Cài đặt → Social Platforms để thiết lập.',
                'redirect' => route('social-platforms.index'),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'auth_url' => $setting->getAuthUrl(),
        ]);
    }
}
