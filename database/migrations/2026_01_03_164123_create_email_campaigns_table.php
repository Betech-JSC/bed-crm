<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->index();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->integer('email_template_id')->nullable()->index();
            $table->string('subject', 500);
            $table->text('body_html')->nullable();
            $table->text('body_text')->nullable();
            $table->integer('email_list_id')->nullable()->index();
            $table->string('status', 50)->default('draft'); // draft, scheduled, sending, sent, paused, cancelled
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('opened_count')->default(0);
            $table->integer('clicked_count')->default(0);
            $table->integer('bounced_count')->default(0);
            $table->integer('unsubscribed_count')->default(0);
            $table->decimal('open_rate', 5, 2)->default(0);
            $table->decimal('click_rate', 5, 2)->default(0);
            $table->integer('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'status']);
            $table->index(['scheduled_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};
