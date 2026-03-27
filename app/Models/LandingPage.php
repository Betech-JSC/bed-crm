<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LandingPage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'created_by', 'title', 'slug', 'description',
        'status', 'meta_title', 'meta_description', 'og_image', 'favicon',
        'blocks', 'style_settings', 'web_form_id',
        'visits_count', 'conversions_count', 'custom_domain',
    ];

    protected $casts = [
        'blocks' => 'array',
        'style_settings' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title) . '-' . Str::random(6);
            }
        });
    }

    // ── Accessors ──
    public function getConversionRateAttribute(): float
    {
        return $this->visits_count > 0
            ? round(($this->conversions_count / $this->visits_count) * 100, 1) : 0;
    }

    public function getPublicUrlAttribute(): string
    {
        return url("/lp/{$this->slug}");
    }

    // ── Block Templates ──
    public static function getBlockTypes(): array
    {
        return [
            'hero' => ['label' => 'Hero Section', 'icon' => 'pi pi-image', 'defaults' => ['heading' => 'Tiêu đề chính', 'subheading' => 'Mô tả ngắn...', 'cta_text' => 'Liên hệ ngay', 'bg_color' => '#4f46e5', 'text_color' => '#ffffff']],
            'features' => ['label' => 'Features/Services', 'icon' => 'pi pi-th-large', 'defaults' => ['heading' => 'Dịch vụ của chúng tôi', 'items' => [['title' => 'Feature 1', 'desc' => 'Mô tả...', 'icon' => '🚀'], ['title' => 'Feature 2', 'desc' => 'Mô tả...', 'icon' => '⚡'], ['title' => 'Feature 3', 'desc' => 'Mô tả...', 'icon' => '🎯']]]],
            'testimonials' => ['label' => 'Testimonials', 'icon' => 'pi pi-comments', 'defaults' => ['heading' => 'Khách hàng nói gì?', 'items' => [['name' => 'Nguyễn Văn A', 'role' => 'CEO', 'text' => 'Dịch vụ tuyệt vời!', 'avatar' => '']]]],
            'cta' => ['label' => 'Call to Action', 'icon' => 'pi pi-bolt', 'defaults' => ['heading' => 'Sẵn sàng bắt đầu?', 'subheading' => 'Liên hệ ngay để nhận tư vấn miễn phí', 'button_text' => 'Liên hệ', 'bg_color' => '#10b981']],
            'form' => ['label' => 'Contact Form', 'icon' => 'pi pi-file-edit', 'defaults' => ['heading' => 'Để lại thông tin', 'use_web_form' => true]],
            'faq' => ['label' => 'FAQ', 'icon' => 'pi pi-question-circle', 'defaults' => ['heading' => 'Câu hỏi thường gặp', 'items' => [['q' => 'Câu hỏi 1?', 'a' => 'Trả lời...']]]],
            'stats' => ['label' => 'Stats/Numbers', 'icon' => 'pi pi-chart-bar', 'defaults' => ['items' => [['number' => '500+', 'label' => 'Khách hàng'], ['number' => '98%', 'label' => 'Hài lòng'], ['number' => '24/7', 'label' => 'Hỗ trợ']]]],
            'text' => ['label' => 'Text Block', 'icon' => 'pi pi-align-left', 'defaults' => ['content' => 'Nội dung...']],
        ];
    }

    // ── Scopes ──
    public function scopeFilter($q, array $filters)
    {
        if (!empty($filters['search'])) {
            $q->where('title', 'like', "%{$filters['search']}%");
        }
        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        return $q;
    }

    // ── Relations ──
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function webForm(): BelongsTo { return $this->belongsTo(WebForm::class); }
}
