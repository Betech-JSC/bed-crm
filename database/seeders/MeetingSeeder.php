<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class MeetingSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::first();
        $users = User::where('account_id', $account->id)->get();
        $userIds = $users->pluck('id')->toArray();

        $meetings = [
            ['title' => 'Sprint Planning - Sprint 12', 'type' => 'team', 'status' => 'completed', 'duration_minutes' => 60, 'ai_summary' => 'Đã plan 15 tasks cho Sprint 12. Focus chính: Email Marketing module, Social Media optimization.'],
            ['title' => 'Demo khách hàng ABC Trading', 'type' => 'external', 'status' => 'completed', 'duration_minutes' => 45, 'ai_summary' => 'Demo thành công CRM cho ABC Trading. Khách quan tâm features: Lead scoring, Email automation. Next step: Gửi proposal.'],
            ['title' => 'Weekly Standup - Team Dev', 'type' => 'team', 'status' => 'completed', 'duration_minutes' => 30, 'ai_summary' => 'Team completed 8/10 tasks. Blocker: API integration delay. Action: Frontend team chờ backend API ready.'],
            ['title' => 'Họp Chiến Lược Q2 2026', 'type' => 'team', 'status' => 'scheduled', 'duration_minutes' => 120, 'ai_summary' => null],
            ['title' => 'Training: Sử dụng Content Studio', 'type' => 'team', 'status' => 'scheduled', 'duration_minutes' => 90, 'ai_summary' => null],
            ['title' => 'Phỏng vấn Senior Developer', 'type' => 'external', 'status' => 'scheduled', 'duration_minutes' => 60, 'ai_summary' => null],
            ['title' => 'Review thiết kế UI/UX - Mobile App', 'type' => 'team', 'status' => 'completed', 'duration_minutes' => 45, 'ai_summary' => 'Đã review 12 screens. Cần chỉnh sửa: Dashboard layout, color scheme cho dark mode. Designer update trong 2 ngày.'],
            ['title' => 'Họp với đối tác - Agency X', 'type' => 'external', 'status' => 'completed', 'duration_minutes' => 60, 'ai_summary' => 'Thảo luận hợp tác referral program. Agency X sẽ giới thiệu BED CRM cho khách hàng. Commission: 15%.'],
            ['title' => 'All-hands Meeting T3/2026', 'type' => 'team', 'status' => 'completed', 'duration_minutes' => 90, 'ai_summary' => 'Review kết quả Q1: Revenue +25% YoY. 12 khách hàng mới. Mục tiêu Q2: 20 khách mới, launch 3 features.'],
            ['title' => 'Customer Success Review - At-risk accounts', 'type' => 'team', 'status' => 'scheduled', 'duration_minutes' => 45, 'ai_summary' => null],
        ];

        foreach ($meetings as $meeting) {
            $isCompleted = $meeting['status'] === 'completed';
            $scheduledAt = $isCompleted
                ? Carbon::now()->subDays(rand(1, 30))->setHour(rand(9, 17))->setMinute(0)
                : Carbon::now()->addDays(rand(1, 14))->setHour(rand(9, 17))->setMinute(0);
            $startedAt = $isCompleted ? $scheduledAt->copy() : null;
            $endedAt = $isCompleted ? $scheduledAt->copy()->addMinutes($meeting['duration_minutes']) : null;

            // Pick 2-5 random participants
            $participantCount = rand(2, min(5, count($userIds)));
            $participants = collect($userIds)->random($participantCount)->values()->toArray();

            Meeting::create([
                'account_id' => $account->id,
                'created_by' => $users->first()->id,
                'title' => $meeting['title'],
                'description' => 'Cuộc họp: ' . $meeting['title'],
                'type' => $meeting['type'],
                'status' => $meeting['status'],
                'scheduled_at' => $scheduledAt,
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'duration_minutes' => $meeting['duration_minutes'],
                'participants' => $participants,
                'max_participants' => 20,
                'is_public' => false,
                'record_enabled' => $isCompleted,
                'ai_summary' => $meeting['ai_summary'],
                'ai_action_items' => $isCompleted ? [
                    'Follow up với khách hàng',
                    'Cập nhật tài liệu nội bộ',
                    'Lên kế hoạch sprint tiếp theo',
                ] : null,
                'ai_key_decisions' => $isCompleted ? [
                    'Quyết định ưu tiên module Email Marketing',
                    'Phân công resources cho Q2',
                ] : null,
                'ai_topics' => $isCompleted ? ['review', 'planning', 'strategy'] : null,
                'agenda' => '1. Review tiến độ\n2. Thảo luận vấn đề\n3. Kế hoạch tiếp theo\n4. Q&A',
            ]);
        }

        $this->command->info('Created ' . count($meetings) . ' meetings.');
    }
}
