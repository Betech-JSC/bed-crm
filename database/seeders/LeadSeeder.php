<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ICP;
use App\Models\Lead;
use App\Models\SLASetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $account = Account::first();
        $icps = ICP::all();
        $slaSettings = SLASetting::all();
        $salesUsers = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SALE])->get();

        if (!$account || $salesUsers->isEmpty()) {
            $this->command->error('No account or sales users found. Please run DatabaseSeeder first.');
            return;
        }

        $industries = ['Technology', 'SaaS', 'E-commerce', 'Healthcare', 'Finance', 'Education', 'Manufacturing', 'Retail'];
        $sources = [Lead::SOURCE_WEBSITE, Lead::SOURCE_REFERRAL, Lead::SOURCE_EMAIL, Lead::SOURCE_SOCIAL, Lead::SOURCE_PHONE];
        $statuses = [Lead::STATUS_NEW, Lead::STATUS_CONTACTED, Lead::STATUS_QUALIFIED, Lead::STATUS_LOST, Lead::STATUS_WON];
        $priorities = ['hot', 'warm', 'cold'];

        // Create leads with various scenarios
        $leads = [];

        // High-scoring leads (Hot)
        for ($i = 0; $i < 10; $i++) {
            $slaSetting = $slaSettings->random();
            $slaStartedAt = Carbon::now()->subMinutes(rand(5, 20));
            $firstResponseAt = rand(0, 1) ? $slaStartedAt->copy()->addMinutes(rand(2, 12)) : null;

            $leads[] = [
                'account_id' => $account->id,
                'name' => fake()->name(),
                'phone' => fake()->phoneNumber(),
                'email' => fake()->unique()->safeEmail(),
                'company' => fake()->company(),
                'industry' => $industries[array_rand($industries)],
                'source' => Lead::SOURCE_WEBSITE,
                'status' => $statuses[array_rand([Lead::STATUS_NEW, Lead::STATUS_CONTACTED, Lead::STATUS_QUALIFIED])],
                'assigned_to' => $salesUsers->random()->id,
                'notes' => 'High-value prospect. Interested in enterprise solution. Budget approved. Decision maker.',
                'tags' => ['hot', 'enterprise', 'qualified'],
                'score' => rand(75, 95),
                'icp_id' => $icps->isNotEmpty() ? $icps->random()->id : null,
                'scoring_details' => [
                    'source_score' => 80,
                    'industry_score' => 100,
                    'company_size_score' => 90,
                    'engagement_score' => 85,
                    'website_score' => 90,
                ],
                'enrichment_data' => [
                    'industry' => 'Technology',
                    'company_size' => 'enterprise',
                    'employees' => rand(500, 5000),
                    'revenue' => rand(10000000, 100000000),
                ],
                'email_opens' => rand(3, 10),
                'email_clicks' => rand(1, 5),
                'last_email_open_at' => Carbon::now()->subHours(rand(1, 24)),
                'last_email_click_at' => Carbon::now()->subHours(rand(1, 12)),
                'website_visits' => rand(5, 20),
                'page_views' => rand(10, 50),
                'visited_pages' => ['/pricing', '/features', '/contact', '/case-studies'],
                'last_website_visit_at' => Carbon::now()->subHours(rand(1, 48)),
                'time_on_site_seconds' => rand(300, 1800),
                'sla_setting_id' => $slaSetting->id,
                'sla_started_at' => $slaStartedAt,
                'first_response_at' => $firstResponseAt,
                'response_time_minutes' => $firstResponseAt ? $slaStartedAt->diffInMinutes($firstResponseAt) : null,
                'sla_status' => $firstResponseAt ? 'met' : ($slaStartedAt->diffInMinutes(now()) > $slaSetting->first_response_threshold ? 'breached' : 'pending'),
                'last_scored_at' => Carbon::now()->subHours(rand(1, 24)),
            ];
        }

        // Medium-scoring leads (Warm)
        for ($i = 0; $i < 20; $i++) {
            $slaSetting = $slaSettings->random();
            $slaStartedAt = Carbon::now()->subMinutes(rand(20, 60));
            $firstResponseAt = rand(0, 1) ? $slaStartedAt->copy()->addMinutes(rand(15, 45)) : null;

            $leads[] = [
                'account_id' => $account->id,
                'name' => fake()->name(),
                'phone' => fake()->phoneNumber(),
                'email' => fake()->unique()->safeEmail(),
                'company' => fake()->company(),
                'industry' => $industries[array_rand($industries)],
                'source' => $sources[array_rand($sources)],
                'status' => $statuses[array_rand([Lead::STATUS_NEW, Lead::STATUS_CONTACTED])],
                'assigned_to' => $salesUsers->random()->id,
                'notes' => 'Interested but needs more information. Budget not yet approved.',
                'tags' => ['warm', 'follow-up'],
                'score' => rand(50, 74),
                'priority' => 'warm',
                'icp_id' => $icps->isNotEmpty() ? $icps->random()->id : null,
                'scoring_details' => [
                    'source_score' => 60,
                    'industry_score' => 75,
                    'company_size_score' => 70,
                    'engagement_score' => 50,
                    'website_score' => 60,
                ],
                'enrichment_data' => [
                    'industry' => $industries[array_rand($industries)],
                    'company_size' => 'medium',
                    'employees' => rand(50, 500),
                ],
                'email_opens' => rand(1, 3),
                'email_clicks' => rand(0, 2),
                'last_email_open_at' => Carbon::now()->subDays(rand(1, 7)),
                'website_visits' => rand(1, 5),
                'page_views' => rand(2, 10),
                'visited_pages' => ['/features', '/pricing'],
                'last_website_visit_at' => Carbon::now()->subDays(rand(1, 14)),
                'time_on_site_seconds' => rand(60, 300),
                'sla_setting_id' => $slaSetting->id,
                'sla_started_at' => $slaStartedAt,
                'first_response_at' => $firstResponseAt,
                'response_time_minutes' => $firstResponseAt ? $slaStartedAt->diffInMinutes($firstResponseAt) : null,
                'sla_status' => $firstResponseAt ? 'met' : ($slaStartedAt->diffInMinutes(now()) > $slaSetting->first_response_threshold ? 'breached' : 'pending'),
                'last_scored_at' => Carbon::now()->subDays(rand(1, 7)),
            ];
        }

        // Low-scoring leads (Cold)
        for ($i = 0; $i < 15; $i++) {
            $slaSetting = $slaSettings->random();
            $slaStartedAt = Carbon::now()->subHours(rand(1, 24));

            $leads[] = [
                'account_id' => $account->id,
                'name' => fake()->name(),
                'phone' => fake()->phoneNumber(),
                'email' => fake()->unique()->safeEmail(),
                'company' => fake()->company(),
                'industry' => $industries[array_rand($industries)],
                'source' => Lead::SOURCE_OTHER,
                'status' => Lead::STATUS_NEW,
                'assigned_to' => null,
                'notes' => 'New lead. Needs qualification.',
                'tags' => ['cold', 'new'],
                'score' => rand(20, 49),
                'priority' => 'cold',
                'scoring_details' => [
                    'source_score' => 30,
                    'industry_score' => 50,
                    'company_size_score' => 40,
                    'engagement_score' => 20,
                    'website_score' => 25,
                ],
                'enrichment_data' => [
                    'industry' => $industries[array_rand($industries)],
                    'company_size' => 'small',
                    'employees' => rand(1, 50),
                ],
                'email_opens' => 0,
                'email_clicks' => 0,
                'website_visits' => rand(0, 2),
                'page_views' => rand(0, 5),
                'visited_pages' => ['/'],
                'last_website_visit_at' => Carbon::now()->subDays(rand(7, 30)),
                'time_on_site_seconds' => rand(10, 60),
                'sla_setting_id' => $slaSetting->id,
                'sla_started_at' => $slaStartedAt,
                'first_response_at' => null,
                'response_time_minutes' => null,
                'sla_status' => $slaStartedAt->diffInMinutes(now()) > $slaSetting->first_response_threshold ? 'breached' : 'pending',
                'last_scored_at' => Carbon::now()->subDays(rand(1, 30)),
            ];
        }

        // Lost leads
        for ($i = 0; $i < 5; $i++) {
            $leads[] = [
                'account_id' => $account->id,
                'name' => fake()->name(),
                'phone' => fake()->phoneNumber(),
                'email' => fake()->unique()->safeEmail(),
                'company' => fake()->company(),
                'industry' => $industries[array_rand($industries)],
                'source' => $sources[array_rand($sources)],
                'status' => Lead::STATUS_LOST,
                'assigned_to' => $salesUsers->random()->id,
                'notes' => 'Lost to competitor. Price was too high.',
                'tags' => ['lost'],
                'score' => rand(40, 70),
                'priority' => 'cold',
            ];
        }

        foreach ($leads as $leadData) {
            Lead::create($leadData);
        }

        $this->command->info('Created ' . count($leads) . ' leads.');
    }
}
