<?php

namespace Database\Seeders;

use App\Models\PersonalTask;
use Illuminate\Database\Seeder;

class PersonalTaskSeeder extends Seeder
{
    public function run(): void
    {
        $accountId = 1;
        $userId = 1;

        $tasks = [
            [
                'title' => 'Review báo giá cho khách hàng ABC Corp',
                'description' => 'Kiểm tra lại giá dịch vụ thiết kế web & SEO cho ABC Corp. Đảm bảo margin tối thiểu 30%.',
                'status' => 'in_progress',
                'priority' => 'high',
                'category' => 'work',
                'due_date' => now()->addDays(1),
                'is_pinned' => true,
                'checklist' => [
                    ['text' => 'Check giá gốc từ đội dev', 'done' => true],
                    ['text' => 'So sánh với đối thủ cạnh tranh', 'done' => true],
                    ['text' => 'Tính toán margin', 'done' => false],
                    ['text' => 'Gửi cho sếp duyệt', 'done' => false],
                ],
                'tags' => ['báo-giá', 'ABC-Corp'],
            ],
            [
                'title' => 'Chuẩn bị slide cho meeting Sprint Review',
                'description' => 'Tổng hợp kết quả sprint 2 tuần qua, demo tính năng mới cho stakeholders.',
                'status' => 'todo',
                'priority' => 'high',
                'category' => 'meeting',
                'due_date' => now()->addDays(2),
                'is_pinned' => true,
                'tags' => ['meeting', 'sprint'],
            ],
            [
                'title' => 'Follow-up khách hàng XYZ sau demo',
                'description' => 'Gọi điện hoặc email cho anh Minh bên XYZ để hỏi feedback về bản demo tuần trước.',
                'status' => 'todo',
                'priority' => 'medium',
                'category' => 'follow_up',
                'due_date' => now(),
                'tags' => ['follow-up', 'XYZ'],
            ],
            [
                'title' => 'Cập nhật portfolio website',
                'description' => 'Thêm 3 dự án mới vào trang portfolio: E-commerce cho TechVN, Landing page Greenlife, Corporate site DataPro.',
                'status' => 'todo',
                'priority' => 'medium',
                'category' => 'work',
                'due_date' => now()->addDays(5),
                'checklist' => [
                    ['text' => 'Chụp screenshot các dự án', 'done' => false],
                    ['text' => 'Viết case study ngắn', 'done' => false],
                    ['text' => 'Upload và publish', 'done' => false],
                ],
                'tags' => ['portfolio', 'marketing'],
            ],
            [
                'title' => 'Học thêm về Vue 3 Composition API',
                'description' => 'Xem video course trên Laracasts về Vue 3 setup, ref, reactive, computed.',
                'status' => 'in_progress',
                'priority' => 'low',
                'category' => 'personal',
                'due_date' => now()->addDays(7),
                'tags' => ['learning', 'vue'],
            ],
            [
                'title' => 'Gửi invoice tháng 3 cho khách hàng',
                'description' => 'Tạo và gửi invoice cho tất cả khách hàng có hợp đồng tháng 3.',
                'status' => 'done',
                'priority' => 'high',
                'category' => 'work',
                'due_date' => now()->subDays(2),
                'completed_at' => now()->subDay(),
                'tags' => ['invoice', 'tài-chính'],
            ],
            [
                'title' => 'Đặt lịch khám sức khỏe định kỳ',
                'status' => 'todo',
                'priority' => 'low',
                'category' => 'personal',
                'due_date' => now()->addDays(14),
            ],
            [
                'title' => 'Kiểm tra SSL sắp hết hạn cho 5 domain',
                'description' => 'Renew SSL cho: techvn.com, greenlife.vn, datapro.io, betech.vn, bedcrm.com',
                'status' => 'todo',
                'priority' => 'urgent',
                'category' => 'work',
                'due_date' => now()->subDays(1),
                'checklist' => [
                    ['text' => 'techvn.com', 'done' => true],
                    ['text' => 'greenlife.vn', 'done' => false],
                    ['text' => 'datapro.io', 'done' => false],
                    ['text' => 'betech.vn', 'done' => false],
                    ['text' => 'bedcrm.com', 'done' => false],
                ],
                'tags' => ['ssl', 'devops', 'khẩn-cấp'],
            ],
            [
                'title' => 'Viết blog post về CRM cho doanh nghiệp nhỏ',
                'status' => 'todo',
                'priority' => 'medium',
                'category' => 'work',
                'due_date' => now()->addDays(10),
                'tags' => ['content', 'blog', 'seo'],
            ],
            [
                'title' => 'Backup database production',
                'status' => 'done',
                'priority' => 'high',
                'category' => 'work',
                'completed_at' => now()->subHours(3),
                'tags' => ['devops'],
            ],
        ];

        foreach ($tasks as $task) {
            PersonalTask::create(array_merge($task, [
                'account_id' => $accountId,
                'user_id' => $userId,
            ]));
        }
    }
}
