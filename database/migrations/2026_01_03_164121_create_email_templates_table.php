<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->index();
            $table->string('name', 255);
            $table->string('subject', 500);
            $table->text('body_html')->nullable();
            $table->text('body_text')->nullable();
            $table->string('type', 50)->default('campaign'); // campaign, automation, transactional
            $table->json('variables')->nullable(); // Available variables like {{name}}, {{email}}
            $table->boolean('is_active')->default(true);
            $table->integer('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
