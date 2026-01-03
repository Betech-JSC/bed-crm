<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ICP;
use Illuminate\Database\Seeder;

class ICPSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $account = Account::first();

        if (!$account) {
            $this->command->error('No account found. Please run DatabaseSeeder first.');
            return;
        }

        $icps = [
            [
                'name' => 'Enterprise SaaS Companies',
                'description' => 'Large enterprise companies using SaaS solutions',
                'company_size_min' => ['employees' => 500],
                'company_size_max' => ['employees' => 10000],
                'industries' => ['Technology', 'SaaS', 'Software'],
                'locations' => ['United States', 'Canada', 'United Kingdom'],
                'job_titles' => ['CEO', 'CTO', 'VP of Engineering', 'Director of IT'],
                'departments' => ['Engineering', 'IT', 'Technology'],
                'technologies' => ['AWS', 'Azure', 'Docker', 'Kubernetes'],
                'keywords' => ['enterprise', 'scalable', 'cloud', 'B2B'],
                'weight_company_size' => 25,
                'weight_industry' => 30,
                'weight_location' => 15,
                'weight_job_title' => 20,
                'weight_behavioral' => 10,
                'min_score' => 70,
                'is_active' => true,
            ],
            [
                'name' => 'Mid-Market E-commerce',
                'description' => 'Mid-market e-commerce companies looking to scale',
                'company_size_min' => ['employees' => 50],
                'company_size_max' => ['employees' => 500],
                'industries' => ['E-commerce', 'Retail', 'Online Retail'],
                'locations' => ['United States', 'Europe'],
                'job_titles' => ['CMO', 'Marketing Director', 'E-commerce Manager'],
                'departments' => ['Marketing', 'E-commerce', 'Sales'],
                'technologies' => ['Shopify', 'WooCommerce', 'Magento'],
                'keywords' => ['e-commerce', 'online store', 'retail', 'conversion'],
                'weight_company_size' => 20,
                'weight_industry' => 30,
                'weight_location' => 10,
                'weight_job_title' => 25,
                'weight_behavioral' => 15,
                'min_score' => 65,
                'is_active' => true,
            ],
            [
                'name' => 'Healthcare Technology',
                'description' => 'Healthcare organizations adopting technology solutions',
                'company_size_min' => ['employees' => 100],
                'company_size_max' => ['employees' => 2000],
                'industries' => ['Healthcare', 'Health Tech', 'Medical'],
                'locations' => ['United States'],
                'job_titles' => ['CIO', 'IT Director', 'Healthcare Administrator'],
                'departments' => ['IT', 'Administration', 'Operations'],
                'technologies' => ['HIPAA', 'Electronic Health Records'],
                'keywords' => ['healthcare', 'HIPAA', 'compliance', 'patient data'],
                'weight_company_size' => 15,
                'weight_industry' => 35,
                'weight_location' => 20,
                'weight_job_title' => 20,
                'weight_behavioral' => 10,
                'min_score' => 70,
                'is_active' => true,
            ],
            [
                'name' => 'Startup Tech Companies',
                'description' => 'Early-stage technology startups',
                'company_size_min' => ['employees' => 1],
                'company_size_max' => ['employees' => 50],
                'industries' => ['Technology', 'SaaS', 'Startup'],
                'locations' => ['United States', 'Canada'],
                'job_titles' => ['Founder', 'CEO', 'CTO', 'Co-founder'],
                'departments' => ['Engineering', 'Product'],
                'technologies' => ['React', 'Node.js', 'Python', 'JavaScript'],
                'keywords' => ['startup', 'MVP', 'scalable', 'growth'],
                'weight_company_size' => 10,
                'weight_industry' => 25,
                'weight_location' => 15,
                'weight_job_title' => 30,
                'weight_behavioral' => 20,
                'min_score' => 60,
                'is_active' => true,
            ],
        ];

        foreach ($icps as $icpData) {
            ICP::create(array_merge($icpData, ['account_id' => $account->id]));
        }

        $this->command->info('Created ' . count($icps) . ' ICPs.');
    }
}
