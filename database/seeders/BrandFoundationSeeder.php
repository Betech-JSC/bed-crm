<?php

namespace Database\Seeders;

use App\Models\BrandAsset;
use App\Models\BrandAuditLog;
use App\Models\BrandGuideline;
use Illuminate\Database\Seeder;

class BrandFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $brand = BrandGuideline::create([
            'account_id' => 1,
            'brand_purpose' => 'Trao quyền cho doanh nghiệp SME Việt Nam bằng công nghệ AI tiên tiến, giúp họ cạnh tranh ngang tầm với doanh nghiệp lớn.',
            'brand_vision' => 'Trở thành nền tảng CRM AI hàng đầu Đông Nam Á, nơi mỗi SME đều có một đội ngũ AI riêng để vận hành doanh nghiệp thông minh.',
            'brand_mission' => 'Cung cấp hệ sinh thái CRM tích hợp AI, giúp doanh nghiệp tự động hóa quy trình kinh doanh, tăng trưởng doanh thu, và nâng cao trải nghiệm khách hàng.',
            'brand_promise' => 'Mỗi doanh nghiệp sử dụng BED CRM sẽ có một AI team đáng tin cậy — hoạt động 24/7, không bao giờ nghỉ ốm, liên tục học hỏi và phát triển.',
            'tagline' => 'Your AI-Powered Business OS',
            'brand_values' => [
                ['name' => 'Innovation First', 'description' => 'Luôn đi đầu trong ứng dụng AI vào CRM. Không chấp nhận giải pháp cũ khi có cách tốt hơn.', 'icon' => 'pi pi-bolt'],
                ['name' => 'Customer Obsession', 'description' => 'Mọi tính năng đều bắt đầu từ vấn đề thực tế của khách hàng, không phải từ công nghệ.', 'icon' => 'pi pi-heart'],
                ['name' => 'Simplicity', 'description' => 'Công nghệ phức tạp phải được gói gọn trong trải nghiệm đơn giản, ai cũng dùng được.', 'icon' => 'pi pi-sparkles'],
                ['name' => 'Transparency', 'description' => 'Minh bạch về pricing, data, và AI decisions. Không hidden fees, không black box.', 'icon' => 'pi pi-eye'],
            ],
            'brand_personality' => [
                ['trait' => 'Intelligent', 'score' => 9],
                ['trait' => 'Approachable', 'score' => 8],
                ['trait' => 'Innovative', 'score' => 9],
                ['trait' => 'Reliable', 'score' => 8],
                ['trait' => 'Bold', 'score' => 7],
            ],
            'brand_positioning' => [
                'target_audience' => 'SME Việt Nam (10-200 nhân viên), đặc biệt là B2B SaaS, Agency, và doanh nghiệp dịch vụ',
                'differentiation' => 'CRM duy nhất tích hợp AI Agents chuyên biệt cho từng nghiệp vụ — không chỉ là tool, mà là đội ngũ AI',
                'pillars' => ['AI-Native CRM', 'All-in-One Business OS', 'Vietnam-First Design'],
                'statement' => 'BED CRM là nền tảng CRM AI-native đầu tiên tại Việt Nam, giúp SME vận hành toàn bộ doanh nghiệp từ Sales đến Marketing, HR, và Finance — với đội ngũ AI Agents thông minh.',
            ],
            'value_propositions' => [
                ['title' => '5 AI Agents sẵn sàng', 'description' => 'Sales Coach, Support Assistant, Content Writer, Data Analyst, HR Assistant — hoạt động 24/7.'],
                ['title' => 'All-in-One CRM', 'description' => '40+ modules: Sales, Marketing, HR, Finance, Project Management — không cần mua thêm tool nào.'],
                ['title' => 'Vietnam-Optimized', 'description' => 'Thiết kế riêng cho thị trường Việt Nam: tiếng Việt native, VND currency, local integrations.'],
            ],
            'primary_colors' => [
                ['name' => 'Royal Purple', 'hex' => '#7c3aed', 'usage' => 'Primary brand color, CTAs, icons'],
                ['name' => 'Deep Violet', 'hex' => '#5b21b6', 'usage' => 'Dark mode, headers, emphasis'],
                ['name' => 'Electric Indigo', 'hex' => '#6366f1', 'usage' => 'Links, secondary actions'],
            ],
            'secondary_colors' => [
                ['name' => 'Emerald', 'hex' => '#10b981', 'usage' => 'Success states, positive metrics'],
                ['name' => 'Amber', 'hex' => '#f59e0b', 'usage' => 'Warnings, highlights, stars'],
                ['name' => 'Rose', 'hex' => '#f43f5e', 'usage' => 'Errors, destructive actions'],
                ['name' => 'Cyan', 'hex' => '#06b6d4', 'usage' => 'Info states, secondary highlights'],
            ],
            'neutral_colors' => [
                ['name' => 'Slate 900', 'hex' => '#0f172a'],
                ['name' => 'Slate 700', 'hex' => '#334155'],
                ['name' => 'Slate 400', 'hex' => '#94a3b8'],
                ['name' => 'Slate 100', 'hex' => '#f1f5f9'],
            ],
            'font_primary' => 'Inter',
            'font_secondary' => 'Inter',
            'font_config' => ['headings' => 'Inter 700-900', 'body' => 'Inter 400-500', 'sizes' => ['h1' => '2rem', 'h2' => '1.5rem', 'h3' => '1.15rem', 'body' => '.82rem', 'small' => '.68rem']],
            'voice_traits' => [
                ['trait' => 'Chuyên nghiệp nhưng Gần gũi', 'description' => 'Nói chuyện như một advisor đáng tin cậy, không phải robot.', 'do_example' => '"Hãy để AI giúp bạn phân tích pipeline tuần này — chỉ mất 30 giây."', 'dont_example' => '"Hệ thống AI tích hợp thuật toán machine learning để xử lý dữ liệu pipeline."'],
                ['trait' => 'Tự tin nhưng Khiêm tốn', 'description' => 'Show khả năng qua kết quả, không qua hype.', 'do_example' => '"89% khách hàng tăng conversion sau 30 ngày sử dụng."', 'dont_example' => '"BED CRM là CRM tốt nhất thế giới, AI số 1 Việt Nam."'],
                ['trait' => 'Action-Oriented', 'description' => 'Luôn hướng đến hành động cụ thể, không nói suông.', 'do_example' => '"Thử ngay: Tạo AI Agent Sales Coach trong 2 phút →"', 'dont_example' => '"Chúng tôi tin rằng AI sẽ thay đổi tương lai kinh doanh..."'],
            ],
            'tone_variations' => [
                ['context' => 'Marketing Website', 'tone' => 'Tự tin, truyền cảm hứng', 'example' => 'Chào mừng đến với tương lai của CRM — nơi AI làm việc cho bạn.'],
                ['context' => 'In-App UI', 'tone' => 'Ngắn gọn, hữu ích', 'example' => 'Pipeline đang tốt! 3 deals cần follow-up hôm nay.'],
                ['context' => 'Support', 'tone' => 'Kiên nhẫn, empathetic', 'example' => 'Mình hiểu vấn đề rồi. Để mình hướng dẫn từng bước nhé.'],
                ['context' => 'Error Messages', 'tone' => 'Rõ ràng, không đổ lỗi', 'example' => 'Không thể lưu. Kiểm tra kết nối internet và thử lại.'],
            ],
            'writing_guidelines' => [
                'vocabulary' => ['AI Agent', 'Business OS', 'tự động hóa', 'insights', 'pipeline', 'growth', 'smart', 'real-time'],
                'avoid_words' => ['disruption', 'synergy', 'leverage', 'paradigm shift', 'best-in-class', 'tốt nhất thế giới'],
                'grammar_rules' => ['Dùng "bạn/mình" thay vì "quý khách"', 'Viết câu ngắn, max 20 từ', 'Active voice > Passive voice'],
            ],
            'status' => 'active',
            'created_by' => 1,
            'published_at' => now(),
        ]);

        BrandAuditLog::log($brand->id, 'created', 'foundation');
        BrandAuditLog::log($brand->id, 'updated', 'visual');
        BrandAuditLog::log($brand->id, 'updated', 'voice');
        BrandAuditLog::log($brand->id, 'published', 'foundation');
    }
}
