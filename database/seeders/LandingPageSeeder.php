<?php

namespace Database\Seeders;

use App\Models\LandingPage;
use Illuminate\Database\Seeder;

class LandingPageSeeder extends Seeder
{
    public function run(): void
    {
        $accountId = 1;
        $userId = 1;

        LandingPage::create([
            'account_id' => $accountId,
            'created_by' => $userId,
            'title' => 'Thiết kế website chuyên nghiệp',
            'description' => 'Landing page quảng cáo dịch vụ thiết kế website',
            'status' => 'published',
            'meta_title' => 'Thiết kế website chuyên nghiệp | BED CRM',
            'meta_description' => 'Dịch vụ thiết kế website đẳng cấp với giá ưu đãi, cam kết SEO top Google.',
            'web_form_id' => 1,
            'blocks' => [
                ['type' => 'hero', 'data' => ['heading' => 'Thiết kế website đẳng cấp', 'subheading' => 'Tăng 300% chuyển đổi với website chuyên nghiệp', 'cta_text' => 'Nhận tư vấn miễn phí', 'bg_color' => '#4f46e5', 'text_color' => '#ffffff']],
                ['type' => 'stats', 'data' => ['items' => [['number' => '500+', 'label' => 'Website đã triển khai'], ['number' => '98%', 'label' => 'Khách hài lòng'], ['number' => '24/7', 'label' => 'Hỗ trợ kỹ thuật']]]],
                ['type' => 'features', 'data' => ['heading' => 'Tại sao chọn chúng tôi?', 'items' => [['title' => 'Thiết kế premium', 'desc' => 'UI/UX đẳng cấp', 'icon' => '🎨'], ['title' => 'SEO-ready', 'desc' => 'Tối ưu ngay từ đầu', 'icon' => '🔍'], ['title' => 'Tốc độ nhanh', 'desc' => 'Dưới 2 giây load', 'icon' => '⚡']]]],
                ['type' => 'testimonials', 'data' => ['heading' => 'Khách hàng nói gì?', 'items' => [['name' => 'Tran Minh', 'role' => 'CEO, ABC Tech', 'text' => 'Website mới giúp tăng lead 200%!']]]],
                ['type' => 'form', 'data' => ['heading' => 'Nhận báo giá miễn phí', 'use_web_form' => true]],
            ],
            'style_settings' => ['primary_color' => '#4f46e5', 'font' => 'Inter', 'border_radius' => 12],
            'visits_count' => 2340,
            'conversions_count' => 187,
        ]);

        LandingPage::create([
            'account_id' => $accountId,
            'created_by' => $userId,
            'title' => 'SEO Package — Lên top Google',
            'description' => 'Landing page dịch vụ SEO',
            'status' => 'draft',
            'blocks' => [
                ['type' => 'hero', 'data' => ['heading' => 'Lên TOP 1 Google trong 90 ngày', 'subheading' => 'Chiến lược SEO bài bản, cam kết hiệu quả', 'cta_text' => 'Đăng ký ngay', 'bg_color' => '#10b981']],
                ['type' => 'cta', 'data' => ['heading' => 'Ưu đãi giảm 30%', 'subheading' => 'Áp dụng đến hết tháng 4', 'button_text' => 'Nhận ưu đãi', 'bg_color' => '#f59e0b']],
            ],
            'style_settings' => ['primary_color' => '#10b981', 'font' => 'Be Vietnam Pro'],
            'visits_count' => 0,
            'conversions_count' => 0,
        ]);
    }
}
