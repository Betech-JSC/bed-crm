<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiContentTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'name', 'category', 'description',
        'system_prompt', 'user_prompt_template', 'variables',
        'ai_model', 'is_system', 'usage_count',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_system' => 'boolean',
    ];

    public static function getCategories(): array
    {
        return [
            'blog_seo' => ['label' => 'Blog SEO', 'icon' => 'pi pi-file-edit', 'color' => '#6366f1'],
            'social_caption' => ['label' => 'Social Caption', 'icon' => 'pi pi-share-alt', 'color' => '#ec4899'],
            'email_sequence' => ['label' => 'Email Sequence', 'icon' => 'pi pi-envelope', 'color' => '#f59e0b'],
            'ad_copy' => ['label' => 'Ad Copy', 'icon' => 'pi pi-megaphone', 'color' => '#10b981'],
            'product_desc' => ['label' => 'Mô tả sản phẩm', 'icon' => 'pi pi-box', 'color' => '#0ea5e9'],
            'landing_page' => ['label' => 'Landing Page', 'icon' => 'pi pi-desktop', 'color' => '#8b5cf6'],
        ];
    }

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
}
