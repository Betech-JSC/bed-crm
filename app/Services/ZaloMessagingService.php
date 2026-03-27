<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Zalo Messaging Service
 *
 * Handles sending messages via Zalo OA (Official Account) API.
 * This is a thin wrapper that can be extended to support:
 * - Zalo OA API v3 (when access token is configured)
 * - SMS fallback
 * - Third-party Zalo integration services
 *
 * Configuration:
 *   ZALO_OA_ACCESS_TOKEN=xxx       (in .env)
 *   ZALO_OA_ID=xxx                 (in .env)
 *   ZALO_API_URL=https://openapi.zalo.me  (in .env, optional)
 */
class ZaloMessagingService
{
    private ?string $accessToken;
    private ?string $oaId;
    private string $apiUrl;

    public function __construct()
    {
        $this->accessToken = config('services.zalo.access_token');
        $this->oaId = config('services.zalo.oa_id');
        $this->apiUrl = config('services.zalo.api_url', 'https://openapi.zalo.me/v3.0/oa');
    }

    /**
     * Send a text message to a phone number via Zalo OA.
     *
     * Note: Zalo OA API requires the user to have interacted with the OA first.
     * For cold outreach, this would typically use Zalo's "send message by phone"
     * feature or a third-party integration.
     */
    public function sendMessage(string $phone, string $message): bool
    {
        // Normalize phone number
        $phone = $this->normalizePhone($phone);

        // If Zalo OA not configured, log and skip gracefully
        if (!$this->accessToken) {
            Log::info('Zalo OA not configured, skipping message', [
                'phone' => $phone,
                'message_preview' => mb_substr($message, 0, 100),
            ]);
            return false;
        }

        try {
            $response = Http::withHeaders([
                'access_token' => $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/message/cs", [
                'recipient' => [
                    'user_id' => $this->getUserIdByPhone($phone),
                ],
                'message' => [
                    'text' => $message,
                ],
            ]);

            if ($response->successful() && $response->json('error') === 0) {
                Log::info('Zalo message sent successfully', ['phone' => $phone]);
                return true;
            }

            Log::warning('Zalo message failed', [
                'phone' => $phone,
                'response' => $response->json(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Zalo message exception', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get Zalo user ID from phone number.
     *
     * This uses Zalo's "get follower by phone" API.
     * Requires the phone owner to be following the OA.
     */
    private function getUserIdByPhone(string $phone): ?string
    {
        try {
            $response = Http::withHeaders([
                'access_token' => $this->accessToken,
            ])->get("{$this->apiUrl}/getprofile", [
                'data' => json_encode(['user_id_by_phone' => $phone]),
            ]);

            return $response->json('data.user_id');
        } catch (\Exception $e) {
            Log::warning('Could not resolve Zalo user ID for phone', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return $phone; // Fallback to phone as identifier
        }
    }

    /**
     * Normalize Vietnamese phone number to format: 84xxxxxxxxx
     */
    private function normalizePhone(string $phone): string
    {
        // Remove spaces and dashes
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

        // Convert +84 to 84
        if (str_starts_with($phone, '+84')) {
            $phone = '84' . substr($phone, 3);
        }

        // Convert 0xx to 84xx
        if (str_starts_with($phone, '0')) {
            $phone = '84' . substr($phone, 1);
        }

        return $phone;
    }

    /**
     * Check if Zalo OA is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->accessToken) && !empty($this->oaId);
    }
}
