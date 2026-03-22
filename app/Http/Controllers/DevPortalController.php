<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\Webhook;
use App\Models\ApiLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DevPortalController extends Controller
{
    public function index(Request $request)
    {
        $accountId = auth()->user()->account_id;

        // API Keys
        $apiKeys = ApiKey::where('account_id', $accountId)
            ->with('creator')
            ->latest()
            ->get()
            ->map(fn ($k) => [
                'id' => $k->id,
                'name' => $k->name,
                'key' => $k->key,
                'secret_last4' => $k->secret_last4,
                'permissions' => $k->permissions,
                'rate_limit' => $k->rate_limit,
                'allowed_ips' => $k->allowed_ips,
                'allowed_domains' => $k->allowed_domains,
                'is_active' => $k->is_active,
                'status_label' => $k->status_label,
                'is_expired' => $k->is_expired,
                'expires_at' => $k->expires_at?->format('Y-m-d'),
                'last_used_at' => $k->last_used_at?->diffForHumans(),
                'total_requests' => $k->total_requests,
                'creator_name' => $k->creator?->name ?? '—',
                'notes' => $k->notes,
                'created_at' => $k->created_at->format('d/m/Y'),
            ]);

        // Webhooks
        $webhooks = Webhook::where('account_id', $accountId)
            ->with('creator')
            ->latest()
            ->get()
            ->map(fn ($w) => [
                'id' => $w->id,
                'name' => $w->name,
                'url' => $w->url,
                'events' => $w->events,
                'headers' => $w->headers,
                'is_active' => $w->is_active,
                'status_label' => $w->status_label,
                'retry_count' => $w->retry_count,
                'timeout' => $w->timeout,
                'last_triggered_at' => $w->last_triggered_at?->diffForHumans(),
                'last_status_code' => $w->last_status_code,
                'total_deliveries' => $w->total_deliveries,
                'total_failures' => $w->total_failures,
                'success_rate' => $w->success_rate,
                'creator_name' => $w->creator?->name ?? '—',
                'notes' => $w->notes,
                'created_at' => $w->created_at->format('d/m/Y'),
            ]);

        // Recent API Logs
        $logs = ApiLog::where('account_id', $accountId)
            ->with('apiKey')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'api_key_name' => $l->apiKey?->name ?? '—',
                'method' => $l->method,
                'endpoint' => $l->endpoint,
                'status_code' => $l->status_code,
                'status_class' => $l->status_class,
                'ip_address' => $l->ip_address,
                'duration_ms' => $l->duration_ms,
                'error_message' => $l->error_message,
                'created_at' => $l->created_at->format('d/m H:i:s'),
            ]);

        // Stats
        $stats = [
            'total_keys' => ApiKey::where('account_id', $accountId)->count(),
            'active_keys' => ApiKey::where('account_id', $accountId)->where('is_active', true)->count(),
            'total_webhooks' => Webhook::where('account_id', $accountId)->count(),
            'active_webhooks' => Webhook::where('account_id', $accountId)->where('is_active', true)->count(),
            'total_requests_today' => ApiLog::where('account_id', $accountId)->whereDate('created_at', today())->count(),
            'error_rate_today' => $this->calcErrorRate($accountId),
        ];

        return Inertia::render('DevPortal/Index', [
            'apiKeys' => $apiKeys,
            'webhooks' => $webhooks,
            'logs' => $logs,
            'stats' => $stats,
            'availableEvents' => Webhook::availableEvents(),
            'availablePermissions' => ['read', 'write', 'delete', 'admin'],
        ]);
    }

    private function calcErrorRate($accountId): ?float
    {
        $total = ApiLog::where('account_id', $accountId)->whereDate('created_at', today())->count();
        if ($total === 0) return null;
        $errors = ApiLog::where('account_id', $accountId)->whereDate('created_at', today())->where('status_code', '>=', 400)->count();
        return round($errors / $total * 100, 1);
    }

    // ── API Key CRUD ──
    public function storeApiKey(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'rate_limit' => 'nullable|integer|min:1',
            'allowed_ips' => 'nullable|array',
            'allowed_domains' => 'nullable|array',
            'expires_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $accountId = auth()->user()->account_id;
        $secret = ApiKey::generateSecret();

        $apiKey = ApiKey::create([
            'account_id' => $accountId,
            'name' => $validated['name'],
            'key' => ApiKey::generateKey(),
            'secret_hash' => hash('sha256', $secret),
            'secret_last4' => substr($secret, -4),
            'permissions' => $validated['permissions'] ?? ['read'],
            'rate_limit' => $validated['rate_limit'] ?? 1000,
            'allowed_ips' => $validated['allowed_ips'],
            'allowed_domains' => $validated['allowed_domains'],
            'is_active' => true,
            'expires_at' => $validated['expires_at'],
            'created_by' => auth()->id(),
            'notes' => $validated['notes'],
        ]);

        return back()->with('success', 'API Key đã tạo. Secret: ' . $secret . ' (chỉ hiện 1 lần!)');
    }

    public function updateApiKey(Request $request, ApiKey $apiKey)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'rate_limit' => 'nullable|integer|min:1',
            'allowed_ips' => 'nullable|array',
            'allowed_domains' => 'nullable|array',
            'is_active' => 'boolean',
            'expires_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $apiKey->update($validated);

        return back()->with('success', 'Đã cập nhật API Key.');
    }

    public function destroyApiKey(ApiKey $apiKey)
    {
        $apiKey->delete();
        return back()->with('success', 'Đã xóa API Key.');
    }

    public function regenerateApiKey(ApiKey $apiKey)
    {
        $secret = ApiKey::generateSecret();
        $apiKey->update([
            'key' => ApiKey::generateKey(),
            'secret_hash' => hash('sha256', $secret),
            'secret_last4' => substr($secret, -4),
        ]);

        return back()->with('success', 'Đã tạo lại API Key. Secret mới: ' . $secret . ' (chỉ hiện 1 lần!)');
    }

    // ── Webhook CRUD ──
    public function storeWebhook(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'headers' => 'nullable|array',
            'retry_count' => 'nullable|integer|min:0|max:10',
            'timeout' => 'nullable|integer|min:1|max:60',
            'notes' => 'nullable|string',
        ]);

        $accountId = auth()->user()->account_id;

        Webhook::create([
            'account_id' => $accountId,
            'name' => $validated['name'],
            'url' => $validated['url'],
            'secret' => Str::random(32),
            'events' => $validated['events'],
            'headers' => $validated['headers'],
            'is_active' => true,
            'retry_count' => $validated['retry_count'] ?? 3,
            'timeout' => $validated['timeout'] ?? 10,
            'created_by' => auth()->id(),
            'notes' => $validated['notes'],
        ]);

        return back()->with('success', 'Đã tạo Webhook.');
    }

    public function updateWebhook(Request $request, Webhook $webhook)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'headers' => 'nullable|array',
            'is_active' => 'boolean',
            'retry_count' => 'nullable|integer|min:0|max:10',
            'timeout' => 'nullable|integer|min:1|max:60',
            'notes' => 'nullable|string',
        ]);

        $webhook->update($validated);

        return back()->with('success', 'Đã cập nhật Webhook.');
    }

    public function destroyWebhook(Webhook $webhook)
    {
        $webhook->delete();
        return back()->with('success', 'Đã xóa Webhook.');
    }

    public function testWebhook(Webhook $webhook)
    {
        // Send test payload
        try {
            $client = new \GuzzleHttp\Client(['timeout' => $webhook->timeout ?? 10]);
            $response = $client->post($webhook->url, [
                'json' => [
                    'event' => 'webhook.test',
                    'timestamp' => now()->toIso8601String(),
                    'data' => ['message' => 'BED CRM webhook test'],
                ],
                'headers' => array_merge(
                    ['X-Webhook-Secret' => $webhook->secret, 'Content-Type' => 'application/json'],
                    $webhook->headers ?? []
                ),
            ]);
            $webhook->update([
                'last_triggered_at' => now(),
                'last_status_code' => $response->getStatusCode(),
                'total_deliveries' => $webhook->total_deliveries + 1,
            ]);
            return back()->with('success', 'Test webhook thành công! Status: ' . $response->getStatusCode());
        } catch (\Exception $e) {
            $webhook->update([
                'last_triggered_at' => now(),
                'last_status_code' => 0,
                'total_deliveries' => $webhook->total_deliveries + 1,
                'total_failures' => $webhook->total_failures + 1,
            ]);
            return back()->with('error', 'Test webhook thất bại: ' . $e->getMessage());
        }
    }
}
