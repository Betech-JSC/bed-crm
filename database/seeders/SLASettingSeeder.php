<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\SLASetting;
use Illuminate\Database\Seeder;

class SLASettingSeeder extends Seeder
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

        $slaSettings = [
            [
                'name' => 'Standard Response SLA',
                'description' => 'Standard 15-minute response time for all leads',
                'first_response_threshold' => 15, // minutes
                'warning_threshold' => 10, // minutes
                'is_active' => true,
            ],
            [
                'name' => 'Priority Lead SLA',
                'description' => '5-minute response time for high-priority leads',
                'first_response_threshold' => 5, // minutes
                'warning_threshold' => 3, // minutes
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise Lead SLA',
                'description' => '30-minute response time for enterprise leads',
                'first_response_threshold' => 30, // minutes
                'warning_threshold' => 20, // minutes
                'is_active' => true,
            ],
        ];

        foreach ($slaSettings as $slaData) {
            SLASetting::create(array_merge($slaData, ['account_id' => $account->id]));
        }

        $this->command->info('Created ' . count($slaSettings) . ' SLA settings.');
    }
}
