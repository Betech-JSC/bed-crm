<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Activity;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $account = Account::first();
        $users = User::all();
        $leads = Lead::all();
        $deals = Deal::all();

        if (!$account || $users->isEmpty()) {
            $this->command->error('No account or users found. Please run DatabaseSeeder first.');
            return;
        }

        $activities = [];

        // Create activities for leads
        foreach ($leads->take(30) as $lead) {
            $activityTypes = [Activity::TYPE_CALL, Activity::TYPE_EMAIL, Activity::TYPE_MEETING, Activity::TYPE_NOTE];
            
            for ($i = 0; $i < rand(1, 4); $i++) {
                $type = $activityTypes[array_rand($activityTypes)];
                $date = Carbon::now()->subDays(rand(0, 30));
                
                $activities[] = [
                    'account_id' => $account->id,
                    'user_id' => $users->random()->id,
                    'subject_type' => Lead::class,
                    'subject_id' => $lead->id,
                    'type' => $type,
                    'title' => $this->getActivityTitle($type, $lead->name),
                    'description' => $this->getActivityDescription($type, $lead),
                    'date' => $date,
                ];
            }
        }

        // Create activities for deals
        foreach ($deals->take(20) as $deal) {
            $activityTypes = [Activity::TYPE_CALL, Activity::TYPE_EMAIL, Activity::TYPE_MEETING, Activity::TYPE_NOTE];
            
            for ($i = 0; $i < rand(2, 6); $i++) {
                $type = $activityTypes[array_rand($activityTypes)];
                $date = Carbon::now()->subDays(rand(0, 60));
                
                $activities[] = [
                    'account_id' => $account->id,
                    'user_id' => $users->random()->id,
                    'subject_type' => Deal::class,
                    'subject_id' => $deal->id,
                    'type' => $type,
                    'title' => $this->getActivityTitle($type, $deal->title),
                    'description' => $this->getActivityDescription($type, $deal),
                    'date' => $date,
                ];
            }
        }

        foreach ($activities as $activityData) {
            Activity::create($activityData);
        }

        $this->command->info('Created ' . count($activities) . ' activities.');
    }

    private function getActivityTitle(string $type, string $subjectName): string
    {
        return match ($type) {
            Activity::TYPE_CALL => "Call with {$subjectName}",
            Activity::TYPE_EMAIL => "Email to {$subjectName}",
            Activity::TYPE_MEETING => "Meeting with {$subjectName}",
            Activity::TYPE_NOTE => "Note about {$subjectName}",
            default => "Activity with {$subjectName}",
        };
    }

    private function getActivityDescription(string $type, $subject): string
    {
        $painPoints = ['cost', 'scalability', 'security', 'integration', 'performance', 'support'];
        $selectedPainPoints = array_rand($painPoints, rand(1, 3));
        $selectedPainPoints = is_array($selectedPainPoints) ? $selectedPainPoints : [$selectedPainPoints];
        $painPointsText = implode(', ', array_map(fn($i) => $painPoints[$i], $selectedPainPoints));

        return match ($type) {
            Activity::TYPE_CALL => "Discussed solution features and pricing. Customer mentioned pain points: {$painPointsText}. Follow-up scheduled.",
            Activity::TYPE_EMAIL => "Sent proposal and pricing information. Customer is interested in learning more about our solution.",
            Activity::TYPE_MEETING => "Product demo conducted. Customer showed interest in features related to: {$painPointsText}. Next steps: send proposal.",
            Activity::TYPE_NOTE => "Customer is evaluating our solution. Key concerns: {$painPointsText}. Need to address these in next interaction.",
            default => "Activity with customer. Pain points discussed: {$painPointsText}.",
        };
    }
}
