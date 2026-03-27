<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ContentItem;
use App\Models\ContentTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContentItemSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::first();
        $user = User::where('account_id', $account->id)->first();
        $templates = ContentTemplate::where('account_id', $account->id)->get();

        if ($templates->isEmpty()) {
            $this->command->warn('No content templates found. Run ContentTemplateSeeder first.');
            return;
        }

        $items = [
            // Blog items
            ['title' => '10 Bước Thiết Kế Website Chuẩn SEO 2026', 'type' => 'blog', 'status' => 'published', 'content' => '<h2>1. Nghiên cứu từ khóa</h2><p>Trước khi bắt tay vào thiết kế, bạn cần xác định từ khóa mục tiêu...</p><h2>2. Cấu trúc URL thân thiện</h2><p>URL nên ngắn gọn, chứa từ khóa chính...</p><h2>3. Tốc độ tải trang</h2><p>Google ưu tiên website tải nhanh dưới 3 giây...</p>', 'tags' => ['seo', 'web-design', 'guide']],
            ['title' => 'Tại Sao Doanh Nghiệp Cần CRM Trong 2026?', 'type' => 'blog', 'status' => 'published', 'content' => '<h2>CRM Không Chỉ Là Quản Lý Khách Hàng</h2><p>Trong thời đại AI, CRM đã trở thành trung tâm điều hành số...</p><h2>5 Lợi Ích Chính</h2><ol><li>Tăng doanh thu 30%</li><li>Giảm thời gian xử lý 50%</li><li>Automation marketing</li></ol>', 'tags' => ['crm', 'business', 'digital-transformation']],
            ['title' => 'Hướng Dẫn Email Marketing Hiệu Quả', 'type' => 'blog', 'status' => 'draft', 'content' => '<h2>Xây Dựng Danh Sách Email</h2><p>Bắt đầu từ việc tạo lead magnet chất lượng...</p><h2>Viết Subject Line Hấp Dẫn</h2><p>Subject line quyết định 70% tỷ lệ mở email...</p>', 'tags' => ['email', 'marketing', 'guide']],
            ['title' => 'Case Study: Tăng 200% Doanh Thu Cho ABC Trading', 'type' => 'blog', 'status' => 'approved', 'content' => '<h2>Thách Thức</h2><p>ABC Trading gặp khó khăn trong quản lý 500+ khách hàng...</p><h2>Giải Pháp</h2><p>Triển khai BED CRM với automation marketing...</p><h2>Kết Quả</h2><p>Doanh thu tăng 200% sau 6 tháng...</p>', 'tags' => ['case-study', 'crm', 'success']],

            // Social items
            ['title' => '🚀 Ra mắt tính năng AI Chat mới!', 'type' => 'social_post', 'status' => 'published', 'content' => '🎉 Tin vui cho doanh nghiệp! BED CRM vừa ra mắt tính năng AI Chat tự động 24/7. Không cần nhân viên trực, chatbot AI sẽ trả lời mọi câu hỏi của khách hàng. 💬\n\n✅ Tích hợp website\n✅ Hiểu ngữ cảnh Tiếng Việt\n✅ Học từ FAQ của bạn\n\n#BEDCRM #AIChat #Automation', 'tags' => ['launch', 'ai', 'social']],
            ['title' => 'Tips quản lý leads hiệu quả', 'type' => 'social_post', 'status' => 'published', 'content' => '💡 5 Mẹo quản lý leads hiệu quả cho Sales Team:\n\n1️⃣ Phân loại theo điểm số (Lead Scoring)\n2️⃣ Follow up trong 5 phút đầu\n3️⃣ Sử dụng email automation\n4️⃣ Track mọi tương tác\n5️⃣ Nurture bằng content giá trị\n\nBạn đang áp dụng mẹo nào? 👇\n\n#Sales #LeadManagement #CRM', 'tags' => ['tips', 'sales', 'social']],
            ['title' => 'Infographic: Digital Marketing Trends 2026', 'type' => 'social_post', 'status' => 'draft', 'content' => '📊 Xu hướng Digital Marketing 2026:\n\n🔥 AI-powered personalization: 78% marketer áp dụng\n🔥 Video ngắn: Tăng 150% engagement\n🔥 Voice search: 45% tìm kiếm bằng giọng nói\n🔥 Chatbot: Giảm 60% thời gian support\n\nSave để tham khảo! 📌\n\n#DigitalMarketing #Trends2026 #Marketing', 'tags' => ['trends', 'infographic', 'social']],

            // Email items
            ['title' => 'Welcome Email - Khách hàng mới', 'type' => 'email', 'status' => 'approved', 'content' => 'Xin chào {{customer_name}},\n\nChào mừng bạn đến với BED CRM! 🎉\n\nChúng tôi rất vui khi bạn chọn BED CRM làm giải pháp quản lý kinh doanh. Dưới đây là 3 bước để bắt đầu:\n\n1. Hoàn thiện profile công ty\n2. Import danh sách khách hàng\n3. Tạo campaign đầu tiên\n\nBắt đầu ngay → [link]\n\nChúc bạn thành công!', 'tags' => ['welcome', 'email', 'template']],
            ['title' => 'Newsletter Tháng 3/2026', 'type' => 'email', 'status' => 'published', 'content' => '📮 Bản tin BED CRM - Tháng 3/2026\n\n🆕 Tính năng mới:\n- AI Content Studio: Tạo nội dung tự động\n- Meeting Module: Họp video + AI recap\n- Outbound Sales Automation\n\n📈 Tips of the month:\nCách tối ưu workflow automation để tiết kiệm 10h/tuần\n\n🎓 Webinar sắp tới:\n"AI trong CRM - Xu hướng 2026"\nĐăng ký: [link]', 'tags' => ['newsletter', 'monthly', 'email']],

            // Ad Copy items
            ['title' => 'Google Ads - BED CRM Enterprise', 'type' => 'ad_copy', 'status' => 'approved', 'content' => 'Headline 1: CRM Thông Minh Cho Doanh Nghiệp\nHeadline 2: Tăng Doanh Thu 200% Với AI\nHeadline 3: Dùng Thử Miễn Phí 30 Ngày\n\nDescription 1: Quản lý khách hàng, automation marketing, AI chatbot - tất cả trong một nền tảng. Bắt đầu ngay!\nDescription 2: 5000+ doanh nghiệp Việt Nam tin dùng. Tích hợp Email, Zalo, Facebook. Setup 5 phút.', 'tags' => ['google-ads', 'enterprise', 'ad']],
            ['title' => 'Facebook Ads - Lead Generation', 'type' => 'ad_copy', 'status' => 'draft', 'content' => '🎯 Bạn đang quản lý khách hàng bằng Excel?\n\nĐã đến lúc nâng cấp!\n\nBED CRM giúp bạn:\n✅ Tự động theo dõi leads\n✅ Gửi email marketing tự động\n✅ AI phân tích hành vi khách hàng\n✅ Báo cáo doanh thu realtime\n\n🎁 DÙNG THỬ MIỄN PHÍ 30 NGÀY\n\n👉 Đăng ký ngay: [link]', 'tags' => ['facebook-ads', 'lead-gen', 'ad']],
        ];

        foreach ($items as $item) {
            ContentItem::create(array_merge($item, [
                'account_id' => $account->id,
                'created_by' => $user->id,
                'template_id' => $templates->random()->id,
                'ai_model' => collect(['gpt-4', 'gpt-3.5-turbo', 'gemini-pro', 'claude-3'])->random(),
                'usage_count' => rand(0, 50),
                'metadata' => ['generated_at' => now()->subDays(rand(1, 30))->toISOString()],
            ]));
        }

        $this->command->info('Created ' . count($items) . ' content items.');
    }
}
