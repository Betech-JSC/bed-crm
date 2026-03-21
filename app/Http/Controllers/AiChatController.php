<?php

namespace App\Http\Controllers;

use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Services\AI\AiGateway;
use App\Services\AI\AiOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AiChatController extends Controller
{
    public function __construct(
        private AiGateway $ai,
        private AiOrchestrator $orchestrator,
    ) {}

    /**
     * Show the AI Chat page.
     */
    public function index(): Response
    {
        $userId = Auth::id();

        $conversations = AiChatConversation::forUser($userId)
            ->orderByDesc('is_pinned')
            ->orderByDesc('last_message_at')
            ->limit(50)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'provider' => $c->provider,
                'model' => $c->model,
                'message_count' => $c->message_count,
                'is_pinned' => $c->is_pinned,
                'last_message_at' => $c->last_message_at?->diffForHumans(),
                'updated_at' => $c->updated_at->format('d/m'),
            ]);

        $configuredProviders = $this->ai->configuredProviders();
        $defaultProvider = $this->ai->getDefaultDriver();

        // AI tool capabilities for frontend
        $tools = $this->orchestrator->getToolRegistry()->toSchema();

        return Inertia::render('AiChat/Index', [
            'conversations' => $conversations,
            'configuredProviders' => $configuredProviders,
            'defaultProvider' => $defaultProvider,
            'availableModels' => $this->ai->availableModels(),
            'aiTools' => $tools,
        ]);
    }

    /**
     * Get messages for a conversation.
     */
    public function show(AiChatConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($conversation);

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'role' => $m->role,
                'content' => $m->content,
                'provider' => $m->provider,
                'model' => $m->model,
                'tokens_used' => $m->tokens_used,
                'tool_calls' => $m->tool_calls,
                'tool_results' => $m->tool_results,
                'created_at' => $m->created_at->format('H:i'),
            ]);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'provider' => $conversation->provider,
                'model' => $conversation->model,
                'system_prompt' => $conversation->system_prompt,
            ],
            'messages' => $messages,
        ]);
    }

    /**
     * Create a new conversation.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'provider' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:100',
            'system_prompt' => 'nullable|string|max:2000',
        ]);

        $conversation = AiChatConversation::create([
            'account_id' => Auth::user()->account_id,
            'user_id' => Auth::id(),
            'title' => $request->input('title', 'Cuộc trò chuyện mới'),
            'provider' => $request->input('provider'),
            'model' => $request->input('model'),
            'system_prompt' => $request->input('system_prompt'),
            'last_message_at' => now(),
        ]);

        return response()->json([
            'id' => $conversation->id,
            'title' => $conversation->title,
        ]);
    }

    /**
     * Send a message and get AI response with tool calling support.
     */
    public function sendMessage(Request $request, AiChatConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($conversation);

        $request->validate([
            'message' => 'required|string|max:10000',
        ]);

        $userMessage = $request->input('message');

        // Save user message
        $userMsg = AiChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userMessage,
        ]);

        // Build conversation history (last 20 messages for context)
        $conversationHistory = $conversation->messages()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();

        try {
            // Use Orchestrator with tool calling
            $options = ['temperature' => 0.7, 'max_tokens' => 4096];

            if ($conversation->model) {
                $options['model'] = $conversation->model;
            }

            $result = $this->orchestrator->chatWithTools(
                $userMessage,
                $conversationHistory,
                $options
            );

            $aiContent = $result['content'];
            $tokensUsed = $result['tokens_used'] ?? 0;
            $provider = $result['provider'] ?? 'unknown';
            $toolCalls = $result['tool_calls'] ?? [];
            $toolResults = $result['tool_results'] ?? [];

            // Save AI response with tool metadata
            $aiMsg = AiChatMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $aiContent,
                'tokens_used' => $tokensUsed,
                'provider' => $provider,
                'model' => $result['model'] ?? null,
                'tool_calls' => !empty($toolCalls) ? json_encode($toolCalls) : null,
                'tool_results' => !empty($toolResults) ? json_encode($toolResults) : null,
            ]);

            // Update conversation stats
            $conversation->update([
                'message_count' => $conversation->message_count + 2,
                'total_tokens' => $conversation->total_tokens + $tokensUsed,
                'last_message_at' => now(),
                'provider' => $provider,
            ]);

            // Auto-generate title from first message
            if ($conversation->message_count <= 2 && $conversation->title === 'Cuộc trò chuyện mới') {
                $title = Str::limit($userMessage, 60, '...');
                $conversation->update(['title' => $title]);
            }

            return response()->json([
                'user_message' => [
                    'id' => $userMsg->id,
                    'role' => 'user',
                    'content' => $userMessage,
                    'created_at' => $userMsg->created_at->format('H:i'),
                ],
                'ai_message' => [
                    'id' => $aiMsg->id,
                    'role' => 'assistant',
                    'content' => $aiContent,
                    'provider' => $provider,
                    'tokens_used' => $tokensUsed,
                    'tool_calls' => $toolCalls,
                    'tool_results' => $toolResults,
                    'created_at' => $aiMsg->created_at->format('H:i'),
                ],
                'conversation' => [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'message_count' => $conversation->message_count,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'AI không thể phản hồi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get AI suggestion for a specific entity (inline suggestion API).
     */
    public function suggest(Request $request): JsonResponse
    {
        $request->validate([
            'entity_type' => 'required|string|in:lead,deal,contact',
            'entity_id' => 'required|integer',
            'suggestion_type' => 'nullable|string',
        ]);

        $result = $this->orchestrator->suggest(
            $request->input('entity_type'),
            $request->input('entity_id'),
            $request->input('suggestion_type', 'general'),
        );

        return response()->json($result);
    }

    /**
     * Draft an email via AI.
     */
    public function draftEmail(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|string',
            'recipient_name' => 'required|string',
            'company' => 'nullable|string',
            'subject' => 'nullable|string',
            'context' => 'nullable|string',
            'tone' => 'nullable|string',
        ]);

        $result = $this->orchestrator->draftEmail($request->all());

        return response()->json($result);
    }

    /**
     * Generate a report via AI.
     */
    public function report(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|string',
            'period' => 'nullable|string',
        ]);

        $result = $this->orchestrator->report(
            $request->input('type', 'summary'),
            ['period' => $request->input('period', 'tuần này')]
        );

        return response()->json($result);
    }

    /**
     * Update conversation (title, pin, etc.).
     */
    public function update(Request $request, AiChatConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($conversation);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'is_pinned' => 'sometimes|boolean',
            'system_prompt' => 'sometimes|nullable|string|max:2000',
            'provider' => 'sometimes|nullable|string|max:50',
            'model' => 'sometimes|nullable|string|max:100',
        ]);

        $conversation->update($data);

        return response()->json(['success' => true]);
    }

    /**
     * Delete a conversation.
     */
    public function destroy(AiChatConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($conversation);
        $conversation->delete();
        return response()->json(['success' => true]);
    }

    private function authorizeConversation(AiChatConversation $conversation): void
    {
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
