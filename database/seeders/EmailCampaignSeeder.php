<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EmailCampaignSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::first();
        $user = User::where('account_id', $account->id)->first();
        $templates = EmailTemplate::where('account_id', $account->id)->get();

        $campaigns = [
            ['name' => 'Welcome Series - Q1 2026', 'subject' => 'Chào mừng đến với BED CRM! 🎉', 'status' => 'sent', 'sent_at' => Carbon::now()->subDays(15), 'total_recipients' => 340, 'delivered_count' => 332, 'opened_count' => 245, 'clicked_count' => 89, 'bounced_count' => 8, 'unsubscribed_count' => 2],
            ['name' => 'Newsletter Tháng 3/2026', 'subject' => '📮 Bản tin tháng 3 - Tính năng mới!', 'status' => 'sent', 'sent_at' => Carbon::now()->subDays(5), 'total_recipients' => 2450, 'delivered_count' => 2380, 'opened_count' => 980, 'clicked_count' => 320, 'bounced_count' => 70, 'unsubscribed_count' => 15],
            ['name' => 'Flash Sale - Enterprise Plan', 'subject' => '🔥 GIẢM 40% Enterprise Plan - 48h cuối!', 'status' => 'sent', 'sent_at' => Carbon::now()->subDays(10), 'total_recipients' => 1200, 'delivered_count' => 1158, 'opened_count' => 620, 'clicked_count' => 180, 'bounced_count' => 42, 'unsubscribed_count' => 8],
            ['name' => 'Webinar Invitation - AI Sales', 'subject' => '🎓 Mời tham dự: AI trong Sales 2026', 'status' => 'sent', 'sent_at' => Carbon::now()->subDays(20), 'total_recipients' => 800, 'delivered_count' => 785, 'opened_count' => 410, 'clicked_count' => 156, 'bounced_count' => 15, 'unsubscribed_count' => 3],
            ['name' => 'Re-engagement - Inactive Users', 'subject' => 'Chúng tôi nhớ bạn! Quay lại nhé? 👋', 'status' => 'sent', 'sent_at' => Carbon::now()->subDays(8), 'total_recipients' => 620, 'delivered_count' => 590, 'opened_count' => 180, 'clicked_count' => 45, 'bounced_count' => 30, 'unsubscribed_count' => 12],
            ['name' => 'Product Launch - AI Chat', 'subject' => '🚀 RA MẮT: AI Chat 24/7 cho website!', 'status' => 'scheduled', 'scheduled_at' => Carbon::now()->addDays(3), 'total_recipients' => 0, 'delivered_count' => 0, 'opened_count' => 0, 'clicked_count' => 0, 'bounced_count' => 0, 'unsubscribed_count' => 0],
            ['name' => 'Customer Survey Q1', 'subject' => '📋 Khảo sát 2 phút - Nhận voucher 500K', 'status' => 'draft', 'total_recipients' => 0, 'delivered_count' => 0, 'opened_count' => 0, 'clicked_count' => 0, 'bounced_count' => 0, 'unsubscribed_count' => 0],
            ['name' => 'Upgrade Upsell - Pro Plan', 'subject' => 'Nâng cấp Pro: Thêm 10 tính năng AI!', 'status' => 'draft', 'total_recipients' => 0, 'delivered_count' => 0, 'opened_count' => 0, 'clicked_count' => 0, 'bounced_count' => 0, 'unsubscribed_count' => 0],
        ];

        foreach ($campaigns as $camp) {
            $delivered = $camp['delivered_count'];
            $opened = $camp['opened_count'];

            EmailCampaign::create(array_merge($camp, [
                'account_id' => $account->id,
                'created_by' => $user->id,
                'email_template_id' => $templates->isNotEmpty() ? $templates->random()->id : null,
                'description' => 'Campaign ' . $camp['name'],
                'sent_count' => $delivered, // approximate
                'open_rate' => $delivered > 0 ? round(($opened / $delivered) * 100, 2) : 0,
                'click_rate' => $delivered > 0 ? round(($camp['clicked_count'] / $delivered) * 100, 2) : 0,
                'body_html' => '<div style="padding:20px"><h2>' . $camp['subject'] . '</h2><p>Nội dung campaign...</p></div>',
                'body_text' => strip_tags($camp['subject']),
            ]));
        }

        $this->command->info('Created ' . count($campaigns) . ' email campaigns.');
    }
}
