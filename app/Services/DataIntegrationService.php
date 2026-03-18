<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\EmployeeProfile;
use App\Models\FinancialTransaction;
use App\Models\Project;
use App\Models\ProjectExpense;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataIntegrationService
{
    // ════════════════════════════════════════════
    //  Deal Won → Customer + Financial Transaction
    // ════════════════════════════════════════════

    /**
     * When a Deal is won:
     *   1. Create Customer record (idempotent)
     *   2. Record deal revenue as FinancialTransaction (idempotent)
     */
    public function onDealWon(Deal $deal): void
    {
        DB::transaction(function () use ($deal) {
            $this->createCustomerFromDeal($deal);
            $this->recordDealRevenue($deal);
        });

        Log::info("[DataSync] Deal #{$deal->id} won → Customer + Financial synced", [
            'deal_id' => $deal->id,
            'value' => $deal->value,
        ]);
    }

    /**
     * Create or find existing Customer from won Deal.
     * Uses deal_id as unique reference to prevent duplicates.
     */
    private function createCustomerFromDeal(Deal $deal): Customer
    {
        return Customer::firstOrCreate(
            ['account_id' => $deal->account_id, 'deal_id' => $deal->id],
            [
                'name' => $deal->lead?->name ?? $deal->title,
                'email' => $deal->lead?->email,
                'phone' => $deal->lead?->phone,
                'organization_id' => null,
                'contact_id' => null,
                'lifecycle_status' => Customer::STATUS_ONBOARDING,
                'start_date' => now(),
                'mrr' => 0,
                'arr' => 0,
                'health_score' => 100,
                'assigned_to' => $deal->assigned_to,
            ]
        );
    }

    /**
     * Record deal revenue as an income transaction.
     * Uses "deal-{id}" as unique reference to prevent duplicates.
     */
    private function recordDealRevenue(Deal $deal): void
    {
        if (!$deal->value || $deal->value <= 0) {
            return;
        }

        FinancialTransaction::firstOrCreate(
            ['account_id' => $deal->account_id, 'reference' => "deal-{$deal->id}"],
            [
                'type' => FinancialTransaction::TYPE_INCOME,
                'category' => FinancialTransaction::CAT_DEAL_REVENUE,
                'description' => "Deal won: {$deal->title}",
                'amount' => $deal->value,
                'transaction_date' => now(),
                'linkable_type' => 'deal',
                'linkable_id' => $deal->id,
                'recorded_by' => $deal->assigned_to,
            ]
        );
    }

    // ════════════════════════════════════════════
    //  Project Completed → Financial Transaction
    // ════════════════════════════════════════════

    /**
     * When a Project is marked completed, record its revenue.
     */
    public function onProjectCompleted(Project $project): void
    {
        if (!$project->revenue || $project->revenue <= 0) {
            return;
        }

        FinancialTransaction::firstOrCreate(
            ['account_id' => $project->account_id, 'reference' => "project-{$project->id}"],
            [
                'type' => FinancialTransaction::TYPE_INCOME,
                'category' => FinancialTransaction::CAT_PROJECT_REVENUE,
                'description' => "Project completed: {$project->name}",
                'amount' => $project->revenue,
                'transaction_date' => $project->completed_at ?? now(),
                'linkable_type' => 'project',
                'linkable_id' => $project->id,
                'recorded_by' => $project->manager_id,
            ]
        );

        Log::info("[DataSync] Project #{$project->id} completed → Revenue recorded", [
            'project_id' => $project->id,
            'revenue' => $project->revenue,
        ]);
    }

    // ════════════════════════════════════════════
    //  Project Expense → Financial Transaction
    // ════════════════════════════════════════════

    /**
     * When a Project Expense is recorded, mirror it to FinancialTransaction.
     */
    public function onProjectExpenseCreated(ProjectExpense $expense): void
    {
        $project = $expense->project;
        if (!$project) {
            return;
        }

        // Map ProjectExpense categories to FinancialTransaction categories
        $categoryMap = [
            'software' => FinancialTransaction::CAT_SOFTWARE,
            'hosting' => FinancialTransaction::CAT_HOSTING,
            'design' => FinancialTransaction::CAT_CONTRACTOR,
            'hardware' => FinancialTransaction::CAT_EQUIPMENT,
            'other' => FinancialTransaction::CAT_OTHER_EXPENSE,
        ];

        FinancialTransaction::firstOrCreate(
            ['account_id' => $project->account_id, 'reference' => "proj-exp-{$expense->id}"],
            [
                'type' => FinancialTransaction::TYPE_EXPENSE,
                'category' => $categoryMap[$expense->category] ?? FinancialTransaction::CAT_OTHER_EXPENSE,
                'description' => "Project [{$project->name}]: {$expense->description}",
                'amount' => $expense->amount,
                'transaction_date' => $expense->date ?? now(),
                'linkable_type' => 'project',
                'linkable_id' => $project->id,
            ]
        );

        Log::info("[DataSync] ProjectExpense #{$expense->id} → Financial synced");
    }

    // ════════════════════════════════════════════
    //  Lead → Contact Promotion
    // ════════════════════════════════════════════

    /**
     * Promote a Lead to a Contact record.
     * Prevents duplicates by checking email/phone.
     */
    public function promoteLeadToContact(\App\Models\Lead $lead): ?Contact
    {
        if (!$lead->email && !$lead->phone) {
            return null;
        }

        // Check for existing contact by email
        $existing = Contact::where('account_id', $lead->account_id)
            ->where(function ($q) use ($lead) {
                if ($lead->email) {
                    $q->where('email', $lead->email);
                }
                if ($lead->phone) {
                    $q->orWhere('phone', $lead->phone);
                }
            })
            ->first();

        if ($existing) {
            Log::info("[DataSync] Lead #{$lead->id} matches existing Contact #{$existing->id}");
            return $existing;
        }

        $nameParts = explode(' ', $lead->name ?? '', 2);

        return Contact::create([
            'account_id' => $lead->account_id,
            'first_name' => $nameParts[0] ?? '',
            'last_name' => $nameParts[1] ?? '',
            'email' => $lead->email,
            'phone' => $lead->phone,
            'city' => null,
            'organization_id' => null,
        ]);
    }

    // ════════════════════════════════════════════
    //  Recurring Salary Sync (for scheduled job)
    // ════════════════════════════════════════════

    /**
     * Sync monthly salary expenses for all employees.
     * Intended to be called by a monthly scheduled job.
     */
    public function syncMonthlySalaries(int $accountId, ?int $year = null, ?int $month = null): int
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;
        $synced = 0;

        $employees = EmployeeProfile::where('account_id', $accountId)->get();

        foreach ($employees as $employee) {
            if (!$employee->base_salary || $employee->base_salary <= 0) {
                continue;
            }

            $ref = "salary-{$employee->id}-" . sprintf('%04d-%02d', $year, $month);

            $employeeName = $employee->user ? $employee->user->name : 'Employee #' . $employee->id;

            $created = FinancialTransaction::firstOrCreate(
                ['account_id' => $accountId, 'reference' => $ref],
                [
                    'type' => FinancialTransaction::TYPE_EXPENSE,
                    'category' => FinancialTransaction::CAT_SALARY,
                    'description' => "Salary: {$employeeName}",
                    'amount' => $employee->base_salary,
                    'transaction_date' => Carbon::create($year, $month, 1),
                    'linkable_type' => 'employee',
                    'linkable_id' => $employee->id,
                    'is_recurring' => true,
                    'recurring_period' => 'monthly',
                ]
            );

            if ($created->wasRecentlyCreated) {
                $synced++;
            }
        }

        Log::info("[DataSync] Monthly salary sync for {$year}-{$month}: {$synced} new records");
        return $synced;
    }
}
