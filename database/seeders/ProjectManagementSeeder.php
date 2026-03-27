<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ProjectManagementSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::first();
        $users = User::where('account_id', $account->id)->get();
        $customers = Customer::where('account_id', $account->id)->take(8)->get();

        $projects = [
            ['name' => 'Website ABC Trading', 'status' => 'in_progress', 'priority' => 'high', 'progress' => 65, 'budget' => 45000000, 'revenue' => 55000000, 'total_cost' => 22000000],
            ['name' => 'E-commerce XYZ Store', 'status' => 'in_progress', 'priority' => 'high', 'progress' => 40, 'budget' => 80000000, 'revenue' => 95000000, 'total_cost' => 35000000],
            ['name' => 'Landing Page Campaign Q1', 'status' => 'completed', 'priority' => 'medium', 'progress' => 100, 'budget' => 12000000, 'revenue' => 15000000, 'total_cost' => 8000000],
            ['name' => 'SEO Dragon Export', 'status' => 'in_progress', 'priority' => 'medium', 'progress' => 30, 'budget' => 96000000, 'revenue' => 120000000, 'total_cost' => 40000000],
            ['name' => 'CRM Integration - Lotus Digital', 'status' => 'planning', 'priority' => 'high', 'progress' => 10, 'budget' => 60000000, 'revenue' => 72000000, 'total_cost' => 5000000],
            ['name' => 'Redesign Saigon Furniture Web', 'status' => 'on_hold', 'priority' => 'low', 'progress' => 20, 'budget' => 35000000, 'revenue' => 42000000, 'total_cost' => 10000000],
            ['name' => 'Google Ads Smart Home VN', 'status' => 'in_progress', 'priority' => 'medium', 'progress' => 55, 'budget' => 48000000, 'revenue' => 60000000, 'total_cost' => 28000000],
            ['name' => 'Mobile App MekongSoft', 'status' => 'planning', 'priority' => 'high', 'progress' => 5, 'budget' => 150000000, 'revenue' => 180000000, 'total_cost' => 8000000],
            ['name' => 'Email Automation Golden Star', 'status' => 'completed', 'priority' => 'medium', 'progress' => 100, 'budget' => 25000000, 'revenue' => 30000000, 'total_cost' => 15000000],
            ['name' => 'Social Media Management VN Logistics', 'status' => 'in_progress', 'priority' => 'low', 'progress' => 70, 'budget' => 36000000, 'revenue' => 42000000, 'total_cost' => 20000000],
        ];

        $taskTemplates = [
            ['title' => 'Phân tích yêu cầu', 'status' => 'done', 'priority' => 'high'],
            ['title' => 'Thiết kế UI/UX', 'status' => 'done', 'priority' => 'high'],
            ['title' => 'Phát triển Frontend', 'status' => 'in_progress', 'priority' => 'high'],
            ['title' => 'Phát triển Backend', 'status' => 'in_progress', 'priority' => 'high'],
            ['title' => 'Tích hợp API', 'status' => 'todo', 'priority' => 'medium'],
            ['title' => 'Testing & QA', 'status' => 'todo', 'priority' => 'high'],
            ['title' => 'Deploy & Go-live', 'status' => 'todo', 'priority' => 'high'],
            ['title' => 'Training khách hàng', 'status' => 'todo', 'priority' => 'medium'],
            ['title' => 'Bảo hành & support', 'status' => 'todo', 'priority' => 'low'],
        ];

        foreach ($projects as $i => $proj) {
            $startDate = Carbon::now()->subMonths(rand(0, 3))->subDays(rand(0, 15));
            $customer = $customers->count() > $i ? $customers[$i] : null;

            $project = Project::create(array_merge($proj, [
                'account_id' => $account->id,
                'customer_id' => $customer?->id,
                'manager_id' => $users->random()->id,
                'description' => 'Dự án ' . $proj['name'],
                'start_date' => $startDate,
                'due_date' => $startDate->copy()->addMonths(rand(1, 4)),
                'completed_at' => $proj['status'] === 'completed' ? Carbon::now()->subDays(rand(1, 30)) : null,
                'notes' => 'Ghi chú dự án ' . $proj['name'],
            ]));

            // Create tasks for each project
            $taskCount = rand(4, 7);
            for ($t = 0; $t < $taskCount; $t++) {
                $tpl = $taskTemplates[$t % count($taskTemplates)];
                $taskStart = $startDate->copy()->addDays($t * rand(3, 7));

                ProjectTask::create([
                    'project_id' => $project->id,
                    'assigned_to' => $users->random()->id,
                    'title' => $tpl['title'],
                    'description' => $tpl['title'] . ' cho dự án ' . $proj['name'],
                    'status' => $proj['status'] === 'completed' ? 'done' : $tpl['status'],
                    'priority' => $tpl['priority'],
                    'start_date' => $taskStart,
                    'due_date' => $taskStart->copy()->addDays(rand(5, 14)),
                    'estimated_hours' => rand(4, 40),
                    'sort_order' => $t + 1,
                ]);
            }
        }

        $this->command->info('Created ' . count($projects) . ' projects with tasks.');
    }
}
