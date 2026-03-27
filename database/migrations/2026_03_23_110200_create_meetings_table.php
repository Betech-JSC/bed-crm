<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('created_by');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('room_code', 20)->unique();          // VD: "BED-A3X-K9M"
            $table->string('status')->default('scheduled');      // scheduled, live, ended, cancelled
            $table->string('type')->default('video');            // video, audio, screen_share
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_minutes')->nullable();     // actual duration

            // Participants
            $table->json('participants')->nullable();            // [{user_id,name,email,role,joined_at,left_at}]
            $table->integer('max_participants')->default(20);
            $table->boolean('is_public')->default(false);        // anyone with link can join

            // Recording
            $table->boolean('record_enabled')->default(false);
            $table->string('recording_path')->nullable();
            $table->string('recording_url')->nullable();
            $table->integer('recording_size_mb')->nullable();

            // AI Recap
            $table->text('ai_transcript')->nullable();           // Full transcript
            $table->text('ai_summary')->nullable();              // AI-generated summary
            $table->json('ai_action_items')->nullable();         // [{task, assignee, deadline}]
            $table->json('ai_key_decisions')->nullable();        // [{decision, context}]
            $table->json('ai_topics')->nullable();               // ["topic1", "topic2"]

            // Notes & Agenda
            $table->text('agenda')->nullable();
            $table->text('meeting_notes')->nullable();

            // Settings
            $table->json('settings')->nullable();                // {mute_on_join, camera_off, waiting_room}
            $table->string('password')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
