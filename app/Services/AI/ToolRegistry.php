<?php

namespace App\Services\AI;

use App\Contracts\AiToolInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * ToolRegistry
 * ────────────
 * Registers and manages all AI-callable tools.
 * When AI decides to take an action, it flows through here.
 */
class ToolRegistry
{
    /** @var array<string, AiToolInterface> */
    private array $tools = [];

    /**
     * Register a tool.
     */
    public function register(AiToolInterface $tool): self
    {
        $this->tools[$tool->name()] = $tool;
        return $this;
    }

    /**
     * Get a tool by name.
     */
    public function get(string $name): ?AiToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * Execute a tool call from AI.
     */
    public function execute(string $toolName, array $params): array
    {
        $tool = $this->get($toolName);

        if (!$tool) {
            return [
                'success' => false,
                'message' => "Tool '{$toolName}' not found.",
                'data' => null,
            ];
        }

        $context = [
            'user' => Auth::user(),
            'account_id' => Auth::user()?->account_id,
        ];

        try {
            Log::info("AI ToolRegistry: Executing '{$toolName}'", ['params' => $params]);
            $result = $tool->execute($params, $context);
            Log::info("AI ToolRegistry: '{$toolName}' completed", ['success' => $result['success'] ?? false]);
            return $result;
        } catch (\Exception $e) {
            Log::error("AI ToolRegistry: '{$toolName}' failed", ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => "Tool execution failed: {$e->getMessage()}",
                'data' => null,
            ];
        }
    }

    /**
     * Execute multiple tool calls.
     */
    public function executeMany(array $toolCalls): array
    {
        $results = [];
        foreach ($toolCalls as $call) {
            $results[] = [
                'tool' => $call['tool'],
                'result' => $this->execute($call['tool'], $call['params'] ?? []),
            ];
        }
        return $results;
    }

    /**
     * Get all registered tools.
     *
     * @return AiToolInterface[]
     */
    public function all(): array
    {
        return $this->tools;
    }

    /**
     * Get tools list for AI prompt/function calling schema.
     */
    public function toSchema(): array
    {
        return array_map(fn (AiToolInterface $tool) => [
            'name' => $tool->name(),
            'description' => $tool->description(),
            'parameters' => $tool->parameters(),
            'requires_confirmation' => $tool->requiresConfirmation(),
        ], $this->tools);
    }

    /**
     * Check if a tool exists.
     */
    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }
}
