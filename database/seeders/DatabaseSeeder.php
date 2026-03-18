<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $account = Account::create(['name' => 'Acme Corporation']);

        // Create default admin user
        User::factory()->create([
            'account_id' => $account->id,
            'first_name' => 'Admin',
            'last_name' => 'BED',
            'email' => 'admin@gmail.com',
            'password' => 'admin@gmail.com',
            'owner' => true,
            'role' => User::ROLE_ADMIN,
        ]);

        // Create demo user (existing)
        User::factory()->create([
            'account_id' => $account->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'johndoe@example.com',
            'password' => 'secret',
            'owner' => true,
            'role' => User::ROLE_SALE,
        ]);

        // Create users with different roles
        User::factory()->create([
            'account_id' => $account->id,
            'role' => User::ROLE_SALE,
        ]);

        User::factory()->create([
            'account_id' => $account->id,
            'role' => User::ROLE_MARKETING,
        ]);

        User::factory()->create([
            'account_id' => $account->id,
            'role' => User::ROLE_CSKH,
        ]);

        User::factory(2)->create(['account_id' => $account->id]);

        $organizations = Organization::factory(100)
            ->create(['account_id' => $account->id]);

        Contact::factory(100)
            ->create(['account_id' => $account->id])
            ->each(function ($contact) use ($organizations) {
                $contact->update(['organization_id' => $organizations->random()->id]);
            });

        // Seed additional data for testing CRM features
        $this->call([
            ICPSeeder::class,
            SLASettingSeeder::class,
            LeadSeeder::class,
            DealSeeder::class,
            ActivitySeeder::class,
            SalesPlaybookSeeder::class,
            ProposalSeeder::class,
        ]);
    }
}
