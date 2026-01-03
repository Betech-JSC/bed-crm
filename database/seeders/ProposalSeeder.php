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
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $account = Account::first();
        $deals = Deal::where('status', Deal::STATUS_OPEN)->get();
        $users = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SALE])->get();

        if (!$account || $deals->isEmpty() || $users->isEmpty()) {
            $this->command->error('No account, deals, or users found. Please run DatabaseSeeder first.');
            return;
        }

        $proposals = [];

        // Create proposals for deals
        foreach ($deals->take(10) as $deal) {
            $user = $users->random();
            $sentAt = Carbon::now()->subDays(rand(1, 30));
            $status = $this->getRandomStatus();

            $proposal = [
                'account_id' => $account->id,
                'deal_id' => $deal->id,
                'created_by' => $user->id,
                'parent_id' => null,
                'version' => 1,
                'title' => "Proposal for {$deal->title}",
                'description' => "Detailed proposal for {$deal->title}. Includes pricing, features, and implementation timeline.",
                'file_path' => 'proposals/sample-proposal.pdf',
                'file_name' => "Proposal-{$deal->id}-v1.pdf",
                'file_type' => 'application/pdf',
                'file_size' => rand(500000, 2000000), // 500KB to 2MB
                'amount' => $deal->value,
                'valid_until' => Carbon::now()->addDays(rand(30, 90)),
                'status' => $status,
                'sent_at' => in_array($status, [Proposal::STATUS_SENT, Proposal::STATUS_VIEWED, Proposal::STATUS_ACCEPTED, Proposal::STATUS_REJECTED]) ? $sentAt : null,
                'viewed_at' => in_array($status, [Proposal::STATUS_VIEWED, Proposal::STATUS_ACCEPTED, Proposal::STATUS_REJECTED]) ? $sentAt->copy()->addHours(rand(1, 48)) : null,
                'accepted_at' => $status === Proposal::STATUS_ACCEPTED ? $sentAt->copy()->addDays(rand(1, 14)) : null,
                'rejected_at' => $status === Proposal::STATUS_REJECTED ? $sentAt->copy()->addDays(rand(1, 7)) : null,
                'view_count' => in_array($status, [Proposal::STATUS_VIEWED, Proposal::STATUS_ACCEPTED, Proposal::STATUS_REJECTED]) ? rand(1, 10) : 0,
                'last_viewed_at' => in_array($status, [Proposal::STATUS_VIEWED, Proposal::STATUS_ACCEPTED, Proposal::STATUS_REJECTED]) ? Carbon::now()->subDays(rand(0, 7)) : null,
                'metadata' => [
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => fake()->userAgent(),
                ],
            ];

            $proposals[] = $proposal;
        }

        // Create some proposals with versions
        foreach ($deals->take(3) as $deal) {
            $user = $users->random();
            $parentId = null;

            // Create parent proposal
            $parentProposal = [
                'account_id' => $account->id,
                'deal_id' => $deal->id,
                'created_by' => $user->id,
                'parent_id' => null,
                'version' => 1,
                'title' => "Proposal for {$deal->title}",
                'description' => "Initial proposal for {$deal->title}.",
                'file_path' => 'proposals/sample-proposal-v1.pdf',
                'file_name' => "Proposal-{$deal->id}-v1.pdf",
                'file_type' => 'application/pdf',
                'file_size' => rand(500000, 2000000),
                'amount' => $deal->value,
                'valid_until' => Carbon::now()->addDays(30),
                'status' => Proposal::STATUS_REJECTED,
                'rejected_at' => Carbon::now()->subDays(rand(7, 14)),
                'view_count' => rand(1, 5),
            ];

            $proposals[] = $parentProposal;
            $parentId = count($proposals); // Will be set after creation

            // Create version 2
            $proposals[] = [
                'account_id' => $account->id,
                'deal_id' => $deal->id,
                'created_by' => $user->id,
                'parent_id' => null, // Will be set after parent is created
                'version' => 2,
                'title' => "Revised Proposal for {$deal->title}",
                'description' => "Revised proposal addressing feedback from version 1.",
                'file_path' => 'proposals/sample-proposal-v2.pdf',
                'file_name' => "Proposal-{$deal->id}-v2.pdf",
                'file_type' => 'application/pdf',
                'file_size' => rand(500000, 2000000),
                'amount' => $deal->value * 0.9, // 10% discount
                'valid_until' => Carbon::now()->addDays(60),
                'status' => Proposal::STATUS_SENT,
                'sent_at' => Carbon::now()->subDays(rand(1, 5)),
                'view_count' => rand(0, 3),
            ];
        }

        // Create proposals without deals
        for ($i = 0; $i < 5; $i++) {
            $proposals[] = [
                'account_id' => $account->id,
                'deal_id' => null,
                'created_by' => $users->random()->id,
                'parent_id' => null,
                'version' => 1,
                'title' => "Standalone Proposal " . ($i + 1),
                'description' => "Standalone proposal not linked to a deal.",
                'file_path' => 'proposals/standalone-proposal.pdf',
                'file_name' => "Standalone-Proposal-{$i}.pdf",
                'file_type' => 'application/pdf',
                'file_size' => rand(500000, 2000000),
                'amount' => rand(5000, 50000),
                'valid_until' => Carbon::now()->addDays(rand(30, 90)),
                'status' => Proposal::STATUS_DRAFT,
            ];
        }

        // Create proposals and handle versioning
        $createdProposals = [];
        $parentProposals = [];

        foreach ($proposals as $proposalData) {
            $proposal = Proposal::create($proposalData);
            $createdProposals[] = $proposal;

            // Track parent proposals (version 1)
            if ($proposal->version === 1 && $proposal->deal_id) {
                $parentProposals[$proposal->deal_id] = $proposal->id;
            }
        }

        // Update parent_id for versioned proposals
        foreach ($createdProposals as $proposal) {
            if ($proposal->version === 2 && $proposal->parent_id === null && $proposal->deal_id) {
                // Find the parent proposal (version 1 for the same deal)
                if (isset($parentProposals[$proposal->deal_id])) {
                    $proposal->update(['parent_id' => $parentProposals[$proposal->deal_id]]);
                } else {
                    $parent = Proposal::where('deal_id', $proposal->deal_id)
                        ->where('version', 1)
                        ->where('id', '!=', $proposal->id)
                        ->first();

                    if ($parent) {
                        $proposal->update(['parent_id' => $parent->id]);
                    }
                }
            }
        }

        $this->command->info('Created ' . count($proposals) . ' proposals.');
    }

    private function getRandomStatus(): string
    {
        $statuses = [
            Proposal::STATUS_DRAFT,
            Proposal::STATUS_SENT,
            Proposal::STATUS_VIEWED,
            Proposal::STATUS_ACCEPTED,
            Proposal::STATUS_REJECTED,
        ];

        // Weighted random - more drafts and sent, fewer accepted/rejected
        $weights = [3, 3, 2, 1, 1]; // draft, sent, viewed, accepted, rejected
        $totalWeight = array_sum($weights);
        $random = rand(1, $totalWeight);
        $currentWeight = 0;

        foreach ($statuses as $index => $status) {
            $currentWeight += $weights[$index];
            if ($random <= $currentWeight) {
                return $status;
            }
        }

        return Proposal::STATUS_DRAFT;
    }
}
