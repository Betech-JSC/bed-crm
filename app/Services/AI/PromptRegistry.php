<?php

namespace App\Services\AI;

/**
 * PromptRegistry
 * ──────────────
 * Centralized prompt management. All AI prompts in one place.
 * Supports variables, templates, and context injection.
 */
class PromptRegistry
{
    private array $prompts = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    /**
     * Register a prompt template.
     */
    public function register(string $key, string $template, array $defaults = []): void
    {
        $this->prompts[$key] = [
            'template' => $template,
            'defaults' => $defaults,
        ];
    }

    /**
     * Get a prompt, replacing variables.
     */
    public function get(string $key, array $variables = []): string
    {
        if (!isset($this->prompts[$key])) {
            throw new \InvalidArgumentException("Prompt '{$key}' not found in registry.");
        }

        $prompt = $this->prompts[$key];
        $vars = array_merge($prompt['defaults'], $variables);
        $template = $prompt['template'];

        foreach ($vars as $k => $v) {
            if (is_array($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE);
            $template = str_replace("{{$k}}", (string) $v, $template);
        }

        return $template;
    }

    /**
     * Build system prompt with tools description.
     */
    public function systemWithTools(array $tools): string
    {
        $toolDescriptions = [];
        foreach ($tools as $tool) {
            $params = json_encode($tool->parameters(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $toolDescriptions[] = "- **{$tool->name()}**: {$tool->description()}\n  Parameters: {$params}";
        }

        $toolsList = implode("\n\n", $toolDescriptions);

        return $this->get('system.with_tools', ['tools_list' => $toolsList]);
    }

    /**
     * Register all default prompts.
     */
    private function registerDefaults(): void
    {
        // ── System Prompts ──
        $this->register('system.default', <<<'PROMPT'
Bạn là trợ lý AI thông minh của BED CRM — một hệ thống quản lý kinh doanh toàn diện.

## Vai trò
- Hỗ trợ quản lý leads, deals, contacts, marketing
- Phân tích dữ liệu kinh doanh, đề xuất chiến lược
- Viết email, báo cáo, đề xuất chuyên nghiệp
- Trả lời bằng tiếng Việt, ngắn gọn, chuyên nghiệp

## Quy tắc
- Luôn sử dụng dữ liệu CRM thực khi có context
- Khi không chắc chắn, hỏi lại thay vì đoán
- Format response rõ ràng với headers, bullet points
- Luôn đề xuất next actions cụ thể
PROMPT);

        $this->register('system.with_tools', <<<'PROMPT'
Bạn là trợ lý AI thông minh của BED CRM với khả năng thực thi hành động.

## Vai trò
- Quản lý leads, deals, contacts, marketing trong CRM
- Phân tích dữ liệu kinh doanh
- Thực hiện các thao tác khi user yêu cầu

## Tools có sẵn
{tools_list}

## Quy tắc sử dụng Tools
- Khi user yêu cầu tạo/sửa/xóa dữ liệu, hãy gọi tool tương ứng
- Trả về JSON format: {"tool_calls": [{"tool": "tool_name", "params": {...}}]}
- Nếu cần xác nhận, hỏi user trước khi gọi tool
- Sau khi thực thi tool, thông báo kết quả cho user
- Nếu không cần gọi tool, trả lời bình thường
- Luôn trả lời bằng tiếng Việt
PROMPT);

        // ── Lead Prompts ──
        $this->register('lead.score', <<<'PROMPT'
Đánh giá lead score (0-100) cho lead sau dựa trên thông tin:

{lead_data}

Tiêu chí:
- Thông tin liên hệ đầy đủ (20 điểm)
- Công ty/ngành phù hợp (20 điểm)
- Nguồn lead chất lượng (15 điểm)
- Engagement level (20 điểm)
- Potential value (25 điểm)

Trả về JSON: {"score": <0-100>, "reasons": ["..."], "next_action": "..."}
PROMPT);

        $this->register('lead.enrich', <<<'PROMPT'
Phân tích và bổ sung thông tin cho lead:
{lead_data}

Gợi ý:
1. Đánh giá chất lượng thông tin hiện có
2. Đề xuất thông tin cần bổ sung
3. Gợi ý cách tiếp cận phù hợp
4. Đề xuất nhân viên sales phù hợp

Trả về JSON: {"quality": "high|medium|low", "missing_info": [...], "approach": "...", "priority": "hot|warm|cold"}
PROMPT);

        // ── Deal Prompts ──
        $this->register('deal.analyze', <<<'PROMPT'
Phân tích deal:
{deal_data}

Yêu cầu:
1. Đánh giá khả năng win (%)
2. Rủi ro chính
3. Đề xuất next steps
4. Chiến lược đàm phán

Trả về JSON: {"win_probability": <0-100>, "risks": [...], "next_steps": [...], "strategy": "..."}
PROMPT);

        $this->register('deal.next_action', <<<'PROMPT'
Deal hiện tại:
{deal_data}

Context CRM:
{crm_context}

Đề xuất 3 hành động tiếp theo quan trọng nhất, sắp xếp theo priority.
Trả về JSON: {"actions": [{"action": "...", "priority": "high|medium|low", "reason": "...", "deadline": "..."}]}
PROMPT);

        // ── Email Prompts ──
        $this->register('email.draft', <<<'PROMPT'
Viết email {email_type} cho:
- Người nhận: {recipient_name} ({recipient_company})
- Chủ đề: {subject}
- Tone: {tone}
- Context: {context}

Viết email chuyên nghiệp, ngắn gọn, bằng tiếng Việt.
Trả về JSON: {"subject": "...", "body": "...", "follow_up_suggestion": "..."}
PROMPT, ['tone' => 'chuyên nghiệp', 'email_type' => 'chào hàng']);

        // ── Report Prompts ──  
        $this->register('report.summary', <<<'PROMPT'
Dữ liệu CRM:
{crm_data}

Khoảng thời gian: {period}

Tạo báo cáo tóm tắt gồm:
1. Tổng quan hiệu suất
2. Highlights & Lowlights
3. So sánh với kỳ trước (nếu có)
4. Đề xuất cải thiện

Format ngắn gọn, dùng emoji và bullet points.
PROMPT);

        // ── General ──
        $this->register('general.with_context', <<<'PROMPT'
## Context CRM hiện tại
{crm_context}

## Yêu cầu từ user
{user_message}

Trả lời dựa trên context CRM thực tế. Nếu câu hỏi liên quan đến data CRM, sử dụng số liệu thực.
PROMPT);
    }

    /**
     * List all registered prompt keys.
     */
    public function list(): array
    {
        return array_keys($this->prompts);
    }
}
