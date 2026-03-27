<?php

namespace Database\Seeders;

use App\Models\LinkBioPage;
use App\Models\QrCode;
use Illuminate\Database\Seeder;

class GrowthToolsSeeder extends Seeder
{
    public function run(): void
    {
        $accountId = 1;
        $userId = 1;

        // Bio Pages
        LinkBioPage::create([
            'account_id' => $accountId, 'created_by' => $userId,
            'username' => 'betech', 'display_name' => 'BETECH JSC',
            'bio' => 'AI-Powered CRM & Digital Solutions',
            'theme' => 'gradient',
            'links' => [
                ['title' => '🌐 Website chính', 'url' => 'https://betech.vn'],
                ['title' => '💼 BED CRM', 'url' => 'https://crm.betech.vn'],
                ['title' => '📱 Liên hệ tư vấn', 'url' => 'https://betech.vn/contact'],
                ['title' => '📖 Blog công nghệ', 'url' => 'https://betech.vn/blog'],
            ],
            'social_links' => [
                ['platform' => 'Facebook', 'url' => 'https://facebook.com/betech'],
                ['platform' => 'LinkedIn', 'url' => 'https://linkedin.com/company/betech'],
            ],
            'total_views' => 2340, 'total_clicks' => 890,
        ]);

        LinkBioPage::create([
            'account_id' => $accountId, 'created_by' => $userId,
            'username' => 'toan-nguyen', 'display_name' => 'Toan Nguyen',
            'bio' => 'Founder & CEO @ BETECH JSC',
            'theme' => 'dark',
            'links' => [
                ['title' => '🚀 BED CRM Demo', 'url' => 'https://demo.betech.vn'],
                ['title' => '💡 About me', 'url' => 'https://toannguyen.dev'],
            ],
            'total_views' => 560, 'total_clicks' => 180,
        ]);

        // QR Codes
        $qrs = [
            ['name' => 'Website Homepage', 'target_url' => 'https://betech.vn', 'qr_type' => 'url', 'scans_count' => 456, 'unique_scans' => 312],
            ['name' => 'Product Brochure', 'target_url' => 'https://betech.vn/brochure', 'qr_type' => 'url', 'scans_count' => 89, 'unique_scans' => 67],
            ['name' => 'WiFi Guest Office', 'target_url' => 'https://betech.vn', 'qr_type' => 'wifi', 'scans_count' => 234, 'unique_scans' => 180],
            ['name' => 'CEO vCard', 'target_url' => 'https://betech.vn/contact', 'qr_type' => 'vcard', 'scans_count' => 45, 'unique_scans' => 38],
        ];

        foreach ($qrs as $qr) {
            QrCode::create(array_merge($qr, [
                'account_id' => $accountId,
                'created_by' => $userId,
                'design' => ['foreground' => '#000000', 'background' => '#ffffff'],
            ]));
        }
    }
}
