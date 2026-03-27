<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CustomerSuccessSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::first();
        $users = User::where('account_id', $account->id)->get();
        $organizations = Organization::where('account_id', $account->id)->take(20)->get();
        $contacts = Contact::where('account_id', $account->id)->take(20)->get();

        $customerData = [
            ['name' => 'ABC Trading Co.', 'lifecycle_status' => 'active', 'mrr' => 15000000, 'health_score' => 92, 'contract_term' => 12, 'auto_renew' => true],
            ['name' => 'XYZ Manufacturing', 'lifecycle_status' => 'active', 'mrr' => 25000000, 'health_score' => 88, 'contract_term' => 24, 'auto_renew' => true],
            ['name' => 'TechViet Solutions', 'lifecycle_status' => 'active', 'mrr' => 5000000, 'health_score' => 75, 'contract_term' => 6, 'auto_renew' => true],
            ['name' => 'Dragon Export JSC', 'lifecycle_status' => 'active', 'mrr' => 35000000, 'health_score' => 95, 'contract_term' => 24, 'auto_renew' => true],
            ['name' => 'Lotus Digital Agency', 'lifecycle_status' => 'onboarding', 'mrr' => 8000000, 'health_score' => 65, 'contract_term' => 12, 'auto_renew' => false],
            ['name' => 'Saigon Furniture', 'lifecycle_status' => 'at_risk', 'mrr' => 12000000, 'health_score' => 35, 'contract_term' => 12, 'auto_renew' => false],
            ['name' => 'Golden Star Import', 'lifecycle_status' => 'at_risk', 'mrr' => 18000000, 'health_score' => 42, 'contract_term' => 12, 'auto_renew' => true],
            ['name' => 'Smart Home Vietnam', 'lifecycle_status' => 'active', 'mrr' => 10000000, 'health_score' => 80, 'contract_term' => 12, 'auto_renew' => true],
            ['name' => 'Pacific Trading Group', 'lifecycle_status' => 'churned', 'mrr' => 0, 'health_score' => 10, 'contract_term' => 6, 'auto_renew' => false],
            ['name' => 'MekongSoft', 'lifecycle_status' => 'active', 'mrr' => 20000000, 'health_score' => 85, 'contract_term' => 24, 'auto_renew' => true],
            ['name' => 'VN Logistics Pro', 'lifecycle_status' => 'onboarding', 'mrr' => 6000000, 'health_score' => 55, 'contract_term' => 12, 'auto_renew' => false],
            ['name' => 'Bamboo Fashion', 'lifecycle_status' => 'active', 'mrr' => 3000000, 'health_score' => 70, 'contract_term' => 6, 'auto_renew' => true],
        ];

        foreach ($customerData as $i => $data) {
            $startDate = Carbon::now()->subMonths(rand(1, 18));
            $org = $organizations->count() > $i ? $organizations[$i] : null;
            $contact = $contacts->count() > $i ? $contacts[$i] : null;

            Customer::create([
                'account_id' => $account->id,
                'organization_id' => $org?->id,
                'contact_id' => $contact?->id,
                'assigned_to' => $users->random()->id,
                'name' => $data['name'],
                'email' => fake()->companyEmail(),
                'phone' => fake()->phoneNumber(),
                'lifecycle_status' => $data['lifecycle_status'],
                'start_date' => $startDate,
                'mrr' => $data['mrr'],
                'arr' => $data['mrr'] * 12,
                'health_score' => $data['health_score'],
                'health_factors' => [
                    'product_usage' => rand(30, 100),
                    'support_tickets' => rand(0, 10),
                    'nps_score' => rand(1, 10),
                    'payment_status' => $data['lifecycle_status'] !== 'churned' ? 'on_time' : 'overdue',
                    'feature_adoption' => rand(20, 95),
                ],
                'health_calculated_at' => Carbon::now()->subHours(rand(1, 72)),
                'contract_start' => $startDate,
                'contract_end' => $startDate->copy()->addMonths($data['contract_term']),
                'contract_term' => $data['contract_term'],
                'auto_renew' => $data['auto_renew'],
                'renewal_status' => $data['lifecycle_status'] === 'churned' ? 'churned' : ($startDate->copy()->addMonths($data['contract_term'])->diffInDays(now()) < 60 ? 'upcoming' : 'active'),
                'notes' => 'Khách hàng ' . $data['name'],
            ]);
        }

        $this->command->info('Created ' . count($customerData) . ' customers.');
    }
}
