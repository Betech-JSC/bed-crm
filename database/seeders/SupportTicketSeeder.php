<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Customer;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SupportTicketSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::first();
        $users = User::where('account_id', $account->id)->get();
        $customers = Customer::where('account_id', $account->id)->get();

        $tickets = [
            ['subject' => 'Không gửi được email campaign', 'category' => 'technical', 'priority' => 'high', 'status' => 'open'],
            ['subject' => 'Lỗi hiển thị dashboard trên mobile', 'category' => 'bug', 'priority' => 'medium', 'status' => 'in_progress'],
            ['subject' => 'Hỏi cách import contacts từ Excel', 'category' => 'how_to', 'priority' => 'low', 'status' => 'resolved'],
            ['subject' => 'Tốc độ load trang chậm', 'category' => 'performance', 'priority' => 'high', 'status' => 'in_progress'],
            ['subject' => 'Yêu cầu thêm field custom cho Lead', 'category' => 'feature_request', 'priority' => 'medium', 'status' => 'open'],
            ['subject' => 'Automation email bị trùng lặp', 'category' => 'bug', 'priority' => 'urgent', 'status' => 'open'],
            ['subject' => 'Hỏi cách kết nối Facebook Ads', 'category' => 'how_to', 'priority' => 'low', 'status' => 'resolved'],
            ['subject' => 'Báo cáo doanh thu sai số liệu', 'category' => 'bug', 'priority' => 'high', 'status' => 'in_progress'],
            ['subject' => 'API rate limit quá thấp', 'category' => 'technical', 'priority' => 'medium', 'status' => 'open'],
            ['subject' => 'Yêu cầu export data ra PDF', 'category' => 'feature_request', 'priority' => 'low', 'status' => 'open'],
            ['subject' => 'Không login được - lỗi 500', 'category' => 'bug', 'priority' => 'urgent', 'status' => 'resolved'],
            ['subject' => 'Hướng dẫn setup Webhook', 'category' => 'how_to', 'priority' => 'medium', 'status' => 'resolved'],
            ['subject' => 'Chatbot không phản hồi', 'category' => 'technical', 'priority' => 'high', 'status' => 'in_progress'],
            ['subject' => 'Upgrade plan bị lỗi thanh toán', 'category' => 'billing', 'priority' => 'urgent', 'status' => 'open'],
            ['subject' => 'Cần training thêm cho team mới', 'category' => 'how_to', 'priority' => 'low', 'status' => 'closed'],
            ['subject' => 'Tích hợp Zalo OA không hoạt động', 'category' => 'technical', 'priority' => 'high', 'status' => 'open'],
            ['subject' => 'Lead form trên web bị lỗi validation', 'category' => 'bug', 'priority' => 'medium', 'status' => 'in_progress'],
            ['subject' => 'Hỏi về chính sách bảo mật dữ liệu', 'category' => 'general', 'priority' => 'low', 'status' => 'resolved'],
            ['subject' => 'Calendar sync Google bị xung đột', 'category' => 'bug', 'priority' => 'medium', 'status' => 'open'],
            ['subject' => 'Yêu cầu xóa dữ liệu theo GDPR', 'category' => 'compliance', 'priority' => 'high', 'status' => 'in_progress'],
        ];

        foreach ($tickets as $ticket) {
            $createdAt = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23));
            $isResolved = in_array($ticket['status'], ['resolved', 'closed']);
            $firstResponseAt = in_array($ticket['status'], ['in_progress', 'resolved', 'closed'])
                ? $createdAt->copy()->addMinutes(rand(5, 120))
                : null;

            SupportTicket::create(array_merge($ticket, [
                'account_id' => $account->id,
                'customer_id' => $customers->isNotEmpty() ? $customers->random()->id : null,
                'assigned_to' => rand(0, 1) ? $users->random()->id : null,
                'created_by' => $users->random()->id,
                'description' => 'Chi tiết về vấn đề: ' . $ticket['subject'] . '. Khách hàng báo lỗi khi sử dụng hệ thống.',
                'resolved_at' => $isResolved ? $createdAt->copy()->addHours(rand(1, 72)) : null,
                'first_response_at' => $firstResponseAt,
                'created_at' => $createdAt,
                'updated_at' => $createdAt->copy()->addHours(rand(1, 48)),
            ]));
        }

        $this->command->info('Created ' . count($tickets) . ' support tickets.');
    }
}
