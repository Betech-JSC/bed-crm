<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SalesChannel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_id', 'name', 'slug', 'icon', 'color',
        'description', 'stages', 'default_fields', 'is_active',
        'is_default', 'sort_order', 'settings',
    ];

    protected $casts = [
        'stages' => 'array',
        'default_fields' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    // ── Boot ──
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($channel) {
            if (!$channel->slug) {
                $channel->slug = Str::slug($channel->name);
            }
        });
    }

    // ── Preset Channel Templates ──
    public static function getPresets(): array
    {
        return [
            [
                'name' => 'Zalo',
                'slug' => 'zalo',
                'icon' => 'pi pi-comments',
                'color' => '#0068ff',
                'stages' => [
                    ['key' => 'new_contact', 'label' => 'Liên hệ mới', 'color' => '#6366f1'],
                    ['key' => 'chatting', 'label' => 'Đang chat', 'color' => '#3b82f6'],
                    ['key' => 'need_identified', 'label' => 'Xác định nhu cầu', 'color' => '#0ea5e9'],
                    ['key' => 'proposal_sent', 'label' => 'Gửi báo giá', 'color' => '#f59e0b'],
                    ['key' => 'negotiation', 'label' => 'Đàm phán', 'color' => '#ef6820'],
                    ['key' => 'closed_won', 'label' => 'Chốt thành công', 'color' => '#16a34a'],
                    ['key' => 'closed_lost', 'label' => 'Thất bại', 'color' => '#ef4444'],
                ],
            ],
            [
                'name' => 'Facebook',
                'slug' => 'facebook',
                'icon' => 'pi pi-facebook',
                'color' => '#1877f2',
                'stages' => [
                    ['key' => 'inbox', 'label' => 'Inbox', 'color' => '#6366f1'],
                    ['key' => 'qualify', 'label' => 'Đánh giá', 'color' => '#3b82f6'],
                    ['key' => 'demo_call', 'label' => 'Demo / Gọi điện', 'color' => '#0ea5e9'],
                    ['key' => 'proposal', 'label' => 'Đề xuất', 'color' => '#f59e0b'],
                    ['key' => 'closed_won', 'label' => 'Chốt thành công', 'color' => '#16a34a'],
                    ['key' => 'closed_lost', 'label' => 'Thất bại', 'color' => '#ef4444'],
                ],
            ],
            [
                'name' => 'Website / SEO',
                'slug' => 'website',
                'icon' => 'pi pi-globe',
                'color' => '#16a34a',
                'stages' => [
                    ['key' => 'audit', 'label' => 'Audit', 'color' => '#6366f1'],
                    ['key' => 'connect', 'label' => 'Kết bạn', 'color' => '#3b82f6'],
                    ['key' => 'propose', 'label' => 'Gửi giải pháp', 'color' => '#0ea5e9'],
                    ['key' => 'discuss', 'label' => 'Trao đổi', 'color' => '#f59e0b'],
                    ['key' => 'quote', 'label' => 'Báo giá', 'color' => '#ef6820'],
                    ['key' => 'closed_won', 'label' => 'Chốt thành công', 'color' => '#16a34a'],
                    ['key' => 'closed_lost', 'label' => 'Thất bại', 'color' => '#ef4444'],
                ],
            ],
            [
                'name' => 'Shopee / TMDT',
                'slug' => 'ecommerce',
                'icon' => 'pi pi-shopping-cart',
                'color' => '#ee4d2d',
                'stages' => [
                    ['key' => 'lead_in', 'label' => 'Khách hỏi', 'color' => '#6366f1'],
                    ['key' => 'consulting', 'label' => 'Tư vấn', 'color' => '#3b82f6'],
                    ['key' => 'order_placed', 'label' => 'Đặt hàng', 'color' => '#f59e0b'],
                    ['key' => 'shipping', 'label' => 'Giao hàng', 'color' => '#0ea5e9'],
                    ['key' => 'completed', 'label' => 'Hoàn thành', 'color' => '#16a34a'],
                    ['key' => 'cancelled', 'label' => 'Huỷ', 'color' => '#ef4444'],
                ],
            ],
            [
                'name' => 'Telesales',
                'slug' => 'telesales',
                'icon' => 'pi pi-phone',
                'color' => '#8b5cf6',
                'stages' => [
                    ['key' => 'cold_call', 'label' => 'Gọi lạnh', 'color' => '#94a3b8'],
                    ['key' => 'interested', 'label' => 'Quan tâm', 'color' => '#3b82f6'],
                    ['key' => 'meeting', 'label' => 'Hẹn gặp', 'color' => '#0ea5e9'],
                    ['key' => 'proposal', 'label' => 'Báo giá', 'color' => '#f59e0b'],
                    ['key' => 'follow_up', 'label' => 'Theo dõi', 'color' => '#ef6820'],
                    ['key' => 'closed_won', 'label' => 'Chốt thành công', 'color' => '#16a34a'],
                    ['key' => 'closed_lost', 'label' => 'Thất bại', 'color' => '#ef4444'],
                ],
            ],
            [
                'name' => 'Referral / Giới thiệu',
                'slug' => 'referral',
                'icon' => 'pi pi-users',
                'color' => '#f59e0b',
                'stages' => [
                    ['key' => 'referred', 'label' => 'Được giới thiệu', 'color' => '#f59e0b'],
                    ['key' => 'contact', 'label' => 'Liên hệ', 'color' => '#3b82f6'],
                    ['key' => 'meeting', 'label' => 'Gặp mặt', 'color' => '#0ea5e9'],
                    ['key' => 'proposal', 'label' => 'Đề xuất', 'color' => '#ef6820'],
                    ['key' => 'closed_won', 'label' => 'Chốt thành công', 'color' => '#16a34a'],
                    ['key' => 'closed_lost', 'label' => 'Thất bại', 'color' => '#ef4444'],
                ],
            ],
        ];
    }

    /**
     * Get open stages (excluding closed_won/closed_lost)
     */
    public function getOpenStagesAttribute(): array
    {
        return collect($this->stages ?? [])
            ->reject(fn ($s) => in_array($s['key'], ['closed_won', 'closed_lost', 'cancelled', 'completed']))
            ->values()
            ->toArray();
    }

    /**
     * Get closed stages
     */
    public function getClosedStagesAttribute(): array
    {
        return collect($this->stages ?? [])
            ->filter(fn ($s) => in_array($s['key'], ['closed_won', 'closed_lost', 'cancelled', 'completed']))
            ->values()
            ->toArray();
    }

    /**
     * Get stage label by key
     */
    public function getStageLabel(string $key): string
    {
        $stage = collect($this->stages ?? [])->firstWhere('key', $key);
        return $stage['label'] ?? $key;
    }

    // ── Relationships ──
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function pipelines(): HasMany { return $this->hasMany(SalesPipeline::class, 'channel_id'); }

    // ── Scopes ──
    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeOrdered($query) { return $query->orderBy('sort_order')->orderBy('name'); }
}
