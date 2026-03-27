<?php

namespace Database\Seeders;

use App\Models\AiKnowledgeBase;
use App\Models\AiKnowledgeDocument;
use App\Models\AiTrainingSet;
use App\Models\AiAgent;
use App\Models\AiAgentConversation;
use App\Models\AiDataSyncLog;
use Illuminate\Database\Seeder;

class AiDataHubSeeder extends Seeder
{
    public function run(): void
    {
        $accountId = 1;
        $userId = 1;

        // ── 1. Knowledge Bases ──
        $salesKb = AiKnowledgeBase::create([
            'account_id' => $accountId, 'name' => 'Sales Knowledge', 'type' => 'sales',
            'description' => 'Toàn bộ dữ liệu sales: playbook, pipeline patterns, win/loss analysis, objection handling.',
            'status' => 'ready', 'created_by' => $userId,
            'stats' => ['documents' => 8, 'chunks' => 145, 'indexed' => 8],
        ]);

        $supportKb = AiKnowledgeBase::create([
            'account_id' => $accountId, 'name' => 'Support & FAQ', 'type' => 'support',
            'description' => 'FAQ, troubleshooting guides, SLA policies, ticket resolution patterns.',
            'status' => 'ready', 'created_by' => $userId,
            'stats' => ['documents' => 12, 'chunks' => 280, 'indexed' => 12],
        ]);

        $productKb = AiKnowledgeBase::create([
            'account_id' => $accountId, 'name' => 'Product Catalog', 'type' => 'product',
            'description' => 'Sản phẩm, dịch vụ, pricing, specs, comparison.',
            'status' => 'ready', 'created_by' => $userId,
            'stats' => ['documents' => 5, 'chunks' => 90, 'indexed' => 5],
        ]);

        $hrKb = AiKnowledgeBase::create([
            'account_id' => $accountId, 'name' => 'HR & Company', 'type' => 'hr',
            'description' => 'Chính sách nhân sự, quy trình nội bộ, culture values, onboarding.',
            'status' => 'ready', 'created_by' => $userId,
            'stats' => ['documents' => 6, 'chunks' => 120, 'indexed' => 6],
        ]);

        $contentKb = AiKnowledgeBase::create([
            'account_id' => $accountId, 'name' => 'Content & SEO', 'type' => 'general',
            'description' => 'Blog posts, SEO data, content calendar, social media best practices.',
            'status' => 'building', 'created_by' => $userId,
            'stats' => ['documents' => 3, 'chunks' => 45, 'indexed' => 2],
        ]);

        // ── 2. Sample Documents ──
        $documents = [
            [$salesKb->id, 'Sales Playbook 2026', 'text', 'Quy trình bán hàng: Discovery → Qualification → Proposal → Negotiation → Close. Mỗi stage cần 2-3 touchpoints. Always ask BANT questions.'],
            [$salesKb->id, 'Objection Handling Guide', 'text', 'Top objections: 1. Giá cao → Focus on ROI, show case studies. 2. Chưa cần → Create urgency with limited-time offers. 3. Đang dùng tool khác → Comparison table highlighting advantages.'],
            [$salesKb->id, 'CRM Sync: Leads', 'crm_sync', 'Synced 156 leads with full metadata: source, score, status, activities. Used for lead scoring patterns.'],
            [$salesKb->id, 'CRM Sync: Deals Pipeline', 'crm_sync', 'Synced 89 deals: stage distribution, win rate 62%, avg cycle 18 days, deal sizes 50M-500M VND.'],
            [$supportKb->id, 'FAQ — Tính năng CRM', 'text', 'Q: Làm sao tạo báo giá? A: Vào Quotations → Tạo mới → Chọn khách hàng → Thêm sản phẩm → Lưu & Gửi.\nQ: Reset mật khẩu? A: Settings → Account → Đổi mật khẩu.'],
            [$supportKb->id, 'Troubleshooting Guide', 'text', 'Lỗi thường gặp: 1. Không load được trang → Clear cache. 2. Email không gửi → Kiểm tra SMTP settings. 3. Import lỗi → Kiểm tra format CSV.'],
            [$supportKb->id, 'SLA Policy', 'text', 'Response time: Urgent (1h), High (4h), Medium (8h), Low (24h). Resolution time: Urgent (4h), High (1 ngày), Medium (3 ngày), Low (5 ngày).'],
            [$supportKb->id, 'CRM Sync: Tickets', 'crm_sync', 'Synced 234 resolved tickets. Top categories: Bug (45%), Feature Request (30%), Question (25%).'],
            [$productKb->id, 'Product Catalog', 'text', 'BED CRM Plans: 1. Starter (Free) — 5 users, basic CRM. 2. Pro (2M/month) — 20 users, full modules. 3. Enterprise (5M/month) — Unlimited, API, white-label.'],
            [$productKb->id, 'CRM Sync: Products', 'crm_sync', 'Synced product catalog: 45 products across 8 categories.'],
            [$hrKb->id, 'Employee Handbook 2026', 'text', 'Nghỉ phép: 12 ngày/năm. Remote: 2 ngày/tuần. Training budget: 5M/người/năm. Probation: 2 tháng. Review: quarterly.'],
            [$hrKb->id, 'Onboarding Checklist', 'text', 'Ngày 1: Setup accounts, meet team. Tuần 1: Training CRM. Tuần 2: Shadow senior. Tháng 1: First project. Tháng 2: Review & feedback.'],
            [$contentKb->id, 'SEO Best Practices', 'text', 'Focus keyword density 1.5-2%. H1 tag unique per page. Meta description 150-160 chars. Internal linking 3-5 per article. Schema markup for FAQ/HowTo.'],
        ];

        foreach ($documents as [$kbId, $title, $sourceType, $content]) {
            AiKnowledgeDocument::create([
                'knowledge_base_id' => $kbId, 'title' => $title,
                'source_type' => $sourceType, 'source_ref' => $sourceType === 'crm_sync' ? 'auto' : null,
                'content' => $content, 'content_hash' => hash('sha256', $content),
                'status' => 'indexed', 'last_synced_at' => now(),
            ]);
        }

        // ── 3. Training Sets ──
        AiTrainingSet::create([
            'account_id' => $accountId, 'name' => 'Sales Q&A Pairs', 'agent_type' => 'sales',
            'description' => 'Câu hỏi & trả lời thường gặp trong sales.',
            'format' => 'qa_pairs', 'status' => 'active', 'created_by' => $userId,
            'item_count' => 5, 'quality_score' => 8.5,
            'data' => [
                ['q' => 'Làm sao qualify lead hiệu quả?', 'a' => 'Dùng BANT: Budget, Authority, Need, Timeline. Lead có ≥3/4 criteria = Qualified.'],
                ['q' => 'Deal bị stuck ở Proposal, phải làm gì?', 'a' => 'Follow-up call trong 48h, gửi case study cùng ngành, offer deadline incentive.'],
                ['q' => 'Objection "giá cao" xử lý thế nào?', 'a' => 'Show ROI calculation, so sánh cost-of-inaction, offer payment plan.'],
                ['q' => 'Khi nào nên give up một deal?', 'a' => 'Khi: no budget confirmation sau 3 follow-ups, champion rời công ty, competitor đã ký hợp đồng.'],
                ['q' => 'Upsell khách cũ như thế nào?', 'a' => 'Review usage data, identify pain points, propose complementary products, leverage renewal timing.'],
            ],
        ]);

        AiTrainingSet::create([
            'account_id' => $accountId, 'name' => 'Support Resolution Examples', 'agent_type' => 'support',
            'description' => 'Ví dụ cách xử lý ticket thực tế.',
            'format' => 'examples', 'status' => 'active', 'created_by' => $userId,
            'item_count' => 3, 'quality_score' => 9.0,
            'data' => [
                ['scenario' => 'User không thể đăng nhập', 'resolution' => 'Check: 1) Caps Lock, 2) Correct email, 3) Reset password via forgot password, 4) Clear browser cache. Escalate if using SSO.'],
                ['scenario' => 'Data import bị lỗi', 'resolution' => 'Verify: 1) CSV format UTF-8, 2) Column headers match template, 3) No special characters in required fields, 4) File < 5MB.'],
                ['scenario' => 'Email campaign không gửi được', 'resolution' => 'Check: 1) SMTP config valid, 2) Sender email verified, 3) Daily sending limit, 4) Email content spam score.'],
            ],
        ]);

        // ── 4. AI Agents ──
        $salesAgent = AiAgent::create([
            'account_id' => $accountId, 'name' => 'Sales Coach', 'slug' => 'sales-coach',
            'type' => 'sales',
            'description' => 'Tư vấn chiến lược sales, phân tích pipeline, coaching deal, xử lý objections.',
            'system_prompt' => "Bạn là Sales Coach AI chuyên nghiệp. Nhiệm vụ:\n- Phân tích pipeline và đề xuất hành động\n- Coaching deal strategy dựa trên data CRM\n- Hướng dẫn xử lý objections\n- Cung cấp talking points cho meetings\n\nLuôn trả lời có cấu trúc, đưa ra 2-3 đề xuất cụ thể. Dẫn nguồn từ Knowledge Base.",
            'knowledge_base_ids' => [$salesKb->id, $productKb->id],
            'tools' => ['search_knowledge', 'query_crm', 'create_task', 'analyze_data'],
            'model_config' => ['provider' => 'gemini', 'model' => 'gemini-2.5-flash', 'temperature' => 0.7, 'max_tokens' => 4096],
            'is_active' => true, 'created_by' => $userId,
            'total_conversations' => 234, 'total_messages' => 1456, 'avg_satisfaction' => 4.20,
        ]);

        $supportAgent = AiAgent::create([
            'account_id' => $accountId, 'name' => 'Support Assistant', 'slug' => 'support-assistant',
            'type' => 'support',
            'description' => 'Trả lời tickets tự động, hướng dẫn sử dụng hệ thống, troubleshooting.',
            'system_prompt' => "Bạn là Support Assistant AI. Nhiệm vụ:\n- Trả lời câu hỏi khách hàng dựa trên FAQ & docs\n- Hướng dẫn step-by-step cách sử dụng CRM\n- Troubleshoot lỗi thường gặp\n- Escalate khi cần human support\n\nLuôn trả lời lịch sự, rõ ràng, có bước số. Ghi rõ SLA timeline.",
            'knowledge_base_ids' => [$supportKb->id, $productKb->id],
            'tools' => ['search_knowledge', 'query_crm', 'send_email', 'create_task'],
            'model_config' => ['provider' => 'gemini', 'model' => 'gemini-2.5-flash', 'temperature' => 0.3, 'max_tokens' => 2048],
            'is_active' => true, 'created_by' => $userId,
            'total_conversations' => 567, 'total_messages' => 3240, 'avg_satisfaction' => 4.50,
        ]);

        $contentAgent = AiAgent::create([
            'account_id' => $accountId, 'name' => 'Content Writer', 'slug' => 'content-writer',
            'type' => 'content',
            'description' => 'Viết blog SEO, social captions, email marketing, outline bài viết.',
            'system_prompt' => "Bạn là Content Writer AI chuyên nghiệp. Nhiệm vụ:\n- Viết bài blog SEO (outline → full article)\n- Tạo social media captions cho nhiều platform\n- Soạn email marketing sequences\n- Đề xuất content calendar\n\nViết theo SEO best practices. Tone chuyên nghiệp nhưng dễ hiểu. Luôn đề xuất meta title/description.",
            'knowledge_base_ids' => [$contentKb->id],
            'tools' => ['search_knowledge', 'web_search', 'generate_report'],
            'model_config' => ['provider' => 'openai', 'model' => 'gpt-4o', 'temperature' => 0.8, 'max_tokens' => 8192],
            'is_active' => true, 'created_by' => $userId,
            'total_conversations' => 123, 'total_messages' => 890, 'avg_satisfaction' => 4.00,
        ]);

        $analyticsAgent = AiAgent::create([
            'account_id' => $accountId, 'name' => 'Data Analyst', 'slug' => 'data-analyst',
            'type' => 'analytics',
            'description' => 'Phân tích dữ liệu kinh doanh, trends, forecasting, KPI tracking.',
            'system_prompt' => "Bạn là Data Analyst AI. Nhiệm vụ:\n- Phân tích revenue, leads, conversion theo thời gian\n- Identify trends và anomalies\n- Forecast dựa trên historical data\n- Đề xuất actions dựa trên data insights\n\nLuôn trình bày bằng bảng/số liệu. So sánh MoM/QoQ. Ghi rõ confidence level.",
            'knowledge_base_ids' => [$salesKb->id],
            'tools' => ['query_crm', 'analyze_data', 'generate_report'],
            'model_config' => ['provider' => 'gemini', 'model' => 'gemini-2.5-pro-preview-05-06', 'temperature' => 0.2, 'max_tokens' => 4096],
            'is_active' => true, 'created_by' => $userId,
            'total_conversations' => 89, 'total_messages' => 534, 'avg_satisfaction' => 4.30,
        ]);

        $hrAgent = AiAgent::create([
            'account_id' => $accountId, 'name' => 'HR Assistant', 'slug' => 'hr-assistant',
            'type' => 'hr',
            'description' => 'Hỏi đáp chính sách nhân sự, onboarding, training, văn hóa công ty.',
            'system_prompt' => "Bạn là HR Assistant AI. Nhiệm vụ:\n- Trả lời câu hỏi về chính sách công ty\n- Hướng dẫn quy trình nhân sự\n- Support onboarding nhân viên mới\n- Cung cấp thông tin training & benefits\n\nLuôn dẫn chiếu cụ thể policy document. Friendly tone. Recommend contact HR nếu cần approval.",
            'knowledge_base_ids' => [$hrKb->id],
            'tools' => ['search_knowledge', 'create_task'],
            'model_config' => ['provider' => 'claude', 'model' => 'claude-sonnet-4-20250514', 'temperature' => 0.5, 'max_tokens' => 2048],
            'is_active' => true, 'created_by' => $userId,
            'total_conversations' => 45, 'total_messages' => 210, 'avg_satisfaction' => 4.60,
        ]);

        // ── 5. Sample Conversations ──
        AiAgentConversation::create([
            'agent_id' => $salesAgent->id, 'user_id' => $userId,
            'title' => 'Phân tích pipeline tháng 3',
            'messages' => [
                ['role' => 'user', 'content' => 'Phân tích pipeline tháng này cho tôi', 'sources' => [], 'timestamp' => now()->subHours(2)->toISOString()],
                ['role' => 'assistant', 'content' => "📊 **Pipeline Analysis — Tháng 3/2026**\n\n| Stage | Deals | Value |\n|-------|-------|-------|\n| Qualification | 12 | 2.4B |\n| Proposal | 8 | 1.8B |\n| Negotiation | 5 | 1.2B |\n| Won | 15 | 3.5B |\n| Lost | 3 | 800M |\n\n**Insights:** Win rate 83%, tăng 5% so với tháng trước. 3 deals lớn (>500M) đang ở Negotiation, cần focus follow-up tuần này.", 'sources' => ['Sales KB', 'Pipeline Data'], 'timestamp' => now()->subHours(2)->addMinutes(1)->toISOString()],
            ],
            'message_count' => 2, 'satisfaction_rating' => 5, 'tokens_used' => 450,
        ]);

        // ── 6. Sync Logs ──
        $syncEntries = [
            [$salesKb->id, 'crm_sync', 'leads', 'sync', 156, 2, 2340, 'completed'],
            [$salesKb->id, 'crm_sync', 'deals', 'sync', 89, 0, 1560, 'completed'],
            [$supportKb->id, 'crm_sync', 'tickets', 'sync', 234, 5, 3200, 'completed'],
            [$productKb->id, 'crm_sync', 'products', 'sync', 45, 0, 890, 'completed'],
            [$supportKb->id, 'text', 'FAQ Documents', 'index', 12, 0, 1200, 'completed'],
            [$hrKb->id, 'text', 'HR Policies', 'index', 6, 0, 780, 'completed'],
            [$contentKb->id, 'crm_sync', 'content_items', 'sync', 28, 3, 1890, 'completed'],
        ];

        foreach ($syncEntries as [$kbId, $srcType, $srcRef, $action, $processed, $failed, $duration, $status]) {
            AiDataSyncLog::create([
                'account_id' => $accountId, 'knowledge_base_id' => $kbId,
                'source_type' => $srcType, 'source_ref' => $srcRef,
                'action' => $action, 'records_processed' => $processed,
                'records_failed' => $failed, 'duration_ms' => $duration,
                'status' => $status,
                'created_at' => now()->subMinutes(rand(5, 180)),
            ]);
        }
    }
}
