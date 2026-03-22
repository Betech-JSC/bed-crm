<?php

namespace App\Http\Controllers;

use App\Models\SystemConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AccountSettingsController extends Controller
{
    // ── Available options (đa ngữ) ──
    private static function options(): array
    {
        return [
            'timezones' => [
                'Asia/Ho_Chi_Minh' => '(UTC+7) Hồ Chí Minh',
                'Asia/Bangkok' => '(UTC+7) Bangkok',
                'Asia/Singapore' => '(UTC+8) Singapore',
                'Asia/Tokyo' => '(UTC+9) Tokyo',
                'Asia/Seoul' => '(UTC+9) Seoul',
                'Asia/Shanghai' => '(UTC+8) Shanghai',
                'Australia/Sydney' => '(UTC+11) Sydney',
                'Europe/London' => '(UTC+0) London',
                'Europe/Berlin' => '(UTC+1) Berlin',
                'America/New_York' => '(UTC-5) New York',
                'America/Los_Angeles' => '(UTC-8) Los Angeles',
            ],
            'currencies' => [
                'VND' => ['symbol' => '₫', 'name_vi' => 'Đồng Việt Nam', 'name_en' => 'Vietnamese Dong'],
                'USD' => ['symbol' => '$', 'name_vi' => 'Đô la Mỹ', 'name_en' => 'US Dollar'],
                'EUR' => ['symbol' => '€', 'name_vi' => 'Euro', 'name_en' => 'Euro'],
                'GBP' => ['symbol' => '£', 'name_vi' => 'Bảng Anh', 'name_en' => 'British Pound'],
                'JPY' => ['symbol' => '¥', 'name_vi' => 'Yên Nhật', 'name_en' => 'Japanese Yen'],
                'SGD' => ['symbol' => 'S$', 'name_vi' => 'Đô la Singapore', 'name_en' => 'Singapore Dollar'],
                'THB' => ['symbol' => '฿', 'name_vi' => 'Baht Thái', 'name_en' => 'Thai Baht'],
                'KRW' => ['symbol' => '₩', 'name_vi' => 'Won Hàn Quốc', 'name_en' => 'South Korean Won'],
            ],
            'locales' => [
                'vi' => ['name_vi' => 'Tiếng Việt', 'name_en' => 'Vietnamese', 'flag' => '🇻🇳'],
                'en' => ['name_vi' => 'Tiếng Anh', 'name_en' => 'English', 'flag' => '🇺🇸'],
            ],
            'date_formats' => [
                'DD/MM/YYYY' => '31/12/2026',
                'MM/DD/YYYY' => '12/31/2026',
                'YYYY-MM-DD' => '2026-12-31',
                'DD-MM-YYYY' => '31-12-2026',
                'DD.MM.YYYY' => '31.12.2026',
            ],
            'time_formats' => [
                '24h' => '14:30',
                '12h' => '2:30 PM',
            ],
            'industries' => [
                'technology' => ['vi' => 'Công nghệ', 'en' => 'Technology'],
                'saas' => ['vi' => 'SaaS', 'en' => 'SaaS'],
                'ecommerce' => ['vi' => 'Thương mại điện tử', 'en' => 'E-commerce'],
                'education' => ['vi' => 'Giáo dục', 'en' => 'Education'],
                'healthcare' => ['vi' => 'Y tế', 'en' => 'Healthcare'],
                'finance' => ['vi' => 'Tài chính', 'en' => 'Finance & Banking'],
                'real_estate' => ['vi' => 'Bất động sản', 'en' => 'Real Estate'],
                'manufacturing' => ['vi' => 'Sản xuất', 'en' => 'Manufacturing'],
                'consulting' => ['vi' => 'Tư vấn', 'en' => 'Consulting'],
                'marketing' => ['vi' => 'Marketing & Quảng cáo', 'en' => 'Marketing & Advertising'],
                'other' => ['vi' => 'Khác', 'en' => 'Other'],
            ],
        ];
    }

    // ════════════════════════════════════════════
    //  MAIN SETTINGS PAGE
    // ════════════════════════════════════════════

    public function index(): InertiaResponse
    {
        $account = Auth::user()->account;
        $locale = app()->getLocale();

        // Load extended config groups
        $configGroups = [
            'regional' => SystemConfig::getGroup($account->id, 'regional'),
            'crm' => SystemConfig::getGroup($account->id, 'crm'),
            'notifications' => SystemConfig::getGroup($account->id, 'notifications'),
            'appearance' => SystemConfig::getGroup($account->id, 'appearance'),
        ];

        return Inertia::render('AccountSettings/Index', [
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'slogan' => $account->slogan,
                'description' => $account->description,
                'logo' => $account->logo ? Storage::url($account->logo) : null,
                'favicon' => $account->favicon ? Storage::url($account->favicon) : null,
                'timezone' => $account->timezone,
                'currency' => $account->currency,
                'locale' => $account->locale,
                'date_format' => $account->date_format,
                'time_format' => $account->time_format,
                'fiscal_year_start' => $account->fiscal_year_start,
                'phone' => $account->phone,
                'email' => $account->email,
                'website' => $account->website,
                'address' => $account->address,
                'tax_id' => $account->tax_id,
                'registration_number' => $account->registration_number,
                'industry' => $account->industry,
                'company_size' => $account->company_size,
                'founded_year' => $account->founded_year,
                'social_links' => $account->social_links ?? [],
            ],
            'config_groups' => $configGroups,
            'options' => self::options(),
            'app_locale' => $locale,
        ]);
    }

    // ════════════════════════════════════════════
    //  UPDATE COMPANY PROFILE
    // ════════════════════════════════════════════

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slogan' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:2000',
            'logo' => 'nullable|image|max:2048',
            'favicon' => 'nullable|image|max:1024',
            'timezone' => 'sometimes|string|max:50',
            'currency' => 'sometimes|string|max:10',
            'locale' => 'sometimes|in:vi,en',
            'date_format' => 'sometimes|string|max:20',
            'time_format' => 'sometimes|in:24h,12h',
            'fiscal_year_start' => 'sometimes|string|max:5',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'tax_id' => 'nullable|string|max:30',
            'registration_number' => 'nullable|string|max:50',
            'industry' => 'nullable|string|max:50',
            'company_size' => 'nullable|integer|min:1',
            'founded_year' => 'nullable|string|max:4',
            'social_links' => 'nullable|array',
            'social_links.facebook' => 'nullable|url|max:255',
            'social_links.linkedin' => 'nullable|url|max:255',
            'social_links.twitter' => 'nullable|url|max:255',
            'social_links.youtube' => 'nullable|url|max:255',
            'social_links.instagram' => 'nullable|url|max:255',
            'social_links.tiktok' => 'nullable|url|max:255',
        ]);

        $account = Auth::user()->account;

        // Handle logo upload
        if ($request->hasFile('logo')) {
            if ($account->logo && Storage::exists($account->logo)) {
                Storage::delete($account->logo);
            }
            $validated['logo'] = $request->file('logo')->store('accounts/logos', 'public');
        } else {
            unset($validated['logo']);
        }

        // Handle favicon upload
        if ($request->hasFile('favicon')) {
            if ($account->favicon && Storage::exists($account->favicon)) {
                Storage::delete($account->favicon);
            }
            $validated['favicon'] = $request->file('favicon')->store('accounts/favicons', 'public');
        } else {
            unset($validated['favicon']);
        }

        $account->update($validated);

        // Update app locale if changed
        if (isset($validated['locale'])) {
            session(['locale' => $validated['locale']]);
            app()->setLocale($validated['locale']);
        }

        return redirect()->route('account-settings.index')->with('success', 'Settings updated.');
    }

    // ════════════════════════════════════════════
    //  EXTENDED CONFIGS (key-value store)
    // ════════════════════════════════════════════

    /**
     * Update a config group.
     */
    public function updateConfigGroup(Request $request, string $group): JsonResponse
    {
        $request->validate(['configs' => 'required|array']);
        $accountId = Auth::user()->account_id;

        SystemConfig::setBulk($accountId, $group, $request->configs);

        return response()->json([
            'success' => true,
            'configs' => SystemConfig::getGroup($accountId, $group),
        ]);
    }

    /**
     * Get a single config group (API).
     */
    public function getConfigGroup(string $group): JsonResponse
    {
        $accountId = Auth::user()->account_id;
        return response()->json(SystemConfig::getGroup($accountId, $group));
    }

    /**
     * Seed default configs for the current account.
     */
    public function seedDefaults(): JsonResponse
    {
        SystemConfig::seedDefaults(Auth::user()->account_id);
        return response()->json(['success' => true]);
    }

    /**
     * Remove logo.
     */
    public function removeLogo(): JsonResponse
    {
        $account = Auth::user()->account;
        if ($account->logo && Storage::exists($account->logo)) {
            Storage::delete($account->logo);
        }
        $account->update(['logo' => null]);
        return response()->json(['success' => true]);
    }

    /**
     * Remove favicon.
     */
    public function removeFavicon(): JsonResponse
    {
        $account = Auth::user()->account;
        if ($account->favicon && Storage::exists($account->favicon)) {
            Storage::delete($account->favicon);
        }
        $account->update(['favicon' => null]);
        return response()->json(['success' => true]);
    }
}
