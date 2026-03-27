<?php

namespace Database\Seeders;

use App\Models\WebForm;
use App\Models\WebFormField;
use App\Models\WebFormSubmission;
use Illuminate\Database\Seeder;

class WebFormSeeder extends Seeder
{
    public function run(): void
    {
        $accountId = 1;
        $userId = 1;

        // — Form 1: Contact form (inline)
        $form1 = WebForm::create([
            'account_id' => $accountId,
            'created_by' => $userId,
            'name' => 'Form liên hệ - Trang chủ',
            'description' => 'Form thu lead chính trên trang chủ website công ty',
            'form_type' => 'inline',
            'status' => 'active',
            'style_settings' => [
                'primary_color' => '#8b5cf6',
                'bg_color' => '#ffffff',
                'text_color' => '#1e293b',
                'border_radius' => 12,
                'button_text' => 'Nhận tư vấn miễn phí',
                'heading' => 'Cần thiết kế website?',
                'sub_heading' => 'Để lại thông tin, đội ngũ BED sẽ liên hệ trong 30 phút!',
            ],
            'success_action' => 'message',
            'success_message' => 'Cảm ơn! Chúng tôi sẽ liên hệ trong 30 phút.',
            'auto_create_lead' => true,
            'lead_source' => 'homepage_form',
            'lead_status' => 'new',
            'email_notify' => true,
            'notify_emails' => 'sales@betech.vn',
            'views_count' => 1250,
            'submissions_count' => 89,
        ]);

        $fields1 = [
            ['field_type' => 'text', 'label' => 'Họ tên', 'name' => 'name', 'placeholder' => 'Nhập họ tên...', 'is_required' => true, 'crm_mapping' => 'lead.contact_name', 'width' => 100, 'sort_order' => 0],
            ['field_type' => 'email', 'label' => 'Email', 'name' => 'email', 'placeholder' => 'email@company.com', 'is_required' => true, 'crm_mapping' => 'lead.email', 'width' => 50, 'sort_order' => 1],
            ['field_type' => 'phone', 'label' => 'Số điện thoại', 'name' => 'phone', 'placeholder' => '0901234567', 'is_required' => true, 'crm_mapping' => 'lead.phone', 'width' => 50, 'sort_order' => 2],
            ['field_type' => 'text', 'label' => 'Công ty', 'name' => 'company', 'placeholder' => 'Tên công ty', 'is_required' => false, 'crm_mapping' => 'lead.company', 'width' => 100, 'sort_order' => 3],
            ['field_type' => 'select', 'label' => 'Dịch vụ cần tư vấn', 'name' => 'service', 'is_required' => false, 'crm_mapping' => '', 'width' => 100, 'sort_order' => 4, 'options' => [['label' => 'Thiết kế website', 'value' => 'web_design'], ['label' => 'SEO', 'value' => 'seo'], ['label' => 'Marketing Online', 'value' => 'marketing'], ['label' => 'Khác', 'value' => 'other']]],
        ];

        foreach ($fields1 as $f) {
            $form1->fields()->create($f);
        }

        // Sample submissions
        $subs = [
            ['data' => ['name' => 'Nguyễn Văn Minh', 'email' => 'minh@abccorp.vn', 'phone' => '0912345678', 'company' => 'ABC Corp', 'service' => 'web_design'], 'utm_source' => 'google', 'utm_medium' => 'organic', 'status' => 'contacted'],
            ['data' => ['name' => 'Trần Thị Hương', 'email' => 'huong@xyztech.com', 'phone' => '0987654321', 'company' => 'XYZ Tech', 'service' => 'seo'], 'utm_source' => 'facebook', 'utm_medium' => 'social', 'utm_campaign' => 'spring_2026', 'status' => 'new'],
            ['data' => ['name' => 'Lê Hoàng Nam', 'email' => 'nam@greenlife.vn', 'phone' => '0901111222', 'company' => 'GreenLife JSC', 'service' => 'marketing'], 'utm_source' => 'referral', 'status' => 'new'],
        ];

        foreach ($subs as $s) {
            WebFormSubmission::create(array_merge($s, [
                'web_form_id' => $form1->id,
                'account_id' => $accountId,
                'page_url' => 'https://betech.vn',
            ]));
        }

        // — Form 2: Exit-intent popup
        $form2 = WebForm::create([
            'account_id' => $accountId,
            'created_by' => $userId,
            'name' => 'Popup khuyến mãi - Exit Intent',
            'description' => 'Popup hiện khi user sắp rời trang',
            'form_type' => 'popup',
            'status' => 'active',
            'style_settings' => [
                'primary_color' => '#ef4444',
                'bg_color' => '#ffffff',
                'text_color' => '#1e293b',
                'border_radius' => 16,
                'button_text' => 'Nhận ưu đãi ngay!',
                'heading' => '🎁 Giảm 20% thiết kế website!',
                'sub_heading' => 'Chỉ trong hôm nay — để lại email để nhận mã!',
            ],
            'trigger_settings' => ['type' => 'exit_intent', 'delay_seconds' => 3],
            'success_action' => 'redirect',
            'redirect_url' => 'https://betech.vn/thank-you',
            'auto_create_lead' => true,
            'lead_source' => 'exit_popup',
            'views_count' => 450,
            'submissions_count' => 34,
        ]);

        $form2->fields()->create(['field_type' => 'email', 'label' => 'Email', 'name' => 'email', 'placeholder' => 'Nhập email...', 'is_required' => true, 'crm_mapping' => 'lead.email', 'sort_order' => 0]);

        // — Form 3: Slide-in (paused)
        WebForm::create([
            'account_id' => $accountId,
            'created_by' => $userId,
            'name' => 'Slide-in chat nhanh',
            'form_type' => 'slide_in',
            'status' => 'paused',
            'auto_create_lead' => true,
            'lead_source' => 'slide_in',
            'views_count' => 80,
            'submissions_count' => 5,
        ]);
    }
}
