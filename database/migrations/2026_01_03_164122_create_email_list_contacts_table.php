<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_list_contacts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('email_list_id')->index();
            $table->string('contact_type', 50); // 'contact', 'lead'
            $table->integer('contact_id')->index(); // ID of contact or lead
            $table->string('email', 255)->index();
            $table->string('name', 255)->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('unsubscribe_reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['email_list_id', 'contact_type', 'contact_id']);
            $table->index(['email_list_id', 'unsubscribed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_list_contacts');
    }
};
