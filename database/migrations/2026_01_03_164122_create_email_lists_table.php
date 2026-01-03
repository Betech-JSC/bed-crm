<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_lists', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->index();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('type', 50)->default('manual'); // manual, dynamic (based on filters)
            $table->json('filters')->nullable(); // For dynamic lists: filter criteria
            $table->integer('contacts_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_lists');
    }
};
