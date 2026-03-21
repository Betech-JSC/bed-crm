<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * MemoryManager
 * ─────────────
 * Manages per-user AI memory for personalized interactions.
 * Stores user preferences, conversation patterns, and learned insights.
 *
 * Storage: Redis/File cache (fast) + Database (persistent).
 */
class MemoryManager
{
    private const CACHE_TTL = 86400; // 24 hours

    /**
     * Get user memory context for AI prompt injection.
     */
    public function getContext(?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();
        if (!$userId) return [];

        $memory = $this->get($userId);

        return [
            'user_preferences' => $memory['preferences'] ?? [],
            'interaction_style' => $memory['style'] ?? 'professional',
            'language' => $memory['language'] ?? 'vi',
            'learned_facts' => array_slice($memory['facts'] ?? [], -10), // Last 10 facts
            'frequent_actions' => $memory['frequent_actions'] ?? [],
        ];
    }

    /**
     * Remember a fact about the user.
     */
    public function rememberFact(string $fact, ?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        if (!$userId) return;

        $memory = $this->get($userId);
        $facts = $memory['facts'] ?? [];

        // Avoid duplicates
        if (!in_array($fact, $facts)) {
            $facts[] = $fact;
            // Keep max 50 facts
            if (count($facts) > 50) $facts = array_slice($facts, -50);
        }

        $memory['facts'] = $facts;
        $memory['updated_at'] = now()->toISOString();
        $this->save($userId, $memory);
    }

    /**
     * Update user preferences.
     */
    public function setPreference(string $key, mixed $value, ?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        if (!$userId) return;

        $memory = $this->get($userId);
        $prefs = $memory['preferences'] ?? [];
        $prefs[$key] = $value;
        $memory['preferences'] = $prefs;
        $memory['updated_at'] = now()->toISOString();
        $this->save($userId, $memory);
    }

    /**
     * Track a user action for frequency analysis.
     */
    public function trackAction(string $action, ?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        if (!$userId) return;

        $memory = $this->get($userId);
        $actions = $memory['frequent_actions'] ?? [];
        $actions[$action] = ($actions[$action] ?? 0) + 1;

        // Keep top 20 actions
        arsort($actions);
        $actions = array_slice($actions, 0, 20, true);

        $memory['frequent_actions'] = $actions;
        $this->save($userId, $memory);
    }

    /**
     * Set interaction style (formal, friendly, brief, detailed).
     */
    public function setStyle(string $style, ?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        if (!$userId) return;

        $memory = $this->get($userId);
        $memory['style'] = $style;
        $this->save($userId, $memory);
    }

    /**
     * Clear all memory for a user.
     */
    public function clear(?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        if (!$userId) return;

        Cache::forget($this->cacheKey($userId));
    }

    // ── Storage ──

    private function get(int $userId): array
    {
        return Cache::get($this->cacheKey($userId), [
            'preferences' => [],
            'style' => 'professional',
            'language' => 'vi',
            'facts' => [],
            'frequent_actions' => [],
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ]);
    }

    private function save(int $userId, array $memory): void
    {
        Cache::put($this->cacheKey($userId), $memory, self::CACHE_TTL);
    }

    private function cacheKey(int $userId): string
    {
        return "ai_memory:user:{$userId}";
    }
}
