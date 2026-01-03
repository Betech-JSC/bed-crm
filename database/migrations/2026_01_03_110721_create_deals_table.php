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
        Schema::create('deals', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->index();
            $table->integer('lead_id')->nullable()->index();
            $table->string('title', 200);
            $table->string('stage', 50)->default('prospecting');
            $table->decimal('value', 15, 2)->nullable();
            $table->date('expected_close_date')->nullable();
            $table->string('status', 20)->default('open'); // open, won, lost
            $table->text('lost_reason')->nullable();
            $table->integer('assigned_to')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
