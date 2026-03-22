<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id')->index();
            $table->string('contract_number')->unique();
            $table->string('title');
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'active', 'paused', 'completed', 'cancelled', 'expired'])->default('draft');

            // References
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('deal_id')->nullable()->index();
            $table->unsignedBigInteger('quotation_id')->nullable()->index(); // linked quotation
            $table->unsignedBigInteger('contact_id')->nullable()->index();

            // Contract details
            $table->enum('contract_type', ['one_time', 'recurring', 'retainer', 'project_based'])->default('one_time');
            $table->decimal('value', 15, 2)->default(0);
            $table->string('currency', 3)->default('VND');
            $table->text('payment_terms')->nullable();
            $table->text('scope_of_work')->nullable();
            $table->text('terms_conditions')->nullable();

            // Dates
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('signed_date')->nullable();
            $table->date('renewal_date')->nullable();
            $table->boolean('auto_renew')->default(false);

            // Tracking
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('signed_by_client')->nullable();
            $table->string('signed_by_company')->nullable();

            // Attachments & metadata
            $table->string('file_path')->nullable();
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
