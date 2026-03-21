<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

/**
 * ResponseParser
 * ──────────────
 * Parses AI responses into structured data.
 * Handles JSON extraction, tool call detection, and fallback parsing.
 */
class ResponseParser
{
    /**
     * Parse AI response and detect tool calls.
     */
    public function parse(string $content): array
    {
        $toolCalls = $this->extractToolCalls($content);
        $cleanContent = $this->removeToolCallBlock($content);

        return [
            'content' => trim($cleanContent),
            'has_tool_calls' => !empty($toolCalls),
            'tool_calls' => $toolCalls,
            'json_data' => $this->extractJson($content),
        ];
    }

    /**
     * Extract JSON from AI response.
     */
    public function extractJson(string $content): ?array
    {
        // Try to parse entire content as JSON
        $decoded = json_decode($content, true);
        if ($decoded !== null) return $decoded;

        // Try to find JSON block in markdown code fence
        if (preg_match('/```(?:json)?\s*\n?([\s\S]*?)\n?```/', $content, $matches)) {
            $decoded = json_decode(trim($matches[1]), true);
            if ($decoded !== null) return $decoded;
        }

        // Try to find raw JSON object
        if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if ($decoded !== null) return $decoded;
        }

        return null;
    }

    /**
     * Extract tool calls from AI response.
     * Looks for: {"tool_calls": [{"tool": "name", "params": {...}}]}
     */
    public function extractToolCalls(string $content): array
    {
        $json = $this->extractJson($content);
        if (!$json) return [];

        // Direct tool_calls format
        if (isset($json['tool_calls']) && is_array($json['tool_calls'])) {
            return array_map(function ($call) {
                return [
                    'tool' => $call['tool'] ?? $call['name'] ?? '',
                    'params' => $call['params'] ?? $call['parameters'] ?? $call['arguments'] ?? [],
                ];
            }, $json['tool_calls']);
        }

        // Single tool call format
        if (isset($json['tool']) && isset($json['params'])) {
            return [['tool' => $json['tool'], 'params' => $json['params']]];
        }

        return [];
    }

    /**
     * Remove tool call JSON block from content, returning human-readable part.
     */
    private function removeToolCallBlock(string $content): string
    {
        // Remove JSON code blocks
        $cleaned = preg_replace('/```(?:json)?\s*\n?\{[\s\S]*?"tool_calls"[\s\S]*?\}\n?```/', '', $content);

        // Remove inline JSON with tool_calls
        $cleaned = preg_replace('/\{[\s\S]*?"tool_calls"[\s\S]*?\}/', '', $cleaned);

        return trim($cleaned);
    }

    /**
     * Parse a structured field from content (e.g., score: 85).
     */
    public function extractField(string $content, string $field): ?string
    {
        $pattern = '/' . preg_quote($field, '/') . '\s*[:=]\s*(.+)/i';
        if (preg_match($pattern, $content, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    /**
     * Parse numeric value from content.
     */
    public function extractNumber(string $content, string $field): ?float
    {
        $value = $this->extractField($content, $field);
        if ($value === null) return null;

        preg_match('/[\d.]+/', $value, $matches);
        return isset($matches[0]) ? (float) $matches[0] : null;
    }
}
