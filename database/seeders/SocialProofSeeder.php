<?php

namespace Database\Seeders;

use App\Models\CustomerReview;
use App\Models\ReferralCode;
use App\Models\ReferralConversion;
use Illuminate\Database\Seeder;

class SocialProofSeeder extends Seeder
{
    public function run(): void
    {
        $accountId = 1;

        // ── Customer Reviews ──
        $reviews = [
            ['customer_name' => 'Trần Minh Tuấn', 'customer_company' => 'ABC Technology', 'customer_role' => 'CEO', 'review_text' => 'BED CRM giúp chúng tôi quản lý khách hàng hiệu quả hơn 200%. Giao diện trực quan, dễ dùng. Rất recommend!', 'rating' => 5, 'platform' => 'google', 'service_type' => 'crm', 'status' => 'featured'],
            ['customer_name' => 'Nguyễn Thị Hương', 'customer_company' => 'Fashion Plus', 'customer_role' => 'Marketing Director', 'review_text' => 'Website mới giúp tăng lead từ 50 lên 200 mỗi tháng. Team thiết kế rất chuyên nghiệp và sáng tạo.', 'rating' => 5, 'platform' => 'facebook', 'service_type' => 'web_design', 'status' => 'featured'],
            ['customer_name' => 'Lê Văn Đức', 'customer_company' => 'Duc Construction', 'customer_role' => 'Founder', 'review_text' => 'Dịch vụ SEO rất tốt, website đã lên top 5 Google cho nhiều keyword quan trọng.', 'rating' => 4, 'platform' => 'direct', 'service_type' => 'seo', 'status' => 'approved'],
            ['customer_name' => 'Phạm Quốc Bảo', 'customer_company' => 'BQ Foods', 'customer_role' => 'Operations Manager', 'review_text' => 'Hệ thống email marketing tự động hóa quy trình chăm sóc khách hàng rất hiệu quả.', 'rating' => 4, 'platform' => 'google', 'service_type' => 'marketing', 'status' => 'approved'],
            ['customer_name' => 'Vũ Thanh Nga', 'customer_company' => 'Startup Hub', 'customer_role' => 'CTO', 'review_text' => 'App mobile chạy mượt, UI/UX đẹp. Team support 24/7 rất nhiệt tình.', 'rating' => 5, 'platform' => 'trustpilot', 'service_type' => 'app_dev', 'status' => 'approved'],
            ['customer_name' => 'Hoàng Minh Châu', 'customer_company' => '', 'customer_role' => 'Freelancer', 'review_text' => 'Giá hợp lý, chất lượng vượt mong đợi. Sẽ quay lại cho dự án tiếp theo.', 'rating' => 5, 'platform' => 'zalo', 'service_type' => 'web_design', 'status' => 'pending'],
        ];

        foreach ($reviews as $r) {
            CustomerReview::create(array_merge($r, [
                'account_id' => $accountId,
                'review_date' => now()->subDays(rand(1, 60)),
            ]));
        }

        // ── Referral Codes ──
        $codes = [
            ['referrer_name' => 'Trần Minh Tuấn', 'referrer_email' => 'tuan@abc-tech.vn', 'code' => 'TUAN2026', 'reward_type' => 'commission', 'reward_value' => 10, 'reward_unit' => 'percent', 'uses_count' => 8, 'status' => 'active'],
            ['referrer_name' => 'Nguyễn Thị Hương', 'referrer_email' => 'huong@fashionplus.vn', 'code' => 'HUONG10', 'reward_type' => 'discount', 'reward_value' => 10, 'reward_unit' => 'percent', 'uses_count' => 3, 'status' => 'active'],
            ['referrer_name' => 'Lê Văn Đức', 'referrer_email' => 'duc@construction.vn', 'code' => 'DUC500K', 'reward_type' => 'credit', 'reward_value' => 500000, 'reward_unit' => 'fixed', 'uses_count' => 5, 'max_uses' => 10, 'status' => 'active'],
        ];

        foreach ($codes as $c) {
            $code = ReferralCode::create(array_merge($c, ['account_id' => $accountId]));

            // Fake conversions
            for ($i = 0; $i < min($c['uses_count'], 3); $i++) {
                ReferralConversion::create([
                    'referral_code_id' => $code->id,
                    'referred_name' => 'KH ' . ($i + 1) . ' (via ' . $c['code'] . ')',
                    'referred_email' => 'kh' . ($i + 1) . '@example.com',
                    'status' => ['pending', 'qualified', 'converted'][rand(0, 2)],
                    'deal_value' => rand(5, 50) * 1000000,
                    'commission_amount' => $c['reward_type'] === 'commission' ? rand(1, 5) * 1000000 : null,
                ]);
            }
        }
    }
}
