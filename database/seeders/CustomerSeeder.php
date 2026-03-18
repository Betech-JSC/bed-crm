<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\SupportTicket;
use App\Models\UpsellOpportunity;
use App\Models\CustomerHealthLog;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $accountId = 1;

        $samples = [
            ['name' => 'Công ty CP Kỹ thuật ABC', 'email' => 'abc@company.vn', 'lifecycle_status' => 'active', 'mrr' => 15000000, 'health_score' => 85, 'contract_end' => now()->addMonths(6)],
            ['name' => 'Tập đoàn TechViet', 'email' => 'info@techviet.vn', 'lifecycle_status' => 'active', 'mrr' => 35000000, 'health_score' => 92, 'contract_end' => now()->addMonths(8)],
            ['name' => 'TNHH Phát Triển Xanh', 'email' => 'green@dev.vn', 'lifecycle_status' => 'at_risk', 'mrr' => 8000000, 'health_score' => 35, 'contract_end' => now()->addDays(20)],
            ['name' => 'Công ty Giải pháp Số', 'email' => 'digital@solution.vn', 'lifecycle_status' => 'onboarding', 'mrr' => 12000000, 'health_score' => 60, 'contract_end' => now()->addYear()],
            ['name' => 'TNHH Thương mại Bình Minh', 'email' => 'binhminh@trade.vn', 'lifecycle_status' => 'active', 'mrr' => 22000000, 'health_score' => 78, 'contract_end' => now()->addMonths(3)],
            ['name' => 'Công ty CP Xây dựng Hùng Vương', 'email' => 'hungvuong@xd.vn', 'lifecycle_status' => 'churned', 'mrr' => 0, 'health_score' => 15, 'contract_end' => now()->subMonth()],
            ['name' => 'Startup CloudOne', 'email' => 'hello@cloudone.io', 'lifecycle_status' => 'active', 'mrr' => 5000000, 'health_score' => 72, 'contract_end' => now()->addMonths(10)],
        ];

        foreach ($samples as $data) {
            $customer = Customer::create(array_merge($data, [
                'account_id' => $accountId,
                'arr' => ($data['mrr'] ?? 0) * 12,
                'contract_start' => now()->subYear(),
                'contract_term' => 'yearly',
                'start_date' => now()->subMonths(rand(3, 18)),
                'health_factors' => [
                    'engagement' => rand(30, 100),
                    'support' => rand(40, 100),
                    'payment' => rand(50, 100),
                    'renewal' => rand(20, 100),
                    'relationship' => rand(30, 100),
                ],
            ]));

            // Add health logs
            for ($i = 5; $i >= 0; $i--) {
                CustomerHealthLog::create([
                    'customer_id' => $customer->id,
                    'score' => max(0, min(100, $customer->health_score + rand(-15, 10))),
                    'trigger' => 'scheduled',
                    'created_at' => now()->subDays($i * 7),
                ]);
            }

            // Add tickets for some customers
            if (in_array($data['lifecycle_status'], ['active', 'at_risk'])) {
                SupportTicket::create([
                    'account_id' => $accountId,
                    'customer_id' => $customer->id,
                    'subject' => 'Lỗi tính năng xuất báo cáo',
                    'priority' => $data['lifecycle_status'] === 'at_risk' ? 'urgent' : 'medium',
                    'status' => 'open',
                    'category' => 'bug',
                ]);
            }

            // Add upsells for active customers
            if ($data['lifecycle_status'] === 'active' && $data['mrr'] > 10000000) {
                UpsellOpportunity::create([
                    'account_id' => $accountId,
                    'customer_id' => $customer->id,
                    'title' => 'Nâng cấp gói Premium',
                    'value' => $data['mrr'] * 0.5,
                    'type' => 'upsell',
                    'status' => 'identified',
                ]);
            }
        }
    }
}
