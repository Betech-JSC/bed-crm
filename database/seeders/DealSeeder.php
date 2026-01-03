<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DealSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $account = Account::first();
        $qualifiedLeads = Lead::where('status', Lead::STATUS_QUALIFIED)->get();
        // Fallback to any leads if no qualified leads
        $leads = $qualifiedLeads->isEmpty() ? Lead::all() : $qualifiedLeads;
        $salesUsers = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SALE])->get();

        if (!$account || $salesUsers->isEmpty()) {
            $this->command->error('No account or sales users found. Please run DatabaseSeeder first.');
            return;
        }

        $stages = [
            Deal::STAGE_PROSPECTING,
            Deal::STAGE_QUALIFICATION,
            Deal::STAGE_PROPOSAL,
            Deal::STAGE_NEGOTIATION,
            Deal::STAGE_CLOSING,
        ];

        $deals = [];

        // Create deals from qualified leads
        foreach ($leads->take(15) as $lead) {
            $deals[] = [
                'account_id' => $account->id,
                'lead_id' => $lead->id,
                'title' => "Deal with {$lead->company}",
                'stage' => $stages[array_rand($stages)],
                'value' => rand(5000, 100000),
                'expected_close_date' => Carbon::now()->addDays(rand(7, 90)),
                'status' => Deal::STATUS_OPEN,
                'assigned_to' => $lead->assigned_to ?? $salesUsers->random()->id,
                'notes' => "Deal notes for {$lead->company}. Customer is interested in our solution. Key pain points: scalability, cost efficiency, and integration capabilities.",
            ];
        }

        // Create some won deals
        for ($i = 0; $i < 5; $i++) {
            $lead = $leads->random();
            $deals[] = [
                'account_id' => $account->id,
                'lead_id' => $lead->id,
                'title' => "Won Deal - {$lead->company}",
                'stage' => Deal::STAGE_CLOSING,
                'value' => rand(10000, 50000),
                'expected_close_date' => Carbon::now()->subDays(rand(1, 30)),
                'status' => Deal::STATUS_WON,
                'assigned_to' => $lead->assigned_to ?? $salesUsers->random()->id,
                'notes' => 'Deal won! Customer signed contract.',
            ];
        }

        // Create some lost deals
        for ($i = 0; $i < 3; $i++) {
            $lead = $leads->random();
            $deals[] = [
                'account_id' => $account->id,
                'lead_id' => $lead->id,
                'title' => "Lost Deal - {$lead->company}",
                'stage' => Deal::STAGE_NEGOTIATION,
                'value' => rand(5000, 30000),
                'expected_close_date' => Carbon::now()->subDays(rand(1, 60)),
                'status' => Deal::STATUS_LOST,
                'lost_reason' => 'Price too high. Customer chose competitor.',
                'assigned_to' => $lead->assigned_to ?? $salesUsers->random()->id,
                'notes' => 'Deal lost to competitor.',
            ];
        }

        // Create deals without leads
        for ($i = 0; $i < 10; $i++) {
            $deals[] = [
                'account_id' => $account->id,
                'lead_id' => null,
                'title' => "Direct Deal - " . fake()->company(),
                'stage' => $stages[array_rand($stages)],
                'value' => rand(5000, 75000),
                'expected_close_date' => Carbon::now()->addDays(rand(7, 120)),
                'status' => Deal::STATUS_OPEN,
                'assigned_to' => $salesUsers->random()->id,
                'notes' => 'Direct deal. Customer pain points: cost reduction, process automation, and better reporting.',
            ];
        }

        foreach ($deals as $dealData) {
            Deal::create($dealData);
        }

        $this->command->info('Created ' . count($deals) . ' deals.');
    }
}
