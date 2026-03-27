<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\EmailList;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmailListSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::first();
        $user = User::where('account_id', $account->id)->first();

        $lists = [
            ['name' => 'All Subscribers', 'description' => 'Tất cả người đăng ký nhận email', 'type' => 'manual', 'contacts_count' => 2450, 'is_active' => true],
            ['name' => 'Hot Leads', 'description' => 'Leads điểm cao, quan tâm sản phẩm', 'type' => 'dynamic', 'contacts_count' => 380, 'is_active' => true],
            ['name' => 'Khách hàng hiện tại', 'description' => 'Customers đang sử dụng dịch vụ', 'type' => 'manual', 'contacts_count' => 520, 'is_active' => true],
            ['name' => 'Webinar Attendees', 'description' => 'Người tham gia webinar', 'type' => 'manual', 'contacts_count' => 180, 'is_active' => true],
            ['name' => 'Inactive 30 days', 'description' => 'Không hoạt động > 30 ngày', 'type' => 'dynamic', 'contacts_count' => 620, 'is_active' => true],
            ['name' => 'VIP Customers', 'description' => 'Khách hàng VIP, MRR > 10M', 'type' => 'dynamic', 'contacts_count' => 45, 'is_active' => true],
            ['name' => 'Unsubscribed', 'description' => 'Đã hủy đăng ký', 'type' => 'manual', 'contacts_count' => 89, 'is_active' => false],
        ];

        foreach ($lists as $list) {
            EmailList::create(array_merge($list, [
                'account_id' => $account->id,
                'created_by' => $user->id,
            ]));
        }

        $this->command->info('Created ' . count($lists) . ' email lists.');
    }
}
