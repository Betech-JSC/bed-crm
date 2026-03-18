<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Extend deals with probability, forecasting, velocity tracking ──
        Schema::table('deals', function (Blueprint $table) {
            $table->unsignedTinyInteger('win_probability')->default(10)->after('stage');     // 0–100 %
            $table->decimal('weighted_value', 15, 2)->nullable()->after('win_probability'); // value × probability
            $table->string('forecast_category', 20)->default('pipeline')->after('weighted_value');  // pipeline|best_case|commit|omit
            $table->integer('days_in_stage')->default(0)->after('forecast_category');
            $table->timestamp('stage_changed_at')->nullable()->after('days_in_stage');
            $table->json('stage_history')->nullable()->after('stage_changed_at');          // [{stage, entered_at, exited_at}]
            $table->integer('days_to_close')->nullable()->after('stage_history');          // actual days from created to won
            $table->decimal('gross_margin', 5, 2)->nullable()->after('days_to_close');     // %
            $table->decimal('cost', 15, 2)->nullable()->after('gross_margin');
            $table->string('currency', 10)->default('VND')->after('cost');
            $table->string('close_quarter', 10)->nullable()->after('currency');            // Q1-2026
            $table->json('competitors')->nullable()->after('close_quarter');               // [{name, strength}]
            $table->json('pain_points')->nullable()->after('competitors');
            $table->json('next_steps')->nullable()->after('pain_points');
            $table->unsignedSmallInteger('health_score')->default(50)->after('next_steps'); // 0–100, auto-calculated
            $table->boolean('at_risk')->default(false)->after('health_score');
            $table->text('ai_summary')->nullable()->after('at_risk');
            $table->timestamp('last_activity_at')->nullable()->after('ai_summary');
            $table->unsignedSmallInteger('activity_count')->default(0)->after('last_activity_at');
        });

        // ── 2. Deal activity KPI targets (per sales rep, per period) ──
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->index();
            $table->integer('user_id')->index();
            $table->tinyInteger('year');
            $table->tinyInteger('quarter')->nullable();
            $table->tinyInteger('month')->nullable();
            $table->string('period_type', 10)->default('month');                // month|quarter|year
            $table->decimal('revenue_target', 15, 2)->default(0);
            $table->unsignedSmallInteger('deals_target')->default(0);
            $table->unsignedSmallInteger('leads_target')->default(0);
            $table->unsignedSmallInteger('activities_target')->default(0);
            $table->decimal('revenue_actual', 15, 2)->default(0);
            $table->unsignedSmallInteger('deals_actual')->default(0);
            $table->unsignedSmallInteger('leads_actual')->default(0);
            $table->unsignedSmallInteger('activities_actual')->default(0);
            $table->timestamps();
            $table->unique(['account_id', 'user_id', 'year', 'quarter', 'month', 'period_type'], 'sales_targets_unique');
        });

        // ── 3. Sales velocity snapshots (daily) ──
        Schema::create('sales_velocity_snapshots', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->index();
            $table->integer('user_id')->nullable()->index();        // null = account-level
            $table->date('snapshot_date')->index();
            $table->unsignedSmallInteger('open_deals');
            $table->decimal('avg_deal_value', 15, 2);
            $table->decimal('win_rate', 5, 2);                     // %
            $table->decimal('avg_sales_cycle_days', 6, 1);
            $table->decimal('sales_velocity', 15, 2);              // daily revenue velocity
            $table->timestamps();
        });

        // ── 4. Lead assignment rules ──
        Schema::create('lead_assignment_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->index();
            $table->string('name', 100);
            $table->string('assignment_type', 20)->default('round_robin'); // round_robin|load_balance|score_based|territory
            $table->json('conditions')->nullable();   // [{field, operator, value}]
            $table->json('assignees');                // [user_id, ...]
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('priority')->default(0);
            $table->unsignedSmallInteger('last_assigned_index')->default(0); // for round robin
            $table->timestamps();
        });

        // ── 5. Deal forecasts (AI predictions) ──
        Schema::create('deal_ai_insights', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->index();
            $table->integer('deal_id')->index();
            $table->decimal('predicted_win_probability', 5, 2)->nullable();
            $table->decimal('predicted_close_value', 15, 2)->nullable();
            $table->string('predicted_close_date', 20)->nullable();
            $table->json('risk_factors')->nullable();         // [{type, description, severity}]
            $table->json('recommended_actions')->nullable();  // [{action, priority, due_date}]
            $table->string('sentiment', 20)->nullable();      // positive|neutral|negative
            $table->decimal('engagement_score', 5, 2)->nullable();
            $table->text('ai_notes')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        // ── 6. Sales KPI definitions (activity-based) ──
        Schema::create('sales_kpi_definitions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->index();
            $table->string('key', 50);               // calls_per_day, emails_per_day, demos_per_week
            $table->string('name_vi', 100);
            $table->string('name_en', 100);
            $table->string('metric_type', 20)->default('count'); // count|value|rate|duration
            $table->string('activity_type', 30)->nullable();     // call|email|meeting|demo|null=any
            $table->decimal('target_value', 10, 2)->default(0);
            $table->string('period_type', 10)->default('day');   // day|week|month
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['account_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_kpi_definitions');
        Schema::dropIfExists('deal_ai_insights');
        Schema::dropIfExists('lead_assignment_rules');
        Schema::dropIfExists('sales_velocity_snapshots');
        Schema::dropIfExists('sales_targets');
        Schema::table('deals', function (Blueprint $table) {
            $cols = ['win_probability', 'weighted_value', 'forecast_category', 'days_in_stage', 'stage_changed_at', 'stage_history', 'days_to_close', 'gross_margin', 'cost', 'currency', 'close_quarter', 'competitors', 'pain_points', 'next_steps', 'health_score', 'at_risk', 'ai_summary', 'last_activity_at', 'activity_count'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('deals', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
