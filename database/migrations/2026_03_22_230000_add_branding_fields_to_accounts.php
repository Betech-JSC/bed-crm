<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('favicon')->nullable()->after('logo');
            $table->string('slogan', 500)->nullable()->after('name');
            $table->text('description')->nullable()->after('slogan');
            $table->string('founded_year', 4)->nullable()->after('company_size');
            $table->string('registration_number')->nullable()->after('tax_id');
            $table->json('social_links')->nullable()->after('website');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['favicon', 'slogan', 'description', 'founded_year', 'registration_number', 'social_links']);
        });
    }
};
