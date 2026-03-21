<?php

namespace App\Services\AI\Tools;

use App\Contracts\AiToolInterface;

class DraftEmailTool implements AiToolInterface
{
    public function name(): string { return 'draft_email'; }

    public function description(): string
    {
        return 'Soạn email chuyên nghiệp cho khách hàng. Sử dụng khi user yêu cầu viết email chào hàng, follow-up, hay bất kỳ loại email nào.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'type' => ['type' => 'string', 'enum' => ['chào hàng', 'follow_up', 'cảm ơn', 'giới thiệu', 'đề xuất', 'khác'], 'description' => 'Loại email'],
                'recipient_name' => ['type' => 'string', 'description' => 'Tên người nhận'],
                'recipient_company' => ['type' => 'string', 'description' => 'Công ty người nhận'],
                'subject' => ['type' => 'string', 'description' => 'Chủ đề email'],
                'context' => ['type' => 'string', 'description' => 'Ngữ cảnh thêm: sản phẩm, dịch vụ, mối quan hệ...'],
                'tone' => ['type' => 'string', 'enum' => ['formal', 'friendly', 'persuasive'], 'default' => 'formal'],
            ],
            'required' => ['type', 'recipient_name'],
        ];
    }

    public function execute(array $params, array $context = []): array
    {
        // This tool returns data that will be used by AiOrchestrator's draftEmail
        // The actual email writing is done by the AI in the next step
        return [
            'success' => true,
            'message' => "Đã tạo bản nháp email {$params['type']} cho {$params['recipient_name']}" .
                (isset($params['recipient_company']) ? " ({$params['recipient_company']})" : ''),
            'data' => [
                'type' => 'email_draft',
                'params' => $params,
                'note' => 'Email draft parameters collected. AI will generate the email content.',
            ],
        ];
    }

    public function requiresConfirmation(): bool { return false; }
}
