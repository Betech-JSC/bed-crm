<?php

namespace Database\Seeders;

use App\Models\UtmLink;
use Illuminate\Database\Seeder;

class UtmLinkSeeder extends Seeder
{
    public function run(): void
    {
        $accountId = 1;
        $userId = 1;

        $links = [
            ['name' => 'Facebook Spring Campaign', 'base_url' => 'https://betech.vn/thiet-ke-website', 'utm_source' => 'facebook', 'utm_medium' => 'social', 'utm_campaign' => 'spring_2026', 'clicks_count' => 342, 'leads_count' => 28],
            ['name' => 'Google Ads - Web Design', 'base_url' => 'https://betech.vn/landing-web-design', 'utm_source' => 'google', 'utm_medium' => 'cpc', 'utm_campaign' => 'web_design_q1', 'utm_term' => 'thiết kế website', 'clicks_count' => 890, 'leads_count' => 67],
            ['name' => 'Email Newsletter - March', 'base_url' => 'https://betech.vn/blog', 'utm_source' => 'newsletter', 'utm_medium' => 'email', 'utm_campaign' => 'march_news', 'clicks_count' => 125, 'leads_count' => 8],
            ['name' => 'Zalo OA Post', 'base_url' => 'https://betech.vn/seo-services', 'utm_source' => 'zalo', 'utm_medium' => 'social', 'utm_campaign' => 'seo_promo', 'clicks_count' => 78, 'leads_count' => 5],
            ['name' => 'LinkedIn B2B Outreach', 'base_url' => 'https://betech.vn/enterprise', 'utm_source' => 'linkedin', 'utm_medium' => 'social', 'utm_campaign' => 'b2b_enterprise', 'utm_content' => 'banner_v2', 'clicks_count' => 45, 'leads_count' => 12],
            ['name' => 'TikTok Brand Video', 'base_url' => 'https://betech.vn/portfolio', 'utm_source' => 'tiktok', 'utm_medium' => 'video', 'utm_campaign' => 'portfolio_showcase', 'clicks_count' => 560, 'leads_count' => 15],
        ];

        foreach ($links as $l) {
            UtmLink::create(array_merge($l, [
                'account_id' => $accountId,
                'created_by' => $userId,
            ]));
        }
    }
}
