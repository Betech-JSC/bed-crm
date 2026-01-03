<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatWidget;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function __construct(
        private ChatService $chatService
    ) {
    }

    /**
     * Initialize chat conversation
     */
    public function init(Request $request, string $widgetKey): JsonResponse
    {
        \Log::info('Chat Widget Init Request', [
            'widget_key' => $widgetKey,
            'headers' => $request->headers->all(),
            'input' => $request->all(),
        ]);
        
        // Check if this is a preview request (from preview page)
        $isPreview = $this->isPreviewRequest($request);
        
        \Log::info('Preview detection', ['is_preview' => $isPreview]);
        
        $query = ChatWidget::where('widget_key', $widgetKey);
        
        if ($isPreview) {
            // Preview mode - allow inactive widgets
            $widget = $query->firstOrFail();
            \Log::info('Preview mode - widget found', ['widget_id' => $widget->id, 'is_active' => $widget->is_active]);
        } else {
            // Public mode - only active widgets
            $widget = $query->where('is_active', true)->firstOrFail();
            \Log::info('Public mode - active widget found', ['widget_id' => $widget->id]);
        }

        // Validate domain (skip for preview)
        if (!$isPreview) {
            $origin = $request->header('Origin') ?? $request->header('Referer');
            if ($origin && !$this->isDomainAllowed($widget, $origin)) {
                \Log::warning('Domain not allowed', ['origin' => $origin]);
                return response()->json(['error' => 'Domain not allowed'], 403);
            }
        }

        // Generate visitor and session IDs
        $visitorId = $request->input('visitor_id') ?? $this->generateVisitorId();
        $sessionId = Str::uuid()->toString();

        // Check rate limit
        $rateLimitKey = "chat:visitor:{$visitorId}:{$widget->id}";
        if (RateLimiter::tooManyAttempts($rateLimitKey, $widget->rate_limit_per_hour)) {
            return response()->json([
                'error' => 'Rate limit exceeded. Please try again later.',
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 3600); // 1 hour

        // Create or get conversation
        $conversation = ChatConversation::firstOrCreate(
            [
                'account_id' => $widget->account_id,
                'widget_id' => $widget->id,
                'visitor_id' => $visitorId,
                'status' => 'active',
            ],
            [
                'session_id' => $sessionId,
                'visitor_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer_url' => $request->input('referrer'),
                'page_url' => $request->input('page_url'),
                'last_message_at' => now(),
            ]
        );

        return response()->json([
            'conversation_id' => $conversation->id,
            'visitor_id' => $visitorId,
            'session_id' => $conversation->session_id,
            'welcome_message' => $widget->welcome_message,
            'widget_settings' => [
                'primary_color' => $widget->primary_color,
                'position' => $widget->position,
                'collect_email' => $widget->collect_email,
                'collect_phone' => $widget->collect_phone,
                'banners' => $widget->banners ?? [],
                'show_banners' => $widget->show_banners ?? true,
                'banner_rotation_seconds' => $widget->banner_rotation_seconds ?? 5,
            ],
        ]);
    }

    /**
     * Send message
     */
    public function sendMessage(Request $request, string $widgetKey): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['required', 'integer'],
            'message' => ['required', 'string', 'max:2000'],
            'visitor_id' => ['required', 'string'],
            'visitor_name' => ['nullable', 'string', 'max:255'],
            'visitor_email' => ['nullable', 'email', 'max:255'],
            'visitor_phone' => ['nullable', 'string', 'max:50'],
        ]);

        // Check if this is a preview request
        $isPreview = $this->isPreviewRequest($request);
        
        $query = ChatWidget::where('widget_key', $widgetKey);
        if ($isPreview) {
            $widget = $query->firstOrFail();
        } else {
            $widget = $query->where('is_active', true)->firstOrFail();
        }

        $conversation = ChatConversation::where('id', $validated['conversation_id'])
            ->where('widget_id', $widget->id)
            ->where('visitor_id', $validated['visitor_id'])
            ->where('status', 'active')
            ->firstOrFail();

        // Update visitor info if provided
        if ($validated['visitor_name'] || $validated['visitor_email'] || $validated['visitor_phone']) {
            $this->chatService->updateVisitorInfo(
                $conversation,
                $validated['visitor_name'] ?? null,
                $validated['visitor_email'] ?? null,
                $validated['visitor_phone'] ?? null
            );
        }

        // Process message
        try {
            $assistantMessage = $this->chatService->processMessage(
                $conversation,
                $validated['message']
            );

            return response()->json([
                'message_id' => $assistantMessage->id,
                'content' => $assistantMessage->content,
                'role' => $assistantMessage->role,
                'created_at' => $assistantMessage->created_at->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('Chat message processing failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to process message. Please try again.',
            ], 500);
        }
    }

    /**
     * Get conversation history
     */
    public function getHistory(Request $request, string $widgetKey): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['required', 'integer'],
            'visitor_id' => ['required', 'string'],
        ]);

        $isPreview = $this->isPreviewRequest($request);
        $query = ChatWidget::where('widget_key', $widgetKey);
        $widget = $isPreview ? $query->firstOrFail() : $query->where('is_active', true)->firstOrFail();

        $conversation = ChatConversation::where('id', $validated['conversation_id'])
            ->where('widget_id', $widget->id)
            ->where('visitor_id', $validated['visitor_id'])
            ->firstOrFail();

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn($msg) => [
                'id' => $msg->id,
                'role' => $msg->role,
                'content' => $msg->content,
                'created_at' => $msg->created_at->toIso8601String(),
            ]);

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages' => $messages,
        ]);
    }

    /**
     * Close conversation
     */
    public function closeConversation(Request $request, string $widgetKey): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['required', 'integer'],
            'visitor_id' => ['required', 'string'],
        ]);

        $isPreview = $this->isPreviewRequest($request);
        $query = ChatWidget::where('widget_key', $widgetKey);
        $widget = $isPreview ? $query->firstOrFail() : $query->where('is_active', true)->firstOrFail();

        $conversation = ChatConversation::where('id', $validated['conversation_id'])
            ->where('widget_id', $widget->id)
            ->where('visitor_id', $validated['visitor_id'])
            ->firstOrFail();

        $conversation->close();

        return response()->json(['success' => true]);
    }

    /**
     * Check if domain is allowed
     */
    private function isDomainAllowed(ChatWidget $widget, string $origin): bool
    {
        if (empty($widget->allowed_domains)) {
            return true;
        }

        $parsedUrl = parse_url($origin);
        $domain = $parsedUrl['host'] ?? null;

        if (!$domain) {
            return false;
        }

        return $widget->isDomainAllowed($domain);
    }

    /**
     * Generate unique visitor ID
     */
    private function generateVisitorId(): string
    {
        return 'visitor_' . Str::random(32);
    }

    /**
     * Check if request is from preview page
     */
    private function isPreviewRequest(Request $request): bool
    {
        $referer = $request->header('Referer') ?? '';
        $origin = $request->header('Origin') ?? '';
        $pageUrl = $request->input('page_url', '');
        
        // Check multiple sources for preview detection
        $isPreview = str_contains($referer, '/chat-widgets/') && str_contains($referer, '/preview')
            || str_contains($origin, '/chat-widgets/') && str_contains($origin, '/preview')
            || str_contains($pageUrl, '/chat-widgets/') && str_contains($pageUrl, '/preview');
        
        \Log::info('Preview detection', [
            'referer' => $referer,
            'origin' => $origin,
            'page_url' => $pageUrl,
            'is_preview' => $isPreview
        ]);
        
        return $isPreview;
    }
}
