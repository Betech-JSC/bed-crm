<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_chat_messages')) {
            return; // Table created in a later migration; columns will be added there
        }

        Schema::table('ai_chat_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_chat_messages', 'tool_calls')) {
                $table->text('tool_calls')->nullable()->after('metadata');
            }
            if (!Schema::hasColumn('ai_chat_messages', 'tool_results')) {
                $table->text('tool_results')->nullable()->after('tool_calls');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_chat_messages', function (Blueprint $table) {
            $table->dropColumn(['tool_calls', 'tool_results']);
        });
    }
};
