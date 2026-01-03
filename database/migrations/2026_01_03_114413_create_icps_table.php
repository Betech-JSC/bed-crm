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
        Schema::create('icps', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->index();
            $table->string('name', 200);
            $table->text('description')->nullable();
            
            // Company criteria
            $table->json('company_size_min')->nullable(); // e.g., {"employees": 10}
            $table->json('company_size_max')->nullable(); // e.g., {"employees": 500}
            $table->json('industries')->nullable(); // Array of industry names
            $table->json('locations')->nullable(); // Array of countries/regions
            
            // Contact criteria
            $table->json('job_titles')->nullable(); // Array of job titles/keywords
            $table->json('departments')->nullable(); // Array of departments
            
            // Behavioral criteria
            $table->json('technologies')->nullable(); // Tech stack they use
            $table->json('keywords')->nullable(); // Website/content keywords
            
            // Scoring weights (0-100 for each criterion)
            $table->integer('weight_company_size')->default(20);
            $table->integer('weight_industry')->default(25);
            $table->integer('weight_location')->default(15);
            $table->integer('weight_job_title')->default(20);
            $table->integer('weight_behavioral')->default(20);
            
            $table->integer('min_score')->default(60); // Minimum score to be considered a match
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('icps');
    }
};
