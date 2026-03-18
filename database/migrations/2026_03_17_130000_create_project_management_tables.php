<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('deal_id')->nullable();
            $table->unsignedInteger('customer_id')->nullable();
            $table->unsignedInteger('manager_id')->nullable();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('planning'); // planning, in_progress, on_hold, delayed, completed, cancelled
            $table->string('priority', 20)->default('medium');

            // Timeline
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('completed_at')->nullable();
            $table->unsignedSmallInteger('progress')->default(0); // 0-100

            // Financials
            $table->decimal('budget', 14, 2)->default(0);
            $table->decimal('revenue', 14, 2)->default(0);      // billed to client
            $table->decimal('total_cost', 14, 2)->default(0);   // calculated from tasks + resources

            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('deal_id')->references('id')->on('deals')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('manager_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['account_id', 'status']);
        });

        Schema::create('project_tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('project_id');
            $table->unsignedInteger('assigned_to')->nullable();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('todo'); // todo, in_progress, review, done
            $table->string('priority', 20)->default('medium');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedSmallInteger('estimated_hours')->default(0);
            $table->decimal('actual_hours', 8, 2)->default(0);
            $table->decimal('hourly_cost', 10, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();

            $table->index(['project_id', 'status']);
        });

        Schema::create('project_resources', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('project_id');
            $table->unsignedInteger('user_id');
            $table->string('role', 50)->default('member'); // manager, developer, designer, qa, member
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->unsignedSmallInteger('allocated_hours')->default(0);
            $table->decimal('logged_hours', 8, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unique(['project_id', 'user_id']);
        });

        Schema::create('project_expenses', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('project_id');
            $table->string('description');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('category', 30)->nullable(); // software, hosting, design, other
            $table->date('date')->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_expenses');
        Schema::dropIfExists('project_resources');
        Schema::dropIfExists('project_tasks');
        Schema::dropIfExists('projects');
    }
};
