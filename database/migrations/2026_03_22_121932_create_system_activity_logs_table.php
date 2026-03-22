<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('action'); // created, updated, deleted, restored, login, logout, exported, imported
            $table->string('module'); // leads, contacts, deals, users, settings...
            $table->nullableMorphs('subject'); // polymorphic: subject_type, subject_id
            $table->string('subject_label')->nullable(); // human-readable label (e.g. "Lead: Nguyễn Văn A")
            $table->json('changes')->nullable(); // old/new values for updates
            $table->json('metadata')->nullable(); // extra context (IP, browser, etc.)
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['module', 'action']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_activity_logs');
    }
};
