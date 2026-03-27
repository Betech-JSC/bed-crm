<?php

namespace Database\Seeders;

use App\Models\AiContentTemplate;
use App\Models\AiGeneratedContent;
use App\Models\ChatbotFlow;
use App\Models\GmbLocation;
use App\Models\GmbPost;
use App\Models\GmbReview;
use Illuminate\Database\Seeder;

class AdvancedModulesSeeder extends Seeder
{
    public function run(): void
    {
        $accountId = 1;
        $userId = 1;

        // ── GMB ──
        $loc = GmbLocation::create([
            'account_id' => $accountId,
            'location_name' => 'BETECH JSC - Trụ sở chính',
            'address' => '123 Nguyễn Huệ, Quận 1, TP.HCM',
            'phone' => '028 1234 5678',
            'website' => 'https://betech.vn',
            'category' => 'Công ty thiết kế website',
            'total_views' => 12450,
            'total_searches' => 3280,
            'total_actions' => 890,
            'avg_rating' => 4.8,
            'review_count' => 5,
        ]);

        $reviewers = [
            ['Nguyễn Văn A', 5, 'Dịch vụ tuyệt vời! Đội ngũ rất chuyên nghiệp.'],
            ['Trần Thị B', 4, 'Website đẹp, nhưng thời gian giao hơi lâu.'],
            ['Lê Minh C', 5, 'Tôi rất hài lòng. SEO hiệu quả, traffic tăng mạnh.'],
            ['Phạm Hương D', 5, 'Best CRM! Recommend cho mọi doanh nghiệp.'],
            ['Vũ Thanh E', 3, 'Ổn, nhưng giá hơi cao so với thị trường.'],
        ];

        foreach ($reviewers as $i => [$name, $rating, $comment]) {
            GmbReview::create([
                'gmb_location_id' => $loc->id,
                'reviewer_name' => $name,
                'rating' => $rating,
                'comment' => $comment,
                'reply' => $i < 2 ? 'Cảm ơn bạn đã đánh giá! Chúng tôi rất vui vì bạn hài lòng.' : null,
                'replied_at' => $i < 2 ? now()->subDays(rand(1, 10)) : null,
                'review_time' => now()->subDays(rand(1, 90)),
            ]);
        }

        GmbPost::create([
            'gmb_location_id' => $loc->id, 'post_type' => 'update',
            'content' => '🚀 Ra mắt BED CRM 2.0 — Hệ thống quản lý doanh nghiệp toàn diện với AI!',
            'cta_type' => 'learn_more', 'cta_url' => 'https://betech.vn/bed-crm',
            'status' => 'published', 'published_at' => now()->subDays(3),
        ]);

        GmbPost::create([
            'gmb_location_id' => $loc->id, 'post_type' => 'offer',
            'content' => '🎉 Giảm 20% thiết kế website cho khách hàng mới trong tháng 4!',
            'cta_type' => 'book', 'cta_url' => 'https://betech.vn/contact',
            'status' => 'draft',
        ]);

        // ── AI Content Templates ──
        $templates = [
            ['name' => 'Blog SEO Toàn diện', 'category' => 'blog_seo', 'description' => 'Viết blog SEO 1500+ từ', 'system_prompt' => 'Bạn là chuyên gia SEO content. Viết bài chuẩn SEO on-page, tối ưu heading, keyword density 0.5-2.5%.', 'user_prompt_template' => 'Viết bài blog SEO về "{keyword}" với tone {tone}, ngôn ngữ {language}, khoảng {length} từ.', 'is_system' => true, 'usage_count' => 45],
            ['name' => 'Social Caption Pack', 'category' => 'social_caption', 'description' => 'Tạo caption cho Facebook, Instagram, LinkedIn', 'system_prompt' => 'Bạn viết social media posts. Mỗi post ngắn gọn, engage, có emoji và hashtags.', 'user_prompt_template' => 'Viết 3 phiên bản caption về "{keyword}" cho mỗi platform: Facebook, Instagram, LinkedIn.', 'is_system' => true, 'usage_count' => 32],
            ['name' => 'Email Sequence Builder', 'category' => 'email_sequence', 'description' => 'Tạo chuỗi email marketing', 'system_prompt' => 'Bạn là email marketing expert. Viết email có subject line hấp dẫn, nội dung ngắn, CTA rõ ràng.', 'user_prompt_template' => 'Tạo chuỗi 3 email nurture cho chủ đề "{keyword}" với tone {tone}.', 'is_system' => true, 'usage_count' => 18],
            ['name' => 'Ad Copy Generator', 'category' => 'ad_copy', 'description' => 'Tạo nội dung quảng cáo', 'system_prompt' => 'Bạn viết ad copy chuyên nghiệp. Headlines + descriptions cho Google Ads và Facebook Ads.', 'user_prompt_template' => 'Tạo ad copy cho sản phẩm/dịch vụ "{keyword}". Cần 5 headlines và 3 descriptions.', 'is_system' => true, 'usage_count' => 22],
        ];

        foreach ($templates as $t) {
            AiContentTemplate::create(['account_id' => $accountId, ...$t]);
        }

        // Sample content
        AiGeneratedContent::create([
            'account_id' => $accountId, 'created_by' => $userId,
            'title' => 'Thiết kế Website Chuẩn SEO 2026',
            'content_type' => 'blog',
            'generated_content' => "# Thiết kế Website Chuẩn SEO 2026\n\n## Giới thiệu\n\nXu hướng thiết kế website năm 2026 đang thay đổi mạnh mẽ...\n\n## 5 Tiêu chí Website Chuẩn SEO\n\n### 1. Core Web Vitals\n### 2. Mobile-First Design\n### 3. Schema Markup\n### 4. Content Structure\n### 5. Page Speed\n\n## Kết luận\n\nĐầu tư website chuẩn SEO là quyết định đúng đắn...",
            'seo_suggestions' => ['meta_title' => 'Thiết kế Website Chuẩn SEO 2026 | BETECH', 'readability_score' => 82],
            'status' => 'published',
        ]);

        // ── Chatbot Flows ──
        ChatbotFlow::create([
            'account_id' => $accountId, 'created_by' => $userId,
            'name' => 'Lead Qualification Bot',
            'description' => 'Chatbot tự động qualify leads khi khách ghé website',
            'trigger_type' => 'time_delay', 'trigger_value' => '5',
            'status' => 'active',
            'conversations_count' => 234,
            'leads_captured' => 67,
            'nodes' => [
                ['id' => 'start', 'type' => 'message', 'data' => ['message' => 'Xin chào! 👋 Tôi là trợ lý ảo của BETECH.'], 'position' => ['x' => 250, 'y' => 50]],
                ['id' => 'q1', 'type' => 'options', 'data' => ['message' => 'Bạn đang quan tâm dịch vụ nào?', 'options' => ['Thiết kế website', 'SEO', 'BED CRM', 'Khác']], 'position' => ['x' => 250, 'y' => 200]],
                ['id' => 'q2', 'type' => 'question', 'data' => ['message' => 'Ngân sách dự kiến của bạn?'], 'position' => ['x' => 250, 'y' => 350]],
                ['id' => 'collect', 'type' => 'collect_info', 'data' => ['message' => 'Để tôi tư vấn tốt hơn:', 'fields' => ['name', 'email', 'phone', 'company']], 'position' => ['x' => 250, 'y' => 500]],
                ['id' => 'end', 'type' => 'end', 'data' => ['message' => 'Cảm ơn bạn! Team BETECH sẽ liên hệ trong 30 phút! 🚀'], 'position' => ['x' => 250, 'y' => 650]],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'start', 'target' => 'q1'],
                ['id' => 'e2', 'source' => 'q1', 'target' => 'q2'],
                ['id' => 'e3', 'source' => 'q2', 'target' => 'collect'],
                ['id' => 'e4', 'source' => 'collect', 'target' => 'end'],
            ],
        ]);

        ChatbotFlow::create([
            'account_id' => $accountId, 'created_by' => $userId,
            'name' => 'Support Bot', 'description' => 'Chatbot hỗ trợ kỹ thuật cơ bản',
            'trigger_type' => 'button_click', 'status' => 'draft',
            'conversations_count' => 0, 'leads_captured' => 0,
            'nodes' => [
                ['id' => 'start', 'type' => 'message', 'data' => ['message' => 'Chào bạn! Tôi có thể hỗ trợ gì?'], 'position' => ['x' => 250, 'y' => 50]],
                ['id' => 'options', 'type' => 'options', 'data' => ['message' => 'Chọn vấn đề:', 'options' => ['Lỗi kỹ thuật', 'Hướng dẫn sử dụng', 'Nâng cấp gói']], 'position' => ['x' => 250, 'y' => 200]],
            ],
            'edges' => [['id' => 'e1', 'source' => 'start', 'target' => 'options']],
        ]);
    }
}
