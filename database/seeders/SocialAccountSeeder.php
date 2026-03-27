<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\SocialAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SocialAccountSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::first();

        $accounts = [
            ['platform' => 'facebook', 'platform_account_id' => 'fb_' . fake()->numerify('##########'), 'name' => 'BED CRM Official', 'username' => 'bedcrm', 'is_active' => true, 'is_connected' => true, 'access_token' => encrypt('fb_token_' . fake()->sha256()), 'token_expires_at' => Carbon::now()->addDays(60), 'platform_metadata' => ['page_id' => 'bedcrm', 'followers' => 12500]],
            ['platform' => 'facebook', 'platform_account_id' => 'fb_' . fake()->numerify('##########'), 'name' => 'BED Technology Group', 'username' => 'bedtech', 'is_active' => true, 'is_connected' => true, 'access_token' => encrypt('fb_token_' . fake()->sha256()), 'token_expires_at' => Carbon::now()->addDays(45), 'platform_metadata' => ['page_id' => 'bedtech', 'followers' => 8200]],
            ['platform' => 'instagram', 'platform_account_id' => 'ig_' . fake()->numerify('##########'), 'name' => '@bedcrm.official', 'username' => 'bedcrm.official', 'is_active' => true, 'is_connected' => true, 'access_token' => encrypt('ig_token_' . fake()->sha256()), 'token_expires_at' => Carbon::now()->addDays(55), 'platform_metadata' => ['followers' => 5800]],
            ['platform' => 'linkedin', 'platform_account_id' => 'li_' . fake()->numerify('##########'), 'name' => 'BED CRM Company', 'username' => 'bed-crm', 'is_active' => true, 'is_connected' => true, 'access_token' => encrypt('li_token_' . fake()->sha256()), 'token_expires_at' => Carbon::now()->addDays(30), 'platform_metadata' => ['company_id' => 'bed-crm', 'followers' => 3200]],
            ['platform' => 'twitter', 'platform_account_id' => 'tw_' . fake()->numerify('##########'), 'name' => '@BEDCRM', 'username' => 'BEDCRM', 'is_active' => true, 'is_connected' => true, 'access_token' => encrypt('tw_token_' . fake()->sha256()), 'token_expires_at' => Carbon::now()->addDays(90), 'platform_metadata' => ['handle' => '@BEDCRM', 'followers' => 2100]],
            ['platform' => 'facebook', 'platform_account_id' => 'fb_' . fake()->numerify('##########'), 'name' => '[Expired] BED Promo', 'username' => 'bedpromo', 'is_active' => false, 'is_connected' => false, 'access_token' => encrypt('fb_expired_' . fake()->sha256()), 'token_expires_at' => Carbon::now()->subDays(10), 'platform_metadata' => ['page_id' => 'bedpromo', 'followers' => 1500]],
        ];

        foreach ($accounts as $acc) {
            SocialAccount::create(array_merge($acc, [
                'account_id' => $account->id,
            ]));
        }

        $this->command->info('Created ' . count($accounts) . ' social accounts.');
    }
}
