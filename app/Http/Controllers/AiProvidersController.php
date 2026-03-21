<?php

namespace App\Http\Controllers;

use App\Models\AiProvider;
use App\Services\AI\AiGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class AiProvidersController extends Controller
{
    /**
     * Show AI Providers settings page.
     */
    public function index(): Response
    {
        $accountId = Auth::user()->account_id;

        // Load existing providers for this account
        $providers = AiProvider::forAccount($accountId)
            ->orderBy('is_default', 'desc')
            ->orderBy('display_name')
            ->get()
            ->map(fn (AiProvider $p) => [
                'id' => $p->id,
                'slug' => $p->slug,
                'display_name' => $p->display_name,
                'model' => $p->model,
                'is_active' => $p->is_active,
                'is_default' => $p->is_default,
                'status' => $p->status,
                'has_api_key' => $p->has_api_key,
                'masked_key' => $p->masked_key,
                'last_tested_at' => $p->last_tested_at?->diffForHumans(),
                'last_error' => $p->last_error,
                'total_requests' => $p->total_requests,
                'total_tokens' => $p->total_tokens,
                'config' => $p->config,
                'provider_meta' => $p->provider_meta,
            ]);

        // Get known providers registry
        $registry = collect(AiProvider::PROVIDERS)->map(fn ($meta, $slug) => [
            'slug' => $slug,
            'display_name' => $meta['display_name'],
            'icon' => $meta['icon'],
            'color' => $meta['color'],
            'models' => $meta['models'],
            'default_model' => $meta['default_model'],
            'supports_embed' => $meta['supports_embed'],
        ]);

        return Inertia::render('Settings/AiProviders', [
            'providers' => $providers,
            'registry' => $registry,
        ]);
    }

    /**
     * Store or update a provider.
     */
    public function store(Request $request)
    {
        $request->validate([
            'slug' => 'required|string|max:50',
            'api_key' => 'nullable|string|max:500',
            'model' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'config' => 'nullable|array',
        ]);

        $accountId = Auth::user()->account_id;
        $slug = $request->input('slug');
        $meta = AiProvider::PROVIDERS[$slug] ?? null;

        $data = [
            'display_name' => $meta['display_name'] ?? ucfirst($slug),
            'model' => $request->input('model') ?? ($meta['default_model'] ?? null),
            'is_active' => $request->boolean('is_active', true),
            'config' => $request->input('config'),
        ];

        // Only update api_key if provided (not masked)
        $apiKey = $request->input('api_key');
        if ($apiKey && !str_contains($apiKey, '•')) {
            $data['api_key'] = $apiKey;
        }

        $provider = AiProvider::updateOrCreate(
            ['account_id' => $accountId, 'slug' => $slug],
            $data,
        );

        // If this is the only active provider, make it default
        $activeCount = AiProvider::forAccount($accountId)->active()->count();
        if ($activeCount === 1) {
            $provider->makeDefault();
        }

        return Redirect::back()->with('success', "Provider {$provider->display_name} đã được lưu.");
    }

    /**
     * Set a provider as default.
     */
    public function setDefault(Request $request, AiProvider $provider)
    {
        $this->authorizeProvider($provider);

        $provider->makeDefault();

        return Redirect::back()->with('success', "{$provider->display_name} đã được đặt làm mặc định.");
    }

    /**
     * Toggle provider active status.
     */
    public function toggle(AiProvider $provider)
    {
        $this->authorizeProvider($provider);

        $provider->update(['is_active' => !$provider->is_active]);

        $status = $provider->is_active ? 'bật' : 'tắt';
        return Redirect::back()->with('success', "{$provider->display_name} đã được {$status}.");
    }

    /**
     * Test provider connection.
     */
    public function test(AiProvider $provider): JsonResponse
    {
        $this->authorizeProvider($provider);

        if (!$provider->has_api_key) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa cấu hình API Key.',
            ]);
        }

        try {
            $gateway = app(AiGateway::class);

            // Dynamically configure the provider with the stored key
            $driverName = $provider->slug;
            $driver = $gateway->driver($driverName);

            if (!$driver->isConfigured()) {
                // The provider exists in gateway but isn't configured via .env
                // Try to test with a simple prompt anyway
                return response()->json([
                    'success' => false,
                    'message' => "Provider {$driverName} chưa được cấu hình API key trong .env. Hãy thêm key vào file .env hoặc cấu hình trong trang này.",
                ]);
            }

            $result = $driver->chat('Respond with exactly: OK', [
                'max_tokens' => 10,
                'temperature' => 0,
            ]);

            $provider->markConnected();

            return response()->json([
                'success' => true,
                'message' => 'Kết nối thành công!',
                'model' => $result['metadata']['model'] ?? 'unknown',
                'provider' => $result['metadata']['provider'] ?? $driverName,
            ]);
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $provider->markError($error);

            return response()->json([
                'success' => false,
                'message' => "Kết nối thất bại: {$error}",
            ]);
        }
    }

    /**
     * Delete a provider configuration.
     */
    public function destroy(AiProvider $provider)
    {
        $this->authorizeProvider($provider);

        $name = $provider->display_name;
        $provider->delete();

        return Redirect::back()->with('success', "{$name} đã được xóa.");
    }

    /**
     * Authorize that the provider belongs to the current user's account.
     */
    private function authorizeProvider(AiProvider $provider): void
    {
        if ($provider->account_id !== Auth::user()->account_id) {
            abort(403);
        }
    }
}
