<?php

namespace App\Services;

use App\Models\AiTrendItem;
use App\Models\AiTrendMonitor;
use App\Models\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiTrendService
{
    /**
     * Fetch trends for a specific monitor.
     */
    public function fetchForMonitor(AiTrendMonitor $monitor): array
    {
        $items = match ($monitor->source) {
            AiTrendMonitor::SOURCE_GITHUB => $this->fetchGitHubTrending($monitor),
            AiTrendMonitor::SOURCE_HACKERNEWS => $this->fetchHackerNews($monitor),
            AiTrendMonitor::SOURCE_PRODUCTHUNT => $this->fetchProductHunt($monitor),
            AiTrendMonitor::SOURCE_DEVTO => $this->fetchDevTo($monitor),
            default => [],
        };

        $newItems = [];
        foreach ($items as $item) {
            $existing = AiTrendItem::where('monitor_id', $monitor->id)
                ->where('external_id', $item['external_id'])
                ->first();

            if (!$existing) {
                $newItems[] = AiTrendItem::create(array_merge($item, [
                    'account_id' => $monitor->account_id,
                    'monitor_id' => $monitor->id,
                    'source' => $monitor->source,
                ]));
            }
        }

        // Send notification if there are new items
        if (count($newItems) > 0 && $monitor->notify_in_app) {
            $this->sendNotification($monitor, $newItems);
        }

        // Update next run
        $monitor->calculateNextRun();

        return $newItems;
    }

    /**
     * Fetch all due monitors and process them.
     */
    public function fetchAllDue(): array
    {
        $monitors = AiTrendMonitor::due()->get();
        $results = ['processed' => 0, 'new_items' => 0, 'errors' => 0];

        foreach ($monitors as $monitor) {
            try {
                $items = $this->fetchForMonitor($monitor);
                $results['processed']++;
                $results['new_items'] += count($items);
            } catch (\Exception $e) {
                $results['errors']++;
                Log::error("AiTrend fetch error for monitor #{$monitor->id}: {$e->getMessage()}");
            }
        }

        return $results;
    }

    /**
     * Fetch GitHub Trending repositories.
     * Uses the unofficial GitHub trending page scraping via github-trending-api.
     */
    protected function fetchGitHubTrending(AiTrendMonitor $monitor): array
    {
        $config = $monitor->source_config ?? [];
        $language = $config['language'] ?? '';
        $since = $config['since'] ?? 'daily';
        $spokenLanguage = $config['spoken_language'] ?? '';

        // Use the unofficial github-trending-api endpoint or scrape
        $url = 'https://api.gitterapp.com/repositories';
        $params = ['since' => $since];
        if ($language) $params['language'] = $language;
        if ($spokenLanguage) $params['spoken_language_code'] = $spokenLanguage;

        try {
            $response = Http::timeout(30)->get($url, $params);

            if (!$response->successful()) {
                // Fallback: try alternative API
                return $this->fetchGitHubTrendingFallback($config);
            }

            $repos = $response->json() ?? [];

            return collect($repos)->take(25)->map(function ($repo) {
                return [
                    'external_id' => $repo['author'] . '/' . $repo['name'],
                    'title' => ($repo['author'] ?? '') . '/' . ($repo['name'] ?? ''),
                    'description' => $repo['description'] ?? '',
                    'url' => $repo['url'] ?? ('https://github.com/' . $repo['author'] . '/' . $repo['name']),
                    'image_url' => $repo['avatar'] ?? null,
                    'author' => $repo['author'] ?? '',
                    'language' => $repo['language'] ?? null,
                    'stars' => $repo['stars'] ?? 0,
                    'stars_today' => $repo['currentPeriodStars'] ?? 0,
                    'forks' => $repo['forks'] ?? 0,
                    'score' => $repo['stars'] ?? 0,
                    'tags' => $repo['builtBy'] ?? [],
                    'published_at' => now(),
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::warning("GitHub trending fetch failed: {$e->getMessage()}");
            return $this->fetchGitHubTrendingFallback($config);
        }
    }

    /**
     * Fallback GitHub trending using GitHub Search API.
     */
    protected function fetchGitHubTrendingFallback(array $config): array
    {
        $since = match ($config['since'] ?? 'daily') {
            'daily' => now()->subDay()->toDateString(),
            'weekly' => now()->subWeek()->toDateString(),
            'monthly' => now()->subMonth()->toDateString(),
            default => now()->subDay()->toDateString(),
        };

        $query = "topic:artificial-intelligence created:>{$since}";
        $language = $config['language'] ?? '';
        if ($language) {
            $query .= " language:{$language}";
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Accept' => 'application/vnd.github.v3+json'])
                ->get('https://api.github.com/search/repositories', [
                    'q' => $query,
                    'sort' => 'stars',
                    'order' => 'desc',
                    'per_page' => 25,
                ]);

            if (!$response->successful()) return [];

            $data = $response->json();

            return collect($data['items'] ?? [])->map(function ($repo) {
                return [
                    'external_id' => $repo['full_name'],
                    'title' => $repo['full_name'],
                    'description' => $repo['description'] ?? '',
                    'url' => $repo['html_url'],
                    'image_url' => $repo['owner']['avatar_url'] ?? null,
                    'author' => $repo['owner']['login'] ?? '',
                    'language' => $repo['language'] ?? null,
                    'stars' => $repo['stargazers_count'] ?? 0,
                    'stars_today' => 0,
                    'forks' => $repo['forks_count'] ?? 0,
                    'score' => $repo['stargazers_count'] ?? 0,
                    'tags' => $repo['topics'] ?? [],
                    'published_at' => $repo['created_at'] ?? now(),
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::warning("GitHub search fallback failed: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Fetch Hacker News top/new stories about AI.
     */
    protected function fetchHackerNews(AiTrendMonitor $monitor): array
    {
        $config = $monitor->source_config ?? [];
        $storyType = $config['type'] ?? 'top'; // top, new, best

        try {
            // Get top story IDs
            $response = Http::timeout(30)
                ->get("https://hacker-news.firebaseio.com/v0/{$storyType}stories.json");

            if (!$response->successful()) return [];

            $storyIds = array_slice($response->json() ?? [], 0, 50);
            $items = [];

            // Fetch individual stories (batch)
            foreach (array_chunk($storyIds, 10) as $chunk) {
                foreach ($chunk as $id) {
                    try {
                        $story = Http::timeout(10)
                            ->get("https://hacker-news.firebaseio.com/v0/item/{$id}.json")
                            ->json();

                        if (!$story || ($story['type'] ?? '') !== 'story') continue;

                        $title = $story['title'] ?? '';
                        // Filter for AI-related stories
                        $aiKeywords = ['ai', 'artificial intelligence', 'machine learning', 'ml', 'llm',
                            'gpt', 'claude', 'gemini', 'openai', 'anthropic', 'deep learning',
                            'neural', 'transformer', 'diffusion', 'copilot', 'chatbot', 'agent'];

                        $isAiRelated = collect($aiKeywords)->contains(
                            fn ($keyword) => Str::contains(strtolower($title), $keyword)
                        );

                        if (!$isAiRelated) continue;

                        $items[] = [
                            'external_id' => 'hn_' . $story['id'],
                            'title' => $title,
                            'description' => $story['text'] ?? '',
                            'url' => $story['url'] ?? "https://news.ycombinator.com/item?id={$story['id']}",
                            'image_url' => null,
                            'author' => $story['by'] ?? '',
                            'language' => null,
                            'stars' => 0,
                            'stars_today' => 0,
                            'forks' => 0,
                            'score' => $story['score'] ?? 0,
                            'comments_count' => $story['descendants'] ?? 0,
                            'tags' => ['hackernews'],
                            'published_at' => isset($story['time'])
                                ? \Carbon\Carbon::createFromTimestamp($story['time'])
                                : now(),
                        ];

                        if (count($items) >= 25) break 2;
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }

            return $items;
        } catch (\Exception $e) {
            Log::warning("HackerNews fetch failed: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Fetch Product Hunt latest products (via unofficial approach).
     */
    protected function fetchProductHunt(AiTrendMonitor $monitor): array
    {
        // Product Hunt requires API key. We'll use a basic approach.
        try {
            $response = Http::timeout(30)
                ->get('https://api.producthunt.com/v2/api/graphql', []);

            // PH requires OAuth — returning empty for now, can be configured later
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Fetch DEV.to top articles about AI.
     */
    protected function fetchDevTo(AiTrendMonitor $monitor): array
    {
        $config = $monitor->source_config ?? [];
        $tag = $config['tag'] ?? 'ai';

        try {
            $response = Http::timeout(30)
                ->get('https://dev.to/api/articles', [
                    'tag' => $tag,
                    'top' => 7, // last 7 days
                    'per_page' => 25,
                ]);

            if (!$response->successful()) return [];

            $articles = $response->json() ?? [];

            return collect($articles)->map(function ($article) {
                return [
                    'external_id' => 'devto_' . $article['id'],
                    'title' => $article['title'] ?? '',
                    'description' => $article['description'] ?? '',
                    'url' => $article['url'] ?? '',
                    'image_url' => $article['cover_image'] ?? $article['social_image'] ?? null,
                    'author' => $article['user']['name'] ?? $article['user']['username'] ?? '',
                    'language' => null,
                    'stars' => 0,
                    'stars_today' => 0,
                    'forks' => 0,
                    'score' => $article['public_reactions_count'] ?? 0,
                    'comments_count' => $article['comments_count'] ?? 0,
                    'tags' => $article['tag_list'] ?? [],
                    'published_at' => $article['published_at'] ?? now(),
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::warning("DEV.to fetch failed: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Send CRM notification about new trend items.
     */
    protected function sendNotification(AiTrendMonitor $monitor, array $newItems): void
    {
        $count = count($newItems);
        $sourceMeta = AiTrendMonitor::getSources()[$monitor->source] ?? ['label' => $monitor->source];

        $topItems = collect($newItems)->take(3)->map(fn ($item) => "• {$item->title}")->join("\n");

        Notification::create([
            'account_id' => $monitor->account_id,
            'user_id' => $monitor->created_by,
            'event_type' => Notification::EVENT_SYSTEM,
            'title' => "🔥 {$count} xu hướng AI mới từ {$sourceMeta['label']}",
            'body' => "Đã phát hiện {$count} mục mới:\n{$topItems}",
            'icon' => $sourceMeta['icon'] ?? 'pi pi-sparkles',
            'severity' => Notification::SEVERITY_INFO,
            'link' => '/ai-trends',
            'data' => [
                'monitor_id' => $monitor->id,
                'source' => $monitor->source,
                'count' => $count,
            ],
        ]);
    }

    /**
     * Get dashboard summary stats.
     */
    public function getDashboardStats(int $accountId): array
    {
        $totalItems = AiTrendItem::where('account_id', $accountId)->count();
        $unreadItems = AiTrendItem::where('account_id', $accountId)->unread()->count();
        $todayItems = AiTrendItem::where('account_id', $accountId)
            ->whereDate('created_at', today())->count();
        $pinnedItems = AiTrendItem::where('account_id', $accountId)->pinned()->count();
        $activeMonitors = AiTrendMonitor::where('account_id', $accountId)->active()->count();

        $sourceCounts = AiTrendItem::where('account_id', $accountId)
            ->recent(7)
            ->selectRaw('source, count(*) as count')
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();

        return compact('totalItems', 'unreadItems', 'todayItems', 'pinnedItems', 'activeMonitors', 'sourceCounts');
    }
}
