<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerReview extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'customer_name', 'customer_email', 'customer_company',
        'customer_role', 'customer_avatar', 'review_text', 'rating', 'platform',
        'service_type', 'status', 'video_url', 'media', 'review_date',
    ];

    protected $casts = [
        'media' => 'array',
        'review_date' => 'date',
    ];

    public static function getPlatforms(): array
    {
        return [
            'direct' => ['label' => 'Website', 'icon' => 'pi pi-globe', 'color' => '#6366f1'],
            'google' => ['label' => 'Google', 'icon' => 'pi pi-google', 'color' => '#ea4335'],
            'facebook' => ['label' => 'Facebook', 'icon' => 'pi pi-facebook', 'color' => '#1877f2'],
            'trustpilot' => ['label' => 'Trustpilot', 'icon' => 'pi pi-star', 'color' => '#00b67a'],
            'zalo' => ['label' => 'Zalo', 'icon' => 'pi pi-comments', 'color' => '#0068ff'],
        ];
    }

    public static function getServiceTypes(): array
    {
        return [
            'web_design' => 'Thiết kế website',
            'seo' => 'SEO',
            'crm' => 'CRM',
            'marketing' => 'Marketing',
            'branding' => 'Branding',
            'app_dev' => 'App Development',
        ];
    }

    public function scopeFilter($q, array $filters)
    {
        if (!empty($filters['search'])) {
            $q->where(fn ($q2) => $q2->where('customer_name', 'like', "%{$filters['search']}%")
                ->orWhere('review_text', 'like', "%{$filters['search']}%"));
        }
        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        if (!empty($filters['platform'])) $q->where('platform', $filters['platform']);
        if (!empty($filters['rating'])) $q->where('rating', $filters['rating']);
        return $q;
    }

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
}
