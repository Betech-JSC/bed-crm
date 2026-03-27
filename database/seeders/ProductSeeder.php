<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::first();

        $products = [
            // Services
            ['name' => 'Thiết kế website chuẩn SEO', 'sku' => 'SRV-WEB-SEO', 'type' => 'service', 'category' => 'Website Design', 'description' => 'Thiết kế website responsive, chuẩn SEO, tích hợp Google Analytics', 'unit' => 'project', 'unit_price' => 35000000, 'cost_price' => 12000000, 'tax_rate' => 10, 'is_active' => true],
            ['name' => 'Thiết kế website E-commerce', 'sku' => 'SRV-WEB-ECOM', 'type' => 'service', 'category' => 'Website Design', 'description' => 'Website bán hàng Online, tích hợp thanh toán, quản lý kho', 'unit' => 'project', 'unit_price' => 55000000, 'cost_price' => 20000000, 'tax_rate' => 10, 'is_active' => true],
            ['name' => 'Thiết kế website đa ngôn ngữ', 'sku' => 'SRV-WEB-MULTI', 'type' => 'service', 'category' => 'Website Design', 'description' => 'Website hỗ trợ EN/VI/CN/JP, phù hợp doanh nghiệp xuất khẩu', 'unit' => 'project', 'unit_price' => 45000000, 'cost_price' => 18000000, 'tax_rate' => 10, 'is_active' => true],
            ['name' => 'Landing Page', 'sku' => 'SRV-LP-01', 'type' => 'service', 'category' => 'Website Design', 'description' => 'Landing page chuyển đổi cao cho chiến dịch quảng cáo', 'unit' => 'page', 'unit_price' => 8000000, 'cost_price' => 3000000, 'tax_rate' => 10, 'is_active' => true],

            // Digital Marketing
            ['name' => 'SEO Package - Basic', 'sku' => 'SRV-SEO-BASIC', 'type' => 'service', 'category' => 'Digital Marketing', 'description' => 'Gói SEO cơ bản: 10 keywords, onpage + offpage, báo cáo hàng tháng', 'unit' => 'month', 'unit_price' => 8000000, 'cost_price' => 3500000, 'tax_rate' => 10, 'is_active' => true],
            ['name' => 'SEO Package - Pro', 'sku' => 'SRV-SEO-PRO', 'type' => 'service', 'category' => 'Digital Marketing', 'description' => 'Gói SEO nâng cao: 30 keywords, content marketing, link building', 'unit' => 'month', 'unit_price' => 18000000, 'cost_price' => 8000000, 'tax_rate' => 10, 'is_active' => true],
            ['name' => 'Google Ads Management', 'sku' => 'SRV-GADS-01', 'type' => 'service', 'category' => 'Digital Marketing', 'description' => 'Quản lý quảng cáo Google Ads, tối ưu CPC, báo cáo ROI', 'unit' => 'month', 'unit_price' => 12000000, 'cost_price' => 5000000, 'tax_rate' => 10, 'is_active' => true],
            ['name' => 'Social Media Management', 'sku' => 'SRV-SMM-01', 'type' => 'service', 'category' => 'Digital Marketing', 'description' => 'Quản lý fanpage, 12 bài/tháng, tương tác, báo cáo', 'unit' => 'month', 'unit_price' => 10000000, 'cost_price' => 4000000, 'tax_rate' => 10, 'is_active' => true],

            // Software Products
            ['name' => 'BED CRM - Starter', 'sku' => 'SW-CRM-START', 'type' => 'product', 'category' => 'Software', 'description' => 'Gói CRM Starter: 5 users, lead management, basic reporting', 'unit' => 'license/month', 'unit_price' => 2000000, 'cost_price' => 200000, 'tax_rate' => 10, 'is_active' => true],
            ['name' => 'BED CRM - Professional', 'sku' => 'SW-CRM-PRO', 'type' => 'product', 'category' => 'Software', 'description' => 'Gói CRM Pro: 20 users, automation, AI features, advanced reporting', 'unit' => 'license/month', 'unit_price' => 5000000, 'cost_price' => 500000, 'tax_rate' => 10, 'is_active' => true],
            ['name' => 'BED CRM - Enterprise', 'sku' => 'SW-CRM-ENT', 'type' => 'product', 'category' => 'Software', 'description' => 'Gói CRM Enterprise: Unlimited users, full AI, custom integrations, SLA', 'unit' => 'license/month', 'unit_price' => 15000000, 'cost_price' => 1500000, 'tax_rate' => 10, 'is_active' => true],
            ['name' => 'AI Chatbot Widget', 'sku' => 'SW-CHAT-01', 'type' => 'product', 'category' => 'Software', 'description' => 'Chatbot AI tích hợp website, trả lời tự động 24/7', 'unit' => 'license/month', 'unit_price' => 3000000, 'cost_price' => 300000, 'tax_rate' => 10, 'is_active' => true],

            // Consulting
            ['name' => 'Tư vấn chiến lược Digital', 'sku' => 'CON-DIG-01', 'type' => 'service', 'category' => 'Consulting', 'description' => 'Tư vấn chiến lược chuyển đổi số, digital marketing strategy', 'unit' => 'session', 'unit_price' => 5000000, 'cost_price' => 1500000, 'tax_rate' => 10, 'is_active' => true],
            ['name' => 'Đào tạo sử dụng CRM', 'sku' => 'CON-TRAIN-01', 'type' => 'service', 'category' => 'Consulting', 'description' => 'Khóa đào tạo 4 buổi, hướng dẫn sử dụng CRM hiệu quả', 'unit' => 'course', 'unit_price' => 8000000, 'cost_price' => 2000000, 'tax_rate' => 10, 'is_active' => true],
        ];

        foreach ($products as $index => $product) {
            Product::create(array_merge($product, [
                'account_id' => $account->id,
                'currency' => 'VND',
                'sort_order' => $index + 1,
            ]));
        }

        $this->command->info('Created ' . count($products) . ' products.');
    }
}
