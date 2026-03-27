<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SeoAuditIssue extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'page_url', 'issue_type', 'severity', 'description',
        'status', 'recommendation',
    ];

    public static function getIssueTypes(): array
    {
        return [
            'missing_title' => ['label' => 'Thiếu Title Tag', 'icon' => '🏷️'],
            'missing_meta' => ['label' => 'Thiếu Meta Description', 'icon' => '📝'],
            'duplicate_meta' => ['label' => 'Duplicate Meta', 'icon' => '🔄'],
            'no_h1' => ['label' => 'Thiếu H1', 'icon' => '📰'],
            'missing_alt' => ['label' => 'Thiếu Alt Image', 'icon' => '🖼️'],
            'slow_page' => ['label' => 'Page chậm', 'icon' => '🐌'],
            'broken_link' => ['label' => 'Link lỗi', 'icon' => '🔗'],
            'no_ssl' => ['label' => 'Thiếu SSL', 'icon' => '🔒'],
            'mobile_issues' => ['label' => 'Mobile không tối ưu', 'icon' => '📱'],
            'thin_content' => ['label' => 'Nội dung mỏng', 'icon' => '📄'],
        ];
    }

    public function scopeFilter($q, array $filters)
    {
        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        if (!empty($filters['severity'])) $q->where('severity', $filters['severity']);
        if (!empty($filters['issue_type'])) $q->where('issue_type', $filters['issue_type']);
        return $q;
    }

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
}
