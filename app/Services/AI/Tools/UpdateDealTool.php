<?php

namespace App\Services\AI\Tools;

use App\Contracts\AiToolInterface;
use App\Models\Deal;
use App\Models\Lead;

class UpdateDealTool implements AiToolInterface
{
    public function name(): string { return 'update_deal'; }

    public function description(): string
    {
        return 'Cập nhật thông tin deal (giai đoạn, giá trị, ghi chú). Sử dụng khi user muốn thay đổi deal.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'deal_id' => ['type' => 'integer', 'description' => 'ID của deal cần update'],
                'stage' => ['type' => 'string', 'enum' => ['prospecting', 'qualification', 'proposal', 'negotiation', 'closing']],
                'value' => ['type' => 'number', 'description' => 'Giá trị deal (VND)'],
                'status' => ['type' => 'string', 'enum' => ['open', 'won', 'lost']],
                'notes' => ['type' => 'string', 'description' => 'Ghi chú cập nhật'],
                'next_steps' => ['type' => 'string', 'description' => 'Bước tiếp theo'],
            ],
            'required' => ['deal_id'],
        ];
    }

    public function execute(array $params, array $context = []): array
    {
        $deal = Deal::where('account_id', $context['account_id'])->find($params['deal_id']);

        if (!$deal) {
            return ['success' => false, 'message' => "Deal #{$params['deal_id']} không tồn tại.", 'data' => null];
        }

        $updateData = [];
        if (isset($params['stage'])) {
            $updateData['stage'] = $params['stage'];
            $updateData['stage_changed_at'] = now();
        }
        if (isset($params['value'])) $updateData['value'] = $params['value'];
        if (isset($params['status'])) $updateData['status'] = $params['status'];
        if (isset($params['notes'])) $updateData['notes'] = $params['notes'];
        if (isset($params['next_steps'])) $updateData['next_steps'] = [$params['next_steps']];

        $deal->update($updateData);

        return [
            'success' => true,
            'message' => "Đã cập nhật deal \"{$deal->title}\"" . (isset($params['stage']) ? " → stage: {$params['stage']}" : ''),
            'data' => ['id' => $deal->id, 'title' => $deal->title],
        ];
    }

    public function requiresConfirmation(): bool { return true; }
}
