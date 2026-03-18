<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Customer profiles
        Schema::create('customers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('organization_id')->nullable();
            $table->unsignedInteger('contact_id')->nullable();
            $table->unsignedInteger('deal_id')->nullable();
            $table->unsignedInteger('assigned_to')->nullable();

            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            // Lifecycle
            $table->string('lifecycle_status', 30)->default('onboarding');
            $table->date('start_date')->nullable();
            $table->decimal('mrr', 12, 2)->default(0);
            $table->decimal('arr', 14, 2)->default(0);

            // Health Score (0-100)
            $table->unsignedTinyInteger('health_score')->default(50);
            $table->json('health_factors')->nullable();
            $table->timestamp('health_calculated_at')->nullable();

            // Renewal
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->string('contract_term', 20)->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->string('renewal_status', 20)->nullable();

            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
            $table->foreign('contact_id')->references('id')->on('contacts')->nullOnDelete();
            $table->foreign('deal_id')->references('id')->on('deals')->nullOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();

            $table->index(['account_id', 'lifecycle_status']);
            $table->index(['account_id', 'health_score']);
            $table->index(['account_id', 'contract_end']);
        });

        // Support tickets
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('customer_id');
            $table->unsignedInteger('assigned_to')->nullable();
            $table->unsignedInteger('created_by')->nullable();

            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('priority', 20)->default('medium');
            $table->string('status', 20)->default('open');
            $table->string('category', 50)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['account_id', 'status']);
            $table->index(['customer_id', 'status']);
        });

        // Upsell / expansion opportunities
        Schema::create('upsell_opportunities', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('customer_id');
            $table->unsignedInteger('assigned_to')->nullable();

            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('value', 14, 2)->default(0);
            $table->string('status', 20)->default('identified');
            $table->string('type', 20)->default('upsell');
            $table->date('target_close_date')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();

            $table->index(['account_id', 'status']);
            $table->index(['customer_id', 'status']);
        });

        // Customer health score history
        Schema::create('customer_health_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('customer_id');
            $table->unsignedTinyInteger('score');
            $table->json('factors')->nullable();
            $table->string('trigger', 50)->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_health_logs');
        Schema::dropIfExists('upsell_opportunities');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('customers');
    }
};
