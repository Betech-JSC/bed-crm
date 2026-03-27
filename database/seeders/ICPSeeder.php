<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ICP;
use Illuminate\Database\Seeder;

class ICPSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::first();

        $icps = [
            [
                'name' => 'Enterprise Manufacturing',
                'description' => 'Doanh nghiệp sản xuất lớn cần website đa ngôn ngữ và hệ thống quản lý',
                'company_size_min' => ['employees' => 200],
                'company_size_max' => ['employees' => 5000],
                'industries' => ['Manufacturing', 'Industrial', 'Automotive'],
                'locations' => ['Vietnam', 'Thailand', 'China'],
                'job_titles' => ['CEO', 'CTO', 'IT Director', 'Marketing Director'],
                'departments' => ['Executive', 'IT', 'Marketing'],
                'technologies' => ['ERP', 'SAP', 'Odoo'],
                'keywords' => ['xuất khẩu', 'B2B', 'OEM', 'công nghiệp'],
                'weight_company_size' => 25,
                'weight_industry' => 30,
                'weight_location' => 15,
                'weight_job_title' => 15,
                'weight_behavioral' => 15,
                'min_score' => 65,
                'is_active' => true,
            ],
            [
                'name' => 'SME Trading Company',
                'description' => 'Công ty thương mại vừa và nhỏ cần website và digital marketing',
                'company_size_min' => ['employees' => 10],
                'company_size_max' => ['employees' => 200],
                'industries' => ['Trading', 'Import/Export', 'Wholesale', 'Retail'],
                'locations' => ['Vietnam', 'ASEAN'],
                'job_titles' => ['Owner', 'Director', 'Marketing Manager', 'Sales Manager'],
                'departments' => ['Management', 'Marketing', 'Sales'],
                'technologies' => ['Shopify', 'WordPress', 'WooCommerce'],
                'keywords' => ['thương mại', 'xuất nhập khẩu', 'buôn bán'],
                'weight_company_size' => 20,
                'weight_industry' => 25,
                'weight_location' => 20,
                'weight_job_title' => 20,
                'weight_behavioral' => 15,
                'min_score' => 55,
                'is_active' => true,
            ],
            [
                'name' => 'Tech Startup',
                'description' => 'Startup công nghệ cần CRM và automation marketing',
                'company_size_min' => ['employees' => 5],
                'company_size_max' => ['employees' => 100],
                'industries' => ['Technology', 'SaaS', 'Fintech', 'E-commerce'],
                'locations' => ['Vietnam', 'Singapore'],
                'job_titles' => ['CEO', 'Founder', 'Head of Growth', 'CMO'],
                'departments' => ['Founders', 'Growth', 'Marketing'],
                'technologies' => ['React', 'Laravel', 'AWS', 'HubSpot'],
                'keywords' => ['startup', 'SaaS', 'growth', 'scale-up'],
                'weight_company_size' => 15,
                'weight_industry' => 25,
                'weight_location' => 10,
                'weight_job_title' => 25,
                'weight_behavioral' => 25,
                'min_score' => 60,
                'is_active' => true,
            ],
            [
                'name' => 'Education & Training',
                'description' => 'Tổ chức giáo dục, trung tâm đào tạo cần website tuyển sinh',
                'company_size_min' => ['employees' => 20],
                'company_size_max' => ['employees' => 500],
                'industries' => ['Education', 'Training', 'EdTech'],
                'locations' => ['Vietnam'],
                'job_titles' => ['Principal', 'Director', 'Head of Marketing', 'Enrollment Manager'],
                'departments' => ['Marketing', 'Admissions', 'Management'],
                'technologies' => ['LMS', 'Google Workspace', 'Zoom'],
                'keywords' => ['tuyển sinh', 'đào tạo', 'giáo dục', 'trung tâm'],
                'weight_company_size' => 15,
                'weight_industry' => 30,
                'weight_location' => 20,
                'weight_job_title' => 20,
                'weight_behavioral' => 15,
                'min_score' => 55,
                'is_active' => true,
            ],
        ];

        foreach ($icps as $icp) {
            ICP::create(array_merge($icp, ['account_id' => $account->id]));
        }

        $this->command->info('Created ' . count($icps) . ' ICPs.');
    }
}
