<?php

namespace App\Services\AI\Tools;

use App\Contracts\AiToolInterface;
use App\Models\Lead;

class CreateLeadTool implements AiToolInterface
{
    public function name(): string { return 'create_lead'; }

    public function description(): string
    {
        return 'Tạo lead mới trong CRM. Sử dụng khi user yêu cầu thêm khách hàng tiềm năng.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string', 'description' => 'Tên lead/khách hàng'],
                'email' => ['type' => 'string', 'description' => 'Email liên hệ'],
                'phone' => ['type' => 'string', 'description' => 'Số điện thoại'],
                'company' => ['type' => 'string', 'description' => 'Tên công ty'],
                'industry' => ['type' => 'string', 'description' => 'Ngành nghề'],
                'source' => ['type' => 'string', 'enum' => ['website', 'referral', 'social', 'email', 'phone', 'other']],
                'notes' => ['type' => 'string', 'description' => 'Ghi chú'],
            ],
            'required' => ['name'],
        ];
    }

    public function execute(array $params, array $context = []): array
    {
        $lead = Lead::create([
            'account_id' => $context['account_id'] ?? $context['user']?->account_id,
            'name' => $params['name'],
            'email' => $params['email'] ?? null,
            'phone' => $params['phone'] ?? null,
            'company' => $params['company'] ?? null,
            'industry' => $params['industry'] ?? null,
            'source' => $params['source'] ?? 'other',
            'status' => 'new',
            'notes' => $params['notes'] ?? null,
            'assigned_to' => $context['user']?->id,
        ]);

        return [
            'success' => true,
            'message' => "Đã tạo lead \"{$lead->name}\" (ID: {$lead->id})",
            'data' => ['id' => $lead->id, 'name' => $lead->name],
        ];
    }

    public function requiresConfirmation(): bool { return false; }
}
