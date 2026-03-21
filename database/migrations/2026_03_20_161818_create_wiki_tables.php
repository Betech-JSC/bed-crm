<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->index();
            $table->integer('parent_id')->nullable()->index();
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['account_id', 'slug']);
        });

        Schema::create('wiki_articles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->index();
            $table->integer('category_id')->nullable()->index();
            $table->string('title', 500);
            $table->string('slug', 500);
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('status', 20)->default('draft');
            $table->boolean('is_pinned')->default(false);
            $table->integer('created_by')->nullable()->index();
            $table->integer('updated_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->integer('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['account_id', 'slug']);
            $table->index('status');
            $table->index('is_pinned');
        });

        Schema::create('wiki_article_versions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('article_id')->index();
            $table->integer('version_number')->default(1);
            $table->string('title', 500);
            $table->longText('content')->nullable();
            $table->integer('edited_by')->nullable();
            $table->string('change_summary', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_article_versions');
        Schema::dropIfExists('wiki_articles');
        Schema::dropIfExists('wiki_categories');
    }
};
