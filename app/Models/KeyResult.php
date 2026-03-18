<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeyResult extends Model
{
    // ── Metric Types ──
    public const METRIC_NUMBER = 'number';
    public const METRIC_CURRENCY = 'currency';
    public const METRIC_PERCENTAGE = 'percentage';
    public const METRIC_BOOLEAN = 'boolean';

    // ── Data Sources (CRM auto-tracking) ──
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_DEALS_COUNT = 'deals_count';
    public const SOURCE_DEALS_VALUE = 'deals_value';
    public const SOURCE_LEADS_COUNT = 'leads_count';
    public const SOURCE_LEADS_QUALIFIED = 'leads_qualified';
    public const SOURCE_REVENUE = 'revenue';
    public const SOURCE_NEW_CUSTOMERS = 'new_customers';
    public const SOURCE_CHURN_RATE = 'churn_rate';
    public const SOURCE_PROJECT_COMPLETION = 'project_completion';
    public const SOURCE_CUSTOM_KPI = 'custom_kpi';

    // ── Statuses ──
    public const STATUS_ON_TRACK = 'on_track';
    public const STATUS_AT_RISK = 'at_risk';
    public const STATUS_BEHIND = 'behind';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'account_id', 'objective_id', 'title', 'description',
        'metric_type', 'start_value', 'target_value', 'current_value', 'unit',
        'data_source', 'data_source_config', 'kpi_definition_id',
        'owner_id', 'status', 'confidence', 'weight',
    ];

    protected $casts = [
        'start_value' => 'decimal:2',
        'target_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'data_source_config' => 'array',
        'confidence' => 'integer',
        'weight' => 'integer',
    ];

    // ── Relationships ──
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function objective(): BelongsTo { return $this->belongsTo(Objective::class); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function kpiDefinition(): BelongsTo { return $this->belongsTo(KpiDefinition::class); }

    // ── Progress Calculation ──

    /**
     * Calculate progress percentage (0-100).
     */
    public function getProgress(): float
    {
        if ($this->metric_type === self::METRIC_BOOLEAN) {
            return (float) $this->current_value >= 1 ? 100 : 0;
        }

        $range = (float) $this->target_value - (float) $this->start_value;
        if ($range == 0) return 0;

        $progress = ((float) $this->current_value - (float) $this->start_value) / $range * 100;
        return round(max(0, min(100, $progress)), 1);
    }

    /**
     * Check-in: update current value and recalculate.
     */
    public function checkIn(float $newValue, ?int $confidence = null): void
    {
        $this->current_value = $newValue;

        if ($confidence !== null) {
            $this->confidence = $confidence;
        }

        // Auto-detect status
        $progress = $this->getProgress();
        if ($progress >= 100) {
            $this->status = self::STATUS_COMPLETED;
        } elseif ($progress >= 60) {
            $this->status = self::STATUS_ON_TRACK;
        } elseif ($progress >= 30) {
            $this->status = self::STATUS_AT_RISK;
        } else {
            $this->status = self::STATUS_BEHIND;
        }

        $this->save();

        // Trigger objective rollup
        $this->objective->recalculateProgress();
    }

    /**
     * Get auto-tracked data sources with labels (đa ngữ).
     */
    public static function getDataSources(): array
    {
        return [
            self::SOURCE_MANUAL => ['vi' => 'Nhập thủ công', 'en' => 'Manual Entry'],
            self::SOURCE_DEALS_COUNT => ['vi' => 'Số deal thắng', 'en' => 'Won Deals Count'],
            self::SOURCE_DEALS_VALUE => ['vi' => 'Giá trị deal thắng', 'en' => 'Won Deals Value'],
            self::SOURCE_LEADS_COUNT => ['vi' => 'Số lead mới', 'en' => 'New Leads Count'],
            self::SOURCE_LEADS_QUALIFIED => ['vi' => 'Lead đạt chuẩn', 'en' => 'Qualified Leads'],
            self::SOURCE_REVENUE => ['vi' => 'Doanh thu', 'en' => 'Revenue'],
            self::SOURCE_NEW_CUSTOMERS => ['vi' => 'Khách hàng mới', 'en' => 'New Customers'],
            self::SOURCE_CHURN_RATE => ['vi' => 'Tỷ lệ rời bỏ', 'en' => 'Churn Rate'],
            self::SOURCE_PROJECT_COMPLETION => ['vi' => 'Dự án hoàn thành', 'en' => 'Completed Projects'],
            self::SOURCE_CUSTOM_KPI => ['vi' => 'KPI tuỳ chỉnh', 'en' => 'Custom KPI'],
        ];
    }
}
