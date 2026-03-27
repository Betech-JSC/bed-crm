<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ContentItem;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SocialPostSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::first();
        $user = User::where('account_id', $account->id)->first();
        $socialAccounts = SocialAccount::where('account_id', $account->id)->where('is_active', true)->get();
        $contentItems = ContentItem::where('account_id', $account->id)->get();

        if ($socialAccounts->isEmpty()) {
            $this->command->warn('No social accounts found. Run SocialAccountSeeder first.');
            return;
        }

        $statuses = ['draft', 'scheduled', 'published', 'published', 'published', 'failed'];

        for ($i = 0; $i < 30; $i++) {
            $status = $statuses[array_rand($statuses)];
            $socialAccount = $socialAccounts->random();
            $contentItem = $contentItems->isNotEmpty() ? $contentItems->random() : null;
            $scheduledAt = $status === 'scheduled' ? Carbon::now()->addDays(rand(1, 14))->addHours(rand(8, 20)) : null;
            $postedAt = $status === 'published' ? Carbon::now()->subDays(rand(0, 30))->subHours(rand(1, 12)) : null;

            $contents = [
                '🚀 Khám phá sức mạnh AI trong quản lý kinh doanh! BED CRM giúp bạn tự động hóa mọi quy trình. #BEDCRM #AI #Automation',
                '💡 Mẹo: Follow up leads trong 5 phút đầu tiên sẽ tăng tỷ lệ chốt deal 400%! Bạn đã thử chưa? #Sales #Tips',
                '🎉 Cảm ơn 5000+ doanh nghiệp đã tin tưởng sử dụng BED CRM! Chúng tôi tiếp tục nâng cấp để phục vụ bạn tốt hơn. ❤️',
                '📊 Báo cáo mới: AI giúp giảm 60% thời gian nhập liệu cho sales team. Xem chi tiết tại [link] #AIReport',
                '🔥 Tính năng mới: Email Marketing Automation - Gửi email đúng người, đúng thời điểm, tự động 100%! #EmailMarketing',
                '💬 "BED CRM đã thay đổi cách chúng tôi quản lý khách hàng" - Anh Minh, CEO ABC Trading. Đọc case study → [link]',
                '🎓 Webinar miễn phí: "AI trong Sales - Từ Lead đến Deal" 📅 Thứ 6 tuần này, 14h. Đăng ký ngay! 👇',
                '✅ Checklist chuyển đổi số cho doanh nghiệp SME:\n1. CRM System\n2. Email Automation\n3. AI Chatbot\n4. Data Analytics\n\nBạn đã có mấy cái? 🤔',
            ];

            SocialPost::create([
                'account_id' => $account->id,
                'content_item_id' => $contentItem?->id,
                'social_account_id' => $socialAccount->id,
                'created_by' => $user->id,
                'platform' => $socialAccount->platform,
                'content' => $contents[array_rand($contents)],
                'status' => $status,
                'scheduled_at' => $scheduledAt,
                'posted_at' => $postedAt,
                'platform_post_id' => $status === 'published' ? fake()->numerify('post_##########') : null,
                'error_message' => $status === 'failed' ? collect(['Token expired', 'Rate limit exceeded', 'Content policy violation', 'Connection timeout'])->random() : null,
                'retry_count' => $status === 'failed' ? rand(1, 3) : 0,
                'analytics' => $status === 'published' ? [
                    'likes' => rand(10, 500),
                    'comments' => rand(2, 80),
                    'shares' => rand(1, 50),
                    'views' => rand(200, 10000),
                ] : null,
                'analytics_synced_at' => $status === 'published' ? Carbon::now()->subHours(rand(1, 48)) : null,
            ]);
        }

        $this->command->info('Created 30 social posts.');
    }
}
