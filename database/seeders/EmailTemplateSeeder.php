<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::first();
        $user = User::where('account_id', $account->id)->first();

        $templates = [
            [
                'name' => 'Welcome Email',
                'subject' => 'Chào mừng bạn đến với {{company_name}}!',
                'type' => 'transactional',
                'body_html' => '<div style="font-family:sans-serif;max-width:600px;margin:0 auto"><div style="background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:30px;text-align:center;border-radius:12px 12px 0 0"><h1 style="color:#fff;margin:0">Chào mừng! 🎉</h1></div><div style="padding:30px;background:#fff"><p>Xin chào <strong>{{customer_name}}</strong>,</p><p>Cảm ơn bạn đã đăng ký {{company_name}}. Bắt đầu tại đây:</p><a href="{{login_url}}" style="display:inline-block;background:#6366f1;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600">Bắt đầu ngay →</a></div></div>',
                'body_text' => 'Chào mừng {{customer_name}}, Cảm ơn bạn đã đăng ký. Truy cập: {{login_url}}',
                'is_active' => true,
            ],
            [
                'name' => 'Newsletter Monthly',
                'subject' => '📮 Bản tin tháng {{month}} - Tin mới & Tips',
                'type' => 'campaign',
                'body_html' => '<div style="font-family:sans-serif;max-width:600px;margin:0 auto"><div style="background:#1e293b;padding:25px;text-align:center;border-radius:12px 12px 0 0"><h1 style="color:#fff;margin:0;font-size:22px">📮 Bản Tin Tháng {{month}}</h1></div><div style="padding:25px;background:#fff"><h2>🆕 Tính năng mới</h2><p>{{new_features}}</p><h2>💡 Tips of the month</h2><p>{{tips}}</p><h2>📅 Sự kiện sắp tới</h2><p>{{events}}</p></div></div>',
                'body_text' => 'Bản tin tháng {{month}}. Tính năng mới: {{new_features}}. Tips: {{tips}}.',
                'is_active' => true,
            ],
            [
                'name' => 'Promotional Sale',
                'subject' => '🔥 {{discount}}% OFF - Chỉ còn {{days}} ngày!',
                'type' => 'campaign',
                'body_html' => '<div style="font-family:sans-serif;max-width:600px;margin:0 auto"><div style="background:linear-gradient(135deg,#f43f5e,#ec4899);padding:35px;text-align:center;border-radius:12px 12px 0 0"><h1 style="color:#fff;margin:0;font-size:36px">SALE {{discount}}%</h1><p style="color:#fecdd3;margin:5px 0 0">Chỉ còn {{days}} ngày!</p></div><div style="padding:25px;background:#fff;text-align:center"><p>Nâng cấp plan ngay để nhận ưu đãi đặc biệt.</p><a href="{{promo_url}}" style="display:inline-block;background:#f43f5e;color:#fff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:700;font-size:16px">MUA NGAY →</a><p style="color:#94a3b8;font-size:12px">Mã: {{promo_code}}</p></div></div>',
                'body_text' => 'Sale {{discount}}%! Chỉ còn {{days}} ngày. Mua ngay: {{promo_url}}. Mã: {{promo_code}}',
                'is_active' => true,
            ],
            [
                'name' => 'Follow-up After Demo',
                'subject' => 'Cảm ơn bạn đã demo - Bước tiếp theo?',
                'type' => 'transactional',
                'body_html' => '<div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:30px;background:#fff"><p>Xin chào {{contact_name}},</p><p>Cảm ơn bạn đã dành thời gian xem demo BED CRM hôm {{demo_date}}.</p><p>Một số điểm nổi bật:</p><ul><li>✅ AI Lead Scoring tự động</li><li>✅ Email Marketing Automation</li><li>✅ Báo cáo doanh thu realtime</li></ul><p>Bước tiếp theo:</p><a href="{{trial_url}}" style="display:inline-block;background:#10b981;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600">Bắt đầu dùng thử miễn phí →</a></div>',
                'body_text' => 'Cảm ơn {{contact_name}} đã demo. Dùng thử: {{trial_url}}',
                'is_active' => true,
            ],
            [
                'name' => 'Re-engagement',
                'subject' => 'Chúng tôi nhớ bạn! Quay lại nhé?',
                'type' => 'campaign',
                'body_html' => '<div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:30px;background:#fff;text-align:center"><h2>Chúng tôi nhớ bạn! 👋</h2><p>Đã lâu không thấy {{customer_name}} đăng nhập. Có gì mới nè:</p><ul style="text-align:left"><li>🆕 AI Content Studio</li><li>🆕 Video Ads Creator</li><li>🆕 Outbound Sales Automation</li></ul><a href="{{login_url}}" style="display:inline-block;background:#6366f1;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600">Khám phá ngay →</a></div>',
                'body_text' => 'Chúng tôi nhớ {{customer_name}}! Quay lại khám phá tính năng mới: {{login_url}}',
                'is_active' => true,
            ],
            [
                'name' => 'Invoice / Receipt',
                'subject' => 'Hóa đơn #{{invoice_number}} - {{company_name}}',
                'type' => 'transactional',
                'body_html' => '<div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:30px;background:#fff"><h2>Hóa đơn #{{invoice_number}}</h2><p>Cảm ơn bạn đã thanh toán.</p><table style="width:100%;border-collapse:collapse"><tr style="background:#f8fafc"><td style="padding:10px;border:1px solid #e2e8f0">Gói dịch vụ</td><td style="padding:10px;border:1px solid #e2e8f0;text-align:right">{{plan_name}}</td></tr><tr><td style="padding:10px;border:1px solid #e2e8f0">Tổng cộng</td><td style="padding:10px;border:1px solid #e2e8f0;text-align:right;font-weight:700">{{total_amount}}</td></tr></table></div>',
                'body_text' => 'Hóa đơn #{{invoice_number}}. Plan: {{plan_name}}. Tổng: {{total_amount}}',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $tpl) {
            EmailTemplate::create(array_merge($tpl, [
                'account_id' => $account->id,
                'created_by' => $user->id,
            ]));
        }

        $this->command->info('Created ' . count($templates) . ' email templates.');
    }
}
