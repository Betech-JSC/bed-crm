<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Deal;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ProposalSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::first();
        $user = User::where('account_id', $account->id)->first();
        $deals = Deal::where('account_id', $account->id)->take(10)->get();

        if ($deals->isEmpty()) {
            $this->command->warn('No deals found. Run DealSeeder first.');
            return;
        }

        $proposals = [
            ['title' => 'Proposal - Website ABC Trading', 'amount' => 45000000, 'status' => 'accepted'],
            ['title' => 'Proposal - E-commerce XYZ Store', 'amount' => 85000000, 'status' => 'sent'],
            ['title' => 'Proposal - SEO Package Dragon Export', 'amount' => 120000000, 'status' => 'viewed'],
            ['title' => 'Proposal - Landing Page Campaign', 'amount' => 12000000, 'status' => 'accepted'],
            ['title' => 'Proposal - CRM Integration Lotus Digital', 'amount' => 65000000, 'status' => 'draft'],
            ['title' => 'Proposal - Redesign Saigon Furniture', 'amount' => 42000000, 'status' => 'rejected'],
            ['title' => 'Proposal - Google Ads Smart Home', 'amount' => 55000000, 'status' => 'sent'],
            ['title' => 'Proposal - Mobile App MekongSoft', 'amount' => 180000000, 'status' => 'draft'],
            ['title' => 'Proposal v2 - Website ABC Trading (revised)', 'amount' => 52000000, 'status' => 'accepted'],
            ['title' => 'Proposal - Full Digital Package VN Logistics', 'amount' => 95000000, 'status' => 'viewed'],
        ];

        foreach ($proposals as $i => $p) {
            $createdAt = Carbon::now()->subDays(rand(5, 45));
            $deal = $deals->count() > $i ? $deals[$i] : $deals->random();

            Proposal::create([
                'account_id' => $account->id,
                'deal_id' => $deal->id,
                'version' => str_contains($p['title'], 'v2') ? 2 : 1,
                'title' => $p['title'],
                'description' => 'Nội dung proposal chi tiết cho ' . $p['title'],
                'amount' => $p['amount'],
                'valid_until' => $createdAt->copy()->addDays(30),
                'status' => $p['status'],
                'sent_at' => in_array($p['status'], ['sent', 'viewed', 'accepted', 'rejected']) ? $createdAt->copy()->addDay() : null,
                'viewed_at' => in_array($p['status'], ['viewed', 'accepted', 'rejected']) ? $createdAt->copy()->addDays(2) : null,
                'accepted_at' => $p['status'] === 'accepted' ? $createdAt->copy()->addDays(rand(3, 7)) : null,
                'rejected_at' => $p['status'] === 'rejected' ? $createdAt->copy()->addDays(rand(3, 7)) : null,
                'rejection_reason' => $p['status'] === 'rejected' ? 'Ngân sách không đủ, khách đề nghị giảm 20%' : null,
                'view_count' => in_array($p['status'], ['viewed', 'accepted']) ? rand(2, 8) : ($p['status'] === 'sent' ? rand(0, 1) : 0),
                'last_viewed_at' => in_array($p['status'], ['viewed', 'accepted']) ? Carbon::now()->subDays(rand(1, 10)) : null,
                'created_by' => $user->id,
                'sent_by' => in_array($p['status'], ['sent', 'viewed', 'accepted', 'rejected']) ? $user->id : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt->copy()->addDays(rand(1, 5)),
            ]);
        }

        $this->command->info('Created ' . count($proposals) . ' proposals.');
    }
}
