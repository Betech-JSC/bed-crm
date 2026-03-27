<?php

namespace Database\Seeders;

use App\Models\ContentCalendar;
use App\Models\SeoAuditIssue;
use App\Models\SeoKeyword;
use App\Models\SeoRankHistory;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SeoContentSeeder extends Seeder
{
    public function run(): void
    {
        $accountId = 1;
        $userId = 1;

        // ── SEO Keywords ──
        $keywords = [
            ['keyword' => 'thiết kế website', 'url' => 'https://betech.vn/thiet-ke-website', 'current_rank' => 3, 'previous_rank' => 5, 'best_rank' => 3, 'search_volume' => 12100, 'difficulty' => 'hard'],
            ['keyword' => 'thiết kế website giá rẻ', 'url' => 'https://betech.vn/thiet-ke-website-gia-re', 'current_rank' => 7, 'previous_rank' => 12, 'best_rank' => 7, 'search_volume' => 5400, 'difficulty' => 'medium'],
            ['keyword' => 'dịch vụ SEO', 'url' => 'https://betech.vn/seo-services', 'current_rank' => 15, 'previous_rank' => 18, 'best_rank' => 12, 'search_volume' => 8100, 'difficulty' => 'hard'],
            ['keyword' => 'phần mềm CRM', 'url' => 'https://betech.vn/crm', 'current_rank' => 22, 'previous_rank' => 28, 'best_rank' => 22, 'search_volume' => 3200, 'difficulty' => 'medium'],
            ['keyword' => 'marketing automation', 'url' => 'https://betech.vn/marketing', 'current_rank' => 44, 'previous_rank' => 35, 'best_rank' => 35, 'search_volume' => 1900, 'difficulty' => 'hard'],
            ['keyword' => 'thiết kế landing page', 'url' => 'https://betech.vn/landing-page', 'current_rank' => 8, 'previous_rank' => 11, 'best_rank' => 8, 'search_volume' => 2400, 'difficulty' => 'easy'],
            ['keyword' => 'email marketing', 'url' => 'https://betech.vn/email-mkt', 'current_rank' => 28, 'previous_rank' => 30, 'best_rank' => 25, 'search_volume' => 6600, 'difficulty' => 'medium'],
            ['keyword' => 'quản lý khách hàng', 'url' => 'https://betech.vn/quan-ly-khach-hang', 'current_rank' => 11, 'previous_rank' => 14, 'best_rank' => 11, 'search_volume' => 4400, 'difficulty' => 'easy'],
        ];

        foreach ($keywords as $kw) {
            $k = SeoKeyword::create(array_merge($kw, [
                'account_id' => $accountId,
                'status' => 'tracking',
                'last_checked_at' => now(),
            ]));
            // Create 7 days history
            for ($d = 6; $d >= 0; $d--) {
                SeoRankHistory::create([
                    'seo_keyword_id' => $k->id,
                    'rank' => max(1, $kw['current_rank'] + rand(-5, 5)),
                    'checked_date' => Carbon::now()->subDays($d)->toDateString(),
                ]);
            }
        }

        // ── SEO Audit Issues ──
        $issues = [
            ['page_url' => '/blog/post-1', 'issue_type' => 'missing_meta', 'severity' => 'warning', 'description' => 'Trang thiếu meta description', 'recommendation' => 'Thêm meta description 120-160 ký tự chứa keyword chính'],
            ['page_url' => '/services', 'issue_type' => 'no_h1', 'severity' => 'critical', 'description' => 'Trang dịch vụ thiếu thẻ H1', 'recommendation' => 'Thêm thẻ H1 chứa keyword "dịch vụ thiết kế website"'],
            ['page_url' => '/about', 'issue_type' => 'missing_alt', 'severity' => 'warning', 'description' => '3 hình ảnh thiếu alt text', 'recommendation' => 'Thêm alt text mô tả cho tất cả hình ảnh'],
            ['page_url' => '/portfolio', 'issue_type' => 'slow_page', 'severity' => 'critical', 'description' => 'Page load time > 4s', 'recommendation' => 'Tối ưu hình ảnh, enable lazy loading, minify CSS/JS'],
            ['page_url' => '/blog/post-5', 'issue_type' => 'thin_content', 'severity' => 'info', 'description' => 'Bài viết chỉ 200 từ', 'recommendation' => 'Bổ sung nội dung lên ít nhất 1000 từ'],
            ['page_url' => '/contact', 'issue_type' => 'broken_link', 'severity' => 'warning', 'description' => '2 link external bị hỏng', 'recommendation' => 'Cập nhật hoặc xóa link bị hỏng'],
        ];

        foreach ($issues as $issue) {
            SeoAuditIssue::create(array_merge($issue, ['account_id' => $accountId, 'status' => 'open']));
        }

        // ── Content Calendar ──
        $contents = [
            ['title' => '10 xu hướng thiết kế website 2026', 'content_type' => 'blog', 'channel' => 'website', 'status' => 'published', 'priority' => 'high', 'planned_date' => Carbon::now()->subDays(5), 'views_count' => 1240, 'clicks_count' => 89, 'shares_count' => 34, 'leads_count' => 12],
            ['title' => 'Case Study: Tăng 200% chuyển đổi cho ABC Corp', 'content_type' => 'case_study', 'channel' => 'website', 'status' => 'published', 'priority' => 'high', 'planned_date' => Carbon::now()->subDays(3), 'views_count' => 560, 'clicks_count' => 42],
            ['title' => 'Hướng dẫn SEO on-page cho người mới', 'content_type' => 'blog', 'channel' => 'website', 'status' => 'in_progress', 'priority' => 'medium', 'planned_date' => Carbon::now()->addDays(2)],
            ['title' => 'Carousel: 5 lý do nên dùng CRM', 'content_type' => 'social', 'channel' => 'facebook', 'status' => 'planned', 'priority' => 'medium', 'planned_date' => Carbon::now()->addDays(4)],
            ['title' => 'Video review BED CRM', 'content_type' => 'video', 'channel' => 'tiktok', 'status' => 'idea', 'priority' => 'low', 'planned_date' => Carbon::now()->addDays(7)],
            ['title' => 'Newsletter tháng 4', 'content_type' => 'email', 'channel' => 'website', 'status' => 'planned', 'priority' => 'high', 'planned_date' => Carbon::now()->addDays(10)],
            ['title' => 'Infographic: Quy trình marketing automation', 'content_type' => 'infographic', 'channel' => 'linkedin', 'status' => 'idea', 'priority' => 'medium', 'planned_date' => Carbon::now()->addDays(14)],
            ['title' => 'Post: Tips tối ưu tỉ lệ chuyển đổi', 'content_type' => 'social', 'channel' => 'instagram', 'status' => 'review', 'priority' => 'medium', 'planned_date' => Carbon::now()->addDays(1)],
        ];

        foreach ($contents as $c) {
            ContentCalendar::create(array_merge($c, [
                'account_id' => $accountId,
                'created_by' => $userId,
            ]));
        }
    }
}
