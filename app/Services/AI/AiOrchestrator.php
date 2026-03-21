<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

/**
 * AiOrchestrator
 * ──────────────
 * The brain of the AI-native CRM.
 * Coordinates context, prompts, tools, and AI calls into intelligent workflows.
 *
 * Usage:
 *   $orchestrator = app(AiOrchestrator::class);
 *
 *   // Simple chat with CRM context
 *   $result = $orchestrator->chat('Phân tích leads tháng này');
 *
 *   // Chat with tool execution
 *   $result = $orchestrator->chatWithTools('Tạo lead mới cho Công ty ABC SĐT 0912345678');
 *
 *   // Inline suggestion for a specific entity
 *   $result = $orchestrator->suggest('lead', $leadId, 'next_action');
 *
 *   // Generate report
 *   $result = $orchestrator->report('weekly_sales');
 */
class AiOrchestrator
{
    public function __construct(
        private AiGateway $gateway,
        private ContextBuilder $context,
        private PromptRegistry $prompts,
        private ResponseParser $parser,
        private ToolRegistry $tools,
        private MemoryManager $memory,
    ) {}

    /**
     * Chat with full CRM context (no tool execution).
     */
    public function chat(string $message, array $options = []): array
    {
        $crmContext = json_encode($this->context->forCurrentUser(), JSON_UNESCAPED_UNICODE);
        $memoryContext = json_encode($this->memory->getContext(), JSON_UNESCAPED_UNICODE);

        $prompt = $this->prompts->get('general.with_context', [
            'crm_context' => $crmContext . "\n## User Memory\n" . $memoryContext,
            'user_message' => $message,
        ]);

        $systemPrompt = $options['system_prompt']
            ?? $this->prompts->get('system.default');

        $result = $this->gateway
            ->withFallback(['gemini', 'openai', 'claude', 'deepseek', 'groq'])
            ->chat($prompt, array_merge([
                'system_prompt' => $systemPrompt,
                'temperature' => 0.7,
                'max_tokens' => 4096,
            ], $options));

        $parsed = $this->parser->parse($result['content']);

        return [
            'content' => $parsed['content'] ?: $result['content'],
            'json_data' => $parsed['json_data'],
            'provider' => $result['metadata']['provider'] ?? 'unknown',
            'tokens_used' => $result['metadata']['tokens_used'] ?? 0,
        ];
    }

    /**
     * Chat with tool calling enabled.
     * AI can decide to execute CRM actions.
     */
    public function chatWithTools(string $message, array $conversationHistory = [], array $options = []): array
    {
        $crmContext = json_encode($this->context->forCurrentUser(), JSON_UNESCAPED_UNICODE);
        $memoryContext = json_encode($this->memory->getContext(), JSON_UNESCAPED_UNICODE);
        $systemPrompt = $this->prompts->systemWithTools(array_values($this->tools->all()));

        // Build conversation prompt
        $conversationPrompt = '';
        foreach ($conversationHistory as $msg) {
            $role = $msg['role'] === 'user' ? 'User' : 'Assistant';
            $conversationPrompt .= "{$role}: {$msg['content']}\n";
        }

        $fullPrompt = "## Context CRM\n{$crmContext}\n\n## User Memory\n{$memoryContext}\n\n{$conversationPrompt}\nUser: {$message}";

        // Track action
        $this->memory->trackAction('chat_with_tools');

        $result = $this->gateway
            ->withFallback(['gemini', 'openai', 'claude', 'deepseek', 'groq'])
            ->chat($fullPrompt, array_merge([
                'system_prompt' => $systemPrompt,
                'temperature' => 0.5,
                'max_tokens' => 4096,
            ], $options));

        $parsed = $this->parser->parse($result['content']);

        // Execute tool calls if any
        $toolResults = [];
        if ($parsed['has_tool_calls']) {
            $toolResults = $this->tools->executeMany($parsed['tool_calls']);

            // After executing tools, generate a summary response
            $toolSummary = $this->summarizeToolResults($toolResults);
            $parsed['content'] = ($parsed['content'] ? $parsed['content'] . "\n\n" : '') . $toolSummary;
        }

        return [
            'content' => $parsed['content'] ?: $result['content'],
            'tool_calls' => $parsed['tool_calls'],
            'tool_results' => $toolResults,
            'json_data' => $parsed['json_data'],
            'provider' => $result['metadata']['provider'] ?? 'unknown',
            'tokens_used' => $result['metadata']['tokens_used'] ?? 0,
        ];
    }

    /**
     * Get AI suggestion for a specific entity.
     */
    public function suggest(string $entityType, int $entityId, string $suggestionType = 'general'): array
    {
        $entityContext = $this->context->forEntity($entityType, $entityId);

        $promptKey = match ("{$entityType}.{$suggestionType}") {
            'lead.score' => 'lead.score',
            'lead.enrich' => 'lead.enrich',
            'deal.analyze' => 'deal.analyze',
            'deal.next_action' => 'deal.next_action',
            default => null,
        };

        if (!$promptKey) {
            // Generic suggestion
            $prompt = "Phân tích {$entityType} này và đưa ra đề xuất:\n" . json_encode($entityContext['focus_entity'] ?? [], JSON_UNESCAPED_UNICODE);
        } else {
            $dataKey = $entityType . '_data';
            $prompt = $this->prompts->get($promptKey, [
                $dataKey => json_encode($entityContext['focus_entity'] ?? [], JSON_UNESCAPED_UNICODE),
                'crm_context' => json_encode($entityContext['crm_summary'] ?? [], JSON_UNESCAPED_UNICODE),
            ]);
        }

        try {
            $result = $this->gateway
                ->withFallback(['gemini', 'openai', 'claude', 'deepseek', 'groq'])
                ->chat($prompt, [
                    'system_prompt' => $this->prompts->get('system.default'),
                    'temperature' => 0.5,
                    'max_tokens' => 2048,
                    'response_format' => 'json',
                ]);

            $parsed = $this->parser->parse($result['content']);

            return [
                'success' => true,
                'suggestion' => $parsed['json_data'] ?? $parsed['content'],
                'content' => $parsed['content'],
                'provider' => $result['metadata']['provider'] ?? 'unknown',
            ];
        } catch (\Exception $e) {
            Log::warning('AiOrchestrator: Suggestion failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'suggestion' => null,
                'content' => 'AI suggestion unavailable.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Draft an email using AI.
     */
    public function draftEmail(array $params): array
    {
        $prompt = $this->prompts->get('email.draft', [
            'email_type' => $params['type'] ?? 'chào hàng',
            'recipient_name' => $params['recipient_name'] ?? 'Quý khách',
            'recipient_company' => $params['company'] ?? '',
            'subject' => $params['subject'] ?? '',
            'tone' => $params['tone'] ?? 'chuyên nghiệp',
            'context' => $params['context'] ?? '',
        ]);

        try {
            $result = $this->gateway
                ->withFallback(['gemini', 'openai', 'claude'])
                ->chat($prompt, [
                    'system_prompt' => 'Bạn là chuyên gia viết email kinh doanh. Trả về JSON format.',
                    'temperature' => 0.7,
                    'max_tokens' => 2048,
                ]);

            $parsed = $this->parser->parse($result['content']);

            return [
                'success' => true,
                'email' => $parsed['json_data'] ?? ['body' => $parsed['content']],
                'provider' => $result['metadata']['provider'] ?? 'unknown',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'email' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate a report.
     */
    public function report(string $type, array $params = []): array
    {
        $crmData = $this->context->forCurrentUser();

        $prompt = $this->prompts->get('report.summary', [
            'crm_data' => json_encode($crmData, JSON_UNESCAPED_UNICODE),
            'period' => $params['period'] ?? 'tuần này',
        ]);

        try {
            $result = $this->gateway
                ->withFallback(['gemini', 'openai', 'claude'])
                ->chat($prompt, [
                    'system_prompt' => $this->prompts->get('system.default'),
                    'temperature' => 0.5,
                    'max_tokens' => 4096,
                ]);

            return [
                'success' => true,
                'report' => $result['content'],
                'provider' => $result['metadata']['provider'] ?? 'unknown',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'report' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Summarize tool execution results.
     */
    private function summarizeToolResults(array $toolResults): string
    {
        $summaries = [];
        foreach ($toolResults as $tr) {
            $tool = $tr['tool'];
            $result = $tr['result'];
            $status = ($result['success'] ?? false) ? '✅' : '❌';
            $message = $result['message'] ?? 'Completed';
            $summaries[] = "{$status} **{$tool}**: {$message}";
        }
        return "### Kết quả thực thi\n" . implode("\n", $summaries);
    }

    // ── Accessors ──

    public function getToolRegistry(): ToolRegistry
    {
        return $this->tools;
    }

    public function getContextBuilder(): ContextBuilder
    {
        return $this->context;
    }

    public function getMemoryManager(): MemoryManager
    {
        return $this->memory;
    }
}
