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
        Schema::create('smtp_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id');
            $table->string('host');
            $table->integer('port')->default(587);
            $table->string('username');
            $table->string('password');
            $table->string('encryption')->default('tls'); // tls, ssl, or null
            $table->string('from_address');
            $table->string('from_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->unique('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smtp_settings');
    }
};
