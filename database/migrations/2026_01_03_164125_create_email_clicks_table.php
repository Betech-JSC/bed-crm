<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_clicks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('email_send_id')->index();
            $table->string('url', 1000);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('clicked_at');
            $table->timestamps();

            $table->index(['email_send_id', 'clicked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_clicks');
    }
};
