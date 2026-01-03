<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatWidget;
use App\Models\Contact;
use App\Models\Lead;
use App\Services\RAGService;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class ChatService
{
    public function __construct(
        private RAGService $ragService
    ) {
    }
    /**
     * Process user message and generate AI response
     */
    public function processMessage(
        ChatConversation $conversation,
        string $userMessage,
        ?array $metadata = null
    ): ChatMessage {
        $widget = $conversation->widget;
        $startTime = microtime(true);

        // Create user message
        $userMsg = ChatMessage::create([
            'account_id' => $conversation->account_id,
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userMessage,
            'metadata' => $metadata,
            'status' => 'sent',
        ]);

        // Update conversation
        $conversation->increment('message_count');
        $conversation->update(['last_message_at' => now()]);

        try {
            // Get RAG context from documents
            $ragContext = $this->ragService->getRAGContext($widget, $userMessage, 3);

            // Build messages array for OpenAI
            $messages = $this->buildMessagesArray($conversation, $widget, $ragContext);

            // Call OpenAI API
            $response = OpenAI::chat()->create([
                'model' => $widget->ai_model,
                'messages' => $messages,
                'temperature' => (float) $widget->temperature,
                'max_tokens' => $widget->max_tokens,
            ]);

            $assistantMessage = $response->choices[0]->message->content;
            $usage = $response->usage;
            $responseTime = (microtime(true) - $startTime) * 1000;

            // Calculate cost (approximate, adjust based on model pricing)
            $cost = $this->calculateCost($widget->ai_model, $usage->totalTokens);

            // Create assistant message
            $assistantMsg = ChatMessage::create([
                'account_id' => $conversation->account_id,
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $assistantMessage,
                'ai_metadata' => [
                    'model' => $widget->ai_model,
                    'prompt_tokens' => $usage->promptTokens,
                    'completion_tokens' => $usage->completionTokens,
                    'total_tokens' => $usage->totalTokens,
                ],
                'tokens_used' => $usage->totalTokens,
                'cost' => $cost,
                'response_time_ms' => (int) $responseTime,
                'status' => 'sent',
            ]);

            return $assistantMsg;
        } catch (\Exception $e) {
            Log::error('Chat AI Error', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            // Create error message
            ChatMessage::create([
                'account_id' => $conversation->account_id,
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => 'I apologize, but I encountered an error. Please try again later.',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Build messages array for OpenAI API
     */
    private function buildMessagesArray(ChatConversation $conversation, ChatWidget $widget, string $ragContext = ''): array
    {
        $messages = [];

        // Add system prompt
        if ($widget->system_prompt) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->buildSystemPrompt($conversation, $widget, $ragContext),
            ];
        }

        // Add conversation history (last 20 messages for context)
        $history = $conversation->messages()
            ->where('role', '!=', 'system')
            ->orderBy('created_at')
            ->limit(20)
            ->get();

        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg->role,
                'content' => $msg->content,
            ];
        }

        return $messages;
    }

    /**
     * Build system prompt with context
     */
    private function buildSystemPrompt(ChatConversation $conversation, ChatWidget $widget, string $ragContext = ''): string
    {
        $prompt = $widget->system_prompt;

        // Add RAG context from documents
        if (!empty($ragContext)) {
            $prompt .= "\n\n=== KNOWLEDGE BASE / REFERENCE DOCUMENTS ===\n";
            $prompt .= "Use the following information from our knowledge base to answer questions accurately:\n\n";
            $prompt .= $ragContext;
            $prompt .= "\n\n=== END KNOWLEDGE BASE ===\n";
            $prompt .= "\nIMPORTANT: When answering questions, prioritize information from the knowledge base above. ";
            $prompt .= "If the question relates to information in the knowledge base, use that information to provide accurate answers. ";
            $prompt .= "If the information is not in the knowledge base, you can use your general knowledge but mention that you're providing general information.";
        }

        // Add visitor context
        $context = [];
        if ($conversation->visitor_name) {
            $context[] = "Visitor name: {$conversation->visitor_name}";
        }
        if ($conversation->visitor_email) {
            $context[] = "Visitor email: {$conversation->visitor_email}";
        }

        if (!empty($context)) {
            $prompt .= "\n\nVisitor Information:\n" . implode("\n", $context);
        }

        return $prompt;
    }

    /**
     * Calculate cost based on model and tokens
     */
    private function calculateCost(string $model, int $tokens): float
    {
        // Pricing per 1K tokens (as of 2024, adjust as needed)
        $pricing = [
            'gpt-4o' => ['input' => 0.005, 'output' => 0.015],
            'gpt-4o-mini' => ['input' => 0.00015, 'output' => 0.0006],
            'gpt-4-turbo' => ['input' => 0.01, 'output' => 0.03],
            'gpt-3.5-turbo' => ['input' => 0.0005, 'output' => 0.0015],
        ];

        $modelPricing = $pricing[$model] ?? $pricing['gpt-4o-mini'];
        
        // Approximate: assume 70% input, 30% output
        $inputTokens = (int) ($tokens * 0.7);
        $outputTokens = $tokens - $inputTokens;

        $cost = ($inputTokens / 1000 * $modelPricing['input']) + 
                ($outputTokens / 1000 * $modelPricing['output']);

        return round($cost, 6);
    }

    /**
     * Create or link lead from conversation
     */
    public function createLeadFromConversation(ChatConversation $conversation): ?Lead
    {
        if (!$conversation->widget->auto_create_leads) {
            return null;
        }

        if ($conversation->lead_id) {
            return $conversation->lead;
        }

        // Check if contact exists or create one
        $contact = null;
        if ($conversation->visitor_email) {
            $contact = Contact::where('account_id', $conversation->account_id)
                ->where('email', $conversation->visitor_email)
                ->first();

            // Create contact if doesn't exist
            if (!$contact && $conversation->visitor_name) {
                $nameParts = explode(' ', trim($conversation->visitor_name), 2);
                $contact = Contact::create([
                    'account_id' => $conversation->account_id,
                    'first_name' => $nameParts[0] ?? 'Chat',
                    'last_name' => $nameParts[1] ?? 'Visitor',
                    'email' => $conversation->visitor_email,
                    'phone' => $conversation->visitor_phone,
                ]);
            }
        }

        // Create lead
        $lead = Lead::create([
            'account_id' => $conversation->account_id,
            'name' => $conversation->visitor_name ?? 'Chat Visitor',
            'email' => $conversation->visitor_email,
            'phone' => $conversation->visitor_phone,
            'source' => Lead::SOURCE_WEBSITE,
            'status' => Lead::STATUS_NEW,
            'notes' => "Created from chat conversation. Page: {$conversation->page_url}",
        ]);

        // Link contact to conversation
        if ($contact) {
            $conversation->update([
                'lead_id' => $lead->id,
                'contact_id' => $contact->id,
            ]);
        } else {
            $conversation->update(['lead_id' => $lead->id]);
        }

        return $lead;
    }

    /**
     * Update visitor information
     */
    public function updateVisitorInfo(
        ChatConversation $conversation,
        ?string $name = null,
        ?string $email = null,
        ?string $phone = null
    ): void {
        $updates = [];
        
        if ($name && !$conversation->visitor_name) {
            $updates['visitor_name'] = $name;
        }
        
        if ($email && !$conversation->visitor_email) {
            $updates['visitor_email'] = $email;
        }
        
        if ($phone && !$conversation->visitor_phone) {
            $updates['visitor_phone'] = $phone;
        }

        if (!empty($updates)) {
            $conversation->update($updates);

            // Auto-create lead if enabled
            if ($conversation->widget->auto_create_leads && ($email || $phone)) {
                $this->createLeadFromConversation($conversation);
            }
        }
    }
}

