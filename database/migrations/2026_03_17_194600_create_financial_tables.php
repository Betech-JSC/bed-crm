<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Financial transactions (all money in/out)
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('recorded_by')->nullable();

            $table->enum('type', ['income', 'expense']); // money in or out
            $table->string('category', 50);               // see FinancialTransaction::CATEGORIES
            $table->string('description');
            $table->decimal('amount', 14, 2);             // always positive
            $table->date('transaction_date');
            $table->string('reference', 100)->nullable();  // invoice #, receipt #, deal ID, etc.

            // Link to related entities
            $table->string('linkable_type', 50)->nullable(); // deal, project, employee, etc.
            $table->unsignedInteger('linkable_id')->nullable();

            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_period', 20)->nullable(); // monthly, quarterly, yearly
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['account_id', 'transaction_date']);
            $table->index(['account_id', 'type']);
            $table->index(['account_id', 'category']);
        });

        // Monthly financial snapshots (pre-computed for performance)
        Schema::create('financial_snapshots', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->string('period_label', 20);  // e.g. "2026-03"
            $table->date('period_start');
            $table->date('period_end');

            $table->decimal('total_income', 14, 2)->default(0);
            $table->decimal('total_expenses', 14, 2)->default(0);
            $table->decimal('net_cashflow', 14, 2)->default(0);
            $table->decimal('profit_margin', 8, 2)->default(0);   // percentage
            $table->decimal('burn_rate', 14, 2)->default(0);      // monthly spend rate
            $table->decimal('runway_months', 8, 2)->default(0);   // cash / burn_rate
            $table->decimal('cash_balance', 14, 2)->default(0);   // cumulative balance

            $table->json('income_breakdown')->nullable();   // { category: amount }
            $table->json('expense_breakdown')->nullable();  // { category: amount }

            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->unique(['account_id', 'period_label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_snapshots');
        Schema::dropIfExists('financial_transactions');
    }
};
