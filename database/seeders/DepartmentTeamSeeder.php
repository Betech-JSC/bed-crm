<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class DepartmentTeamSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::first();
        $users = User::where('account_id', $account->id)->get();

        $departments = [
            ['name' => 'Ban Giám Đốc', 'code' => 'BGD', 'color' => '#6366f1', 'icon' => 'pi pi-crown', 'budget_monthly' => 100000000, 'children' => []],
            ['name' => 'Kinh Doanh', 'code' => 'KD', 'color' => '#10b981', 'icon' => 'pi pi-chart-line', 'budget_monthly' => 80000000, 'children' => [
                ['name' => 'Sales B2B', 'description' => 'Đội ngũ bán hàng B2B', 'color' => '#10b981', 'capacity' => 8],
                ['name' => 'Sales Enterprise', 'description' => 'Enterprise accounts', 'color' => '#059669', 'capacity' => 5],
                ['name' => 'Pre-sales & Demo', 'description' => 'Demo sản phẩm cho khách', 'color' => '#34d399', 'capacity' => 3],
            ]],
            ['name' => 'Marketing', 'code' => 'MKT', 'color' => '#ec4899', 'icon' => 'pi pi-megaphone', 'budget_monthly' => 60000000, 'children' => [
                ['name' => 'Content & Social', 'description' => 'Content marketing, social media', 'color' => '#ec4899', 'capacity' => 5],
                ['name' => 'Digital Ads', 'description' => 'Google Ads, Facebook Ads', 'color' => '#f43f5e', 'capacity' => 4],
                ['name' => 'SEO & Branding', 'description' => 'SEO, brand identity', 'color' => '#fb7185', 'capacity' => 3],
            ]],
            ['name' => 'Kỹ Thuật', 'code' => 'KT', 'color' => '#3b82f6', 'icon' => 'pi pi-code', 'budget_monthly' => 120000000, 'children' => [
                ['name' => 'Frontend', 'description' => 'React, Vue.js development', 'color' => '#3b82f6', 'capacity' => 6],
                ['name' => 'Backend', 'description' => 'Laravel, API development', 'color' => '#2563eb', 'capacity' => 5],
                ['name' => 'DevOps', 'description' => 'Infrastructure, CI/CD', 'color' => '#60a5fa', 'capacity' => 3],
                ['name' => 'QA & Testing', 'description' => 'Quality assurance', 'color' => '#93c5fd', 'capacity' => 3],
            ]],
            ['name' => 'Chăm Sóc Khách Hàng', 'code' => 'CSKH', 'color' => '#f59e0b', 'icon' => 'pi pi-headphones', 'budget_monthly' => 40000000, 'children' => [
                ['name' => 'Support L1', 'description' => 'Hỗ trợ cấp 1, tiếp nhận ticket', 'color' => '#f59e0b', 'capacity' => 5],
                ['name' => 'Support L2', 'description' => 'Hỗ trợ chuyên sâu, kỹ thuật', 'color' => '#d97706', 'capacity' => 3],
                ['name' => 'Customer Success', 'description' => 'Quản lý sức khỏe khách hàng', 'color' => '#fbbf24', 'capacity' => 4],
            ]],
            ['name' => 'Nhân Sự', 'code' => 'HR', 'color' => '#8b5cf6', 'icon' => 'pi pi-users', 'budget_monthly' => 30000000, 'children' => [
                ['name' => 'Tuyển Dụng', 'description' => 'Recruitment team', 'color' => '#8b5cf6', 'capacity' => 3],
                ['name' => 'L&D', 'description' => 'Learning & Development', 'color' => '#a78bfa', 'capacity' => 2],
            ]],
            ['name' => 'Tài Chính - Kế Toán', 'code' => 'TCKT', 'color' => '#14b8a6', 'icon' => 'pi pi-wallet', 'budget_monthly' => 25000000, 'children' => []],
        ];

        foreach ($departments as $order => $dept) {
            $children = $dept['children'] ?? [];
            unset($dept['children']);

            $department = Department::create(array_merge($dept, [
                'account_id' => $account->id,
                'head_user_id' => $users->random()->id,
                'description' => 'Phòng ' . $dept['name'],
                'sort_order' => $order + 1,
                'is_active' => true,
                'budget_yearly' => $dept['budget_monthly'] * 12,
            ]));

            foreach ($children as $teamOrder => $team) {
                Team::create([
                    'account_id' => $account->id,
                    'department_id' => $department->id,
                    'name' => $team['name'],
                    'description' => $team['description'],
                    'leader_user_id' => $users->random()->id,
                    'color' => $team['color'],
                    'sort_order' => $teamOrder + 1,
                    'is_active' => true,
                    'capacity' => $team['capacity'],
                ]);
            }
        }

        $this->command->info('Created ' . count($departments) . ' departments with teams.');
    }
}
