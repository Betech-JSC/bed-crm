<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WebForm extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'created_by', 'name', 'slug', 'description',
        'form_type', 'status', 'style_settings', 'trigger_settings',
        'success_action', 'success_message', 'redirect_url',
        'email_notify', 'notify_emails',
        'auto_create_lead', 'lead_source', 'lead_status',
        'views_count', 'submissions_count',
    ];

    protected $casts = [
        'style_settings' => 'array',
        'trigger_settings' => 'array',
        'email_notify' => 'boolean',
        'auto_create_lead' => 'boolean',
    ];

    // ── Boot ──
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($form) {
            if (empty($form->slug)) {
                $form->slug = Str::slug($form->name) . '-' . Str::random(6);
            }
        });
    }

    // ── Types ──
    public static function getFormTypes(): array
    {
        return [
            'inline' => ['label' => 'Nhúng (Inline)', 'icon' => 'pi pi-code', 'desc' => 'Nhúng trực tiếp vào trang web'],
            'popup' => ['label' => 'Popup', 'icon' => 'pi pi-window-maximize', 'desc' => 'Hiển thị popup giữa màn hình'],
            'slide_in' => ['label' => 'Slide-in', 'icon' => 'pi pi-arrow-left', 'desc' => 'Trượt vào từ góc phải'],
            'floating_bar' => ['label' => 'Thanh nổi', 'icon' => 'pi pi-minus', 'desc' => 'Thanh cố định ở đầu/cuối trang'],
        ];
    }

    // ── Scopes ──
    public function scopeFilter($q, array $filters)
    {
        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $q->where(fn ($q2) => $q2->where('name', 'like', "%{$s}%")->orWhere('slug', 'like', "%{$s}%"));
        }
        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        if (!empty($filters['form_type'])) $q->where('form_type', $filters['form_type']);
        return $q;
    }

    // ── Accessors ──
    public function getConversionRateAttribute(): float
    {
        return $this->views_count > 0
            ? round(($this->submissions_count / $this->views_count) * 100, 1)
            : 0;
    }

    public function getEmbedUrlAttribute(): string
    {
        return url("/forms/embed/{$this->slug}");
    }

    public function getEmbedCodeAttribute(): string
    {
        $url = $this->embed_url;
        if ($this->form_type === 'inline') {
            return "<iframe src=\"{$url}\" width=\"100%\" height=\"500\" frameborder=\"0\" style=\"border:none;\"></iframe>";
        }
        return "<script src=\"{$url}\" data-form=\"{$this->slug}\" data-type=\"{$this->form_type}\"></script>";
    }

    // ── Relations ──
    public function fields(): HasMany { return $this->hasMany(WebFormField::class)->orderBy('sort_order'); }
    public function submissions(): HasMany { return $this->hasMany(WebFormSubmission::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
}
