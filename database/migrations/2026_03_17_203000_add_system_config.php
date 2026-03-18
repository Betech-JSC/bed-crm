<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Extend accounts table with configuration columns ──
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('timezone', 50)->default('Asia/Ho_Chi_Minh')->after('logo');
            $table->string('currency', 10)->default('VND')->after('timezone');
            $table->string('locale', 5)->default('vi')->after('currency');
            $table->string('date_format', 20)->default('DD/MM/YYYY')->after('locale');
            $table->string('time_format', 10)->default('24h')->after('date_format');
            $table->string('fiscal_year_start', 5)->default('01-01')->after('time_format'); // MM-DD
            $table->string('phone', 30)->nullable()->after('fiscal_year_start');
            $table->string('email', 100)->nullable()->after('phone');
            $table->string('website', 255)->nullable()->after('email');
            $table->text('address')->nullable()->after('website');
            $table->string('tax_id', 30)->nullable()->after('address');
            $table->string('industry', 50)->nullable()->after('tax_id');
            $table->unsignedSmallInteger('company_size')->nullable()->after('industry');
        });

        // ── Flexible key-value config store ──
        if (!Schema::hasTable('system_configs')) {
            Schema::create('system_configs', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('account_id')->index();
                $table->string('group', 50)->index();        // 'general', 'email', 'crm', 'finance', etc.
                $table->string('key', 100);
                $table->text('value')->nullable();
                $table->string('type', 20)->default('string'); // string, integer, boolean, json, text
                $table->text('description')->nullable();
                $table->boolean('is_public')->default(false);  // expose to frontend?
                $table->timestamps();
                $table->unique(['account_id', 'group', 'key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_configs');

        Schema::table('accounts', function (Blueprint $table) {
            $cols = [
                'timezone', 'currency', 'locale', 'date_format', 'time_format',
                'fiscal_year_start', 'phone', 'email', 'website', 'address',
                'tax_id', 'industry', 'company_size',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('accounts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
