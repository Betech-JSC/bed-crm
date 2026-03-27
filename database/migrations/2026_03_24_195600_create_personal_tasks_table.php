<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('user_id');

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('todo');       // todo, in_progress, done, cancelled
            $table->string('priority')->default('medium');   // low, medium, high, urgent
            $table->string('category')->nullable();          // work, personal, meeting, follow_up, other
            $table->string('color')->nullable();             // for visual grouping

            $table->date('due_date')->nullable();
            $table->date('reminder_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Relations (optional links)
            $table->string('related_type')->nullable();      // lead, deal, project, contact, meeting
            $table->unsignedInteger('related_id')->nullable();

            // Subtasks & checklist stored as JSON
            $table->json('checklist')->nullable();           // [{text, done}]
            $table->json('tags')->nullable();                // ["tag1", "tag2"]

            $table->integer('sort_order')->default(0);
            $table->boolean('is_pinned')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_tasks');
    }
};
