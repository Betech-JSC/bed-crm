<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\SalesPlaybook;
use Illuminate\Database\Seeder;

class SalesPlaybookSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::first();

        $playbooks = [
            [
                'name' => 'Cold Outreach - Trading Companies',
                'description' => 'Quy trình tiếp cận công ty thương mại chưa có website chuyên nghiệp',
                'industries' => ['Trading', 'Import/Export', 'Wholesale'],
                'deal_stages' => ['prospecting', 'qualification'],
                'pain_points' => ['Không có website', 'Website cũ không mobile-friendly', 'Không có leads online'],
                'talking_points' => "1. Giới thiệu BED Technology và portfolio\n2. Đề cập pain point: Đối thủ có website đẹp, bạn thì chưa\n3. Show case study cùng ngành (ABC Trading tăng 200% leads)\n4. Đề xuất demo 15 phút miễn phí\n5. Nêu gói starter phù hợp budget SME",
                'email_template_subject' => '{{company_name}} - Cơ hội tăng doanh thu online',
                'email_template_body' => "Xin chào {{contact_name}},\n\nTôi là {{sender_name}} từ BED Technology. Chúng tôi chuyên thiết kế website cho doanh nghiệp {{industry}}.\n\nĐối thủ của {{company_name}} đang nhận hàng trăm leads mỗi tháng từ website. Bạn có muốn xem cách họ làm?\n\n✅ Website chuẩn SEO, tối ưu Google\n✅ Đa ngôn ngữ EN/VI\n✅ Tích hợp CRM tự động\n\nDemo miễn phí: [link]\n\nBest,\n{{sender_name}}",
                'recommended_documents' => ['Company Profile', 'Portfolio Website', 'Case Study - Trading'],
                'objections_handling' => "Q: 'Giá cao quá'\nA: So sánh chi phí thuê 1 nhân viên marketing vs website 24/7. ROI sau 3 tháng.\n\nQ: 'Đã có website rồi'\nA: Audit miễn phí, so sánh tốc độ + SEO + mobile score.\n\nQ: 'Không cần website'\nA: 78% khách hàng B2B tìm nhà cung cấp qua Google trước khi gọi điện.",
                'next_steps' => '1. Gửi email personalized\n2. Follow up qua Zalo sau 2 ngày\n3. Sắp lịch demo 15p\n4. Gửi proposal\n5. Close deal trong 14 ngày',
                'tags' => ['cold-outreach', 'trading', 'sme'],
                'priority' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Inbound Lead - Website Request',
                'description' => 'Xử lý lead đã submit form trên website yêu cầu thiết kế web',
                'industries' => ['All'],
                'deal_stages' => ['qualification', 'proposal'],
                'pain_points' => ['Cần website mới', 'Redesign website', 'Mở rộng online'],
                'talking_points' => "1. Cảm ơn đã quan tâm, hỏi thêm về nhu cầu\n2. Tìm hiểu budget và timeline\n3. Đề xuất gói phù hợp\n4. Show demo website cùng ngành\n5. Lên lịch meeting chi tiết",
                'email_template_subject' => 'Cảm ơn {{contact_name}} - BED đã nhận yêu cầu!',
                'email_template_body' => "Xin chào {{contact_name}},\n\nCảm ơn bạn đã quan tâm dịch vụ thiết kế website. Để tư vấn chính xác, vui lòng cho biết:\n\n1. Loại website mong muốn?\n2. Budget dự kiến?\n3. Timeline cần hoàn thành?\n\nTôi có thể demo ngay cho bạn: [calendar_link]\n\nBest,\n{{sender_name}}",
                'recommended_documents' => ['Website Packages', 'Portfolio', 'FAQ'],
                'objections_handling' => "Q: 'Thời gian bao lâu?'\nA: Landing page: 7 ngày. Website full: 3-4 tuần. E-commerce: 6-8 tuần.\n\nQ: 'Có bảo hành không?'\nA: 6 tháng bảo hành miễn phí + support trong giờ hành chính.",
                'next_steps' => '1. Reply trong 5 phút\n2. Qualify: budget, timeline, authority\n3. Demo 30p\n4. Gửi proposal trong 24h\n5. Follow up 3 ngày',
                'tags' => ['inbound', 'warm-lead', 'website'],
                'priority' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Upsell CRM - Existing Customers',
                'description' => 'Cross-sell BED CRM cho khách hàng website hiện tại',
                'industries' => ['All'],
                'deal_stages' => ['qualification', 'proposal', 'negotiation'],
                'pain_points' => ['Quản lý leads bằng Excel', 'Không track được sales', 'Marketing thủ công'],
                'talking_points' => "1. Review kết quả website đã tạo (traffic, leads)\n2. 'Bạn đang quản lý leads thế nào?' → Pain discovery\n3. Demo BED CRM ngay trên leads thật của họ\n4. Show automation email + chatbot\n5. Offer: Miễn phí 3 tháng CRM Starter nếu upgrade",
                'email_template_subject' => '{{contact_name}} - Website của bạn đang nhận {{leads_count}} leads/tháng!',
                'email_template_body' => "Xin chào {{contact_name}},\n\nWebsite {{company_name}} đang hoạt động tốt! Chúng tôi thấy {{leads_count}} leads mỗi tháng đang đến từ organic search.\n\nBạn đang quản lý những leads này thế nào? BED CRM có thể giúp:\n\n✅ Tự động phân loại và chấm điểm leads\n✅ Email automation follow-up\n✅ Báo cáo doanh thu realtime\n\nDùng thử miễn phí 30 ngày → [link]\n\nBest,\n{{sender_name}}",
                'recommended_documents' => ['CRM Features', 'ROI Calculator', 'Success Stories'],
                'objections_handling' => "Q: 'Excel vẫn ổn'\nA: Excel không track được email opens, website visits, hay tự gửi follow-up.\n\nQ: 'Thêm chi phí'\nA: CRM starter chỉ 2M/tháng, ROI 5x trong tháng đầu nếu bạn chốt thêm 1 deal.",
                'next_steps' => '1. Gửi report traffic/leads\n2. Call 15p discuss pain\n3. Offer trial 30 ngày\n4. Onboarding 1h\n5. Review sau 2 tuần',
                'tags' => ['upsell', 'existing-customer', 'crm'],
                'priority' => 15,
                'is_active' => true,
            ],
        ];

        foreach ($playbooks as $pb) {
            SalesPlaybook::create(array_merge($pb, ['account_id' => $account->id]));
        }

        $this->command->info('Created ' . count($playbooks) . ' sales playbooks.');
    }
}
