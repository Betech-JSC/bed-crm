<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectResource;
use App\Models\ProjectExpense;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $accountId = 1;

        $projects = [
            [
                'name' => 'Website Redesign - TechViet Corp',
                'status' => 'in_progress', 'priority' => 'high',
                'start_date' => now()->subDays(30), 'due_date' => now()->addDays(15),
                'budget' => 120000000, 'revenue' => 150000000, 'progress' => 65,
                'customer_id' => 2,
            ],
            [
                'name' => 'E-commerce Platform - GreenDev',
                'status' => 'in_progress', 'priority' => 'urgent',
                'start_date' => now()->subDays(60), 'due_date' => now()->addDays(5),
                'budget' => 250000000, 'revenue' => 350000000, 'progress' => 80,
                'customer_id' => 3,
            ],
            [
                'name' => 'CRM Integration - ABC Tech',
                'status' => 'delayed', 'priority' => 'high',
                'start_date' => now()->subDays(45), 'due_date' => now()->subDays(5),
                'budget' => 80000000, 'revenue' => 95000000, 'progress' => 40,
                'customer_id' => 1,
            ],
            [
                'name' => 'Mobile App - CloudOne',
                'status' => 'planning', 'priority' => 'medium',
                'start_date' => now()->addDays(7), 'due_date' => now()->addDays(90),
                'budget' => 200000000, 'revenue' => 280000000, 'progress' => 0,
                'customer_id' => 7,
            ],
            [
                'name' => 'SEO Campaign - Bình Minh',
                'status' => 'completed', 'priority' => 'medium',
                'start_date' => now()->subDays(90), 'due_date' => now()->subDays(10),
                'budget' => 30000000, 'revenue' => 45000000, 'progress' => 100,
                'customer_id' => 5,
            ],
        ];

        $taskTemplates = [
            ['title' => 'Thiết kế UI/UX', 'status' => 'done', 'estimated_hours' => 40, 'actual_hours' => 38, 'hourly_cost' => 300000],
            ['title' => 'Phát triển Frontend', 'status' => 'in_progress', 'estimated_hours' => 60, 'actual_hours' => 35, 'hourly_cost' => 350000],
            ['title' => 'Phát triển Backend', 'status' => 'in_progress', 'estimated_hours' => 80, 'actual_hours' => 42, 'hourly_cost' => 400000],
            ['title' => 'Testing & QA', 'status' => 'todo', 'estimated_hours' => 20, 'actual_hours' => 0, 'hourly_cost' => 250000],
            ['title' => 'Deploy & Go-live', 'status' => 'todo', 'estimated_hours' => 8, 'actual_hours' => 0, 'hourly_cost' => 300000],
        ];

        foreach ($projects as $pData) {
            $project = Project::create(array_merge($pData, ['account_id' => $accountId]));

            // Tasks
            foreach ($taskTemplates as $i => $t) {
                $project->tasks()->create(array_merge($t, [
                    'priority' => 'medium',
                    'sort_order' => $i,
                ]));
            }

            // Resources - one user per project (unique constraint)
            $project->resources()->create([
                'user_id' => 1, 'role' => 'manager', 'hourly_rate' => 500000,
                'allocated_hours' => 80, 'logged_hours' => rand(20, 60),
            ]);

            // Expenses
            $project->expenses()->create([
                'description' => 'Hosting & Infrastructure', 'amount' => 2000000,
                'category' => 'hosting', 'date' => now()->subDays(15),
            ]);
            $project->expenses()->create([
                'description' => 'Design Tools License', 'amount' => 1500000,
                'category' => 'software', 'date' => now()->subDays(20),
            ]);
        }
    }
}
