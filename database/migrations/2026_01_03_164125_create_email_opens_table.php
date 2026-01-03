<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_opens', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('email_send_id')->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('opened_at');
            $table->timestamps();

            $table->index(['email_send_id', 'opened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_opens');
    }
};
