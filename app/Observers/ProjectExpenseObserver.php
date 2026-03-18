<?php

namespace App\Observers;

use App\Models\ProjectExpense;
use App\Services\DataIntegrationService;

class ProjectExpenseObserver
{
    public function __construct(private DataIntegrationService $integration) {}

    /**
     * When a Project Expense is created, mirror it to FinancialTransaction.
     */
    public function created(ProjectExpense $expense): void
    {
        $this->integration->onProjectExpenseCreated($expense);
    }
}
