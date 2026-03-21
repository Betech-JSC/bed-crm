<?php

namespace App\Contracts;

/**
 * AiToolInterface
 * ────────────────
 * Contract for all AI-callable tools.
 * Each tool represents an action the AI can execute (create lead, send email, etc.)
 */
interface AiToolInterface
{
    /**
     * Tool name (snake_case). Used in function calling.
     */
    public function name(): string;

    /**
     * Human-readable description for the AI.
     */
    public function description(): string;

    /**
     * Tool parameters schema (JSON Schema format).
     * The AI uses this to build properly structured calls.
     */
    public function parameters(): array;

    /**
     * Execute the tool with given parameters.
     *
     * @param  array  $params  Validated parameters from AI
     * @param  array  $context ['user' => User, 'account_id' => int, ...]
     * @return array  ['success' => bool, 'message' => string, 'data' => mixed]
     */
    public function execute(array $params, array $context = []): array;

    /**
     * Whether this tool requires user confirmation before execution.
     */
    public function requiresConfirmation(): bool;
}
