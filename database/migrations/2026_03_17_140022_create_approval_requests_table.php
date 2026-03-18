<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id')->index();
            $table->unsignedInteger('requested_by')->index();
            $table->unsignedInteger('approver_id')->nullable()->index();
            $table->string('subject_type'); // e.g., Deal, Expense
            $table->unsignedBigInteger('subject_id');
            $table->string('reason_vi');
            $table->string('reason_en');
            $table->text('comment')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

            // Note: Not using full constrained() because existing tables might use varied increments
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
