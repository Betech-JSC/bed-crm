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
        Schema::create('activities', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->index();
            $table->morphs('subject'); // subject_type, subject_id (polymorphic)
            $table->string('type', 50); // call, email, meeting, note
            $table->string('title', 200)->nullable();
            $table->text('description')->nullable();
            $table->dateTime('date')->index();
            $table->integer('user_id')->index(); // Who created the activity
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
