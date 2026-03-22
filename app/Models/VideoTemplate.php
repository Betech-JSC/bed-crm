<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id', 'name', 'category', 'description', 'thumbnail',
        'scene_structure', 'aspect_ratio', 'duration_seconds',
        'style_config', 'is_system', 'is_active', 'usage_count',
    ];

    protected $casts = [
        'scene_structure' => 'array',
        'style_config' => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * System templates for quick start.
     */
    public static function getSystemTemplates(): array
    {
        return [
            [
                'name' => 'Flash Sale 15s',
                'category' => 'promo',
                'description' => 'Video khuyến mãi ngắn, thu hút trong 3 giây đầu',
                'aspect_ratio' => '9:16',
                'duration_seconds' => 15,
                'scene_structure' => [
                    ['scene' => 1, 'label' => 'Hook — Gây chú ý', 'duration' => 3, 'type' => 'text_overlay', 'notes' => 'Câu hỏi/sốc/vấn đề'],
                    ['scene' => 2, 'label' => 'Sản phẩm xuất hiện', 'duration' => 4, 'type' => 'product_reveal', 'notes' => 'Show sản phẩm'],
                    ['scene' => 3, 'label' => 'Tính năng nổi bật', 'duration' => 4, 'type' => 'feature_list', 'notes' => '2-3 điểm nổi bật'],
                    ['scene' => 4, 'label' => 'CTA + Giá', 'duration' => 4, 'type' => 'cta', 'notes' => 'Giá khuyến mãi + CTA mạnh'],
                ],
            ],
            [
                'name' => 'Product Review 30s',
                'category' => 'product',
                'description' => 'Video review sản phẩm theo phong cách UGC',
                'aspect_ratio' => '9:16',
                'duration_seconds' => 30,
                'scene_structure' => [
                    ['scene' => 1, 'label' => 'Hook — Vấn đề', 'duration' => 3, 'type' => 'hook', 'notes' => 'Tạo đồng cảm với vấn đề'],
                    ['scene' => 2, 'label' => 'Giới thiệu sản phẩm', 'duration' => 5, 'type' => 'intro', 'notes' => 'Unboxing / giới thiệu'],
                    ['scene' => 3, 'label' => 'Demo sử dụng', 'duration' => 8, 'type' => 'demo', 'notes' => 'Cách dùng, before/after'],
                    ['scene' => 4, 'label' => 'Ưu điểm & kết quả', 'duration' => 6, 'type' => 'benefits', 'notes' => 'Kết quả thực tế'],
                    ['scene' => 5, 'label' => 'Social proof', 'duration' => 4, 'type' => 'social_proof', 'notes' => 'Reviews, ratings'],
                    ['scene' => 6, 'label' => 'CTA', 'duration' => 4, 'type' => 'cta', 'notes' => 'Link mua + mã giảm giá'],
                ],
            ],
            [
                'name' => 'Brand Story 45s',
                'category' => 'story',
                'description' => 'Kể chuyện thương hiệu, tạo kết nối cảm xúc',
                'aspect_ratio' => '9:16',
                'duration_seconds' => 45,
                'scene_structure' => [
                    ['scene' => 1, 'label' => 'Mở đầu — Câu chuyện', 'duration' => 5, 'type' => 'narrative', 'notes' => 'Bối cảnh / vấn đề xã hội'],
                    ['scene' => 2, 'label' => 'Tầm nhìn / Sứ mệnh', 'duration' => 8, 'type' => 'mission', 'notes' => 'Tại sao chúng tôi tồn tại'],
                    ['scene' => 3, 'label' => 'Hành trình', 'duration' => 10, 'type' => 'journey', 'notes' => 'Quá trình phát triển'],
                    ['scene' => 4, 'label' => 'Sản phẩm / Dịch vụ', 'duration' => 8, 'type' => 'product', 'notes' => 'Giải pháp mang đến'],
                    ['scene' => 5, 'label' => 'Tác động', 'duration' => 6, 'type' => 'impact', 'notes' => 'Kết quả & con số'],
                    ['scene' => 6, 'label' => 'CTA', 'duration' => 8, 'type' => 'cta', 'notes' => 'Tham gia cùng chúng tôi'],
                ],
            ],
            [
                'name' => 'Tutorial 60s',
                'category' => 'tutorial',
                'description' => 'Hướng dẫn sử dụng sản phẩm chi tiết',
                'aspect_ratio' => '9:16',
                'duration_seconds' => 60,
                'scene_structure' => [
                    ['scene' => 1, 'label' => 'Hook — Kết quả trước', 'duration' => 5, 'type' => 'result_first', 'notes' => 'Show kết quả cuối cùng'],
                    ['scene' => 2, 'label' => 'Giới thiệu', 'duration' => 5, 'type' => 'intro', 'notes' => 'Sản phẩm + mục tiêu'],
                    ['scene' => 3, 'label' => 'Bước 1', 'duration' => 10, 'type' => 'step', 'notes' => 'Bước đầu tiên'],
                    ['scene' => 4, 'label' => 'Bước 2', 'duration' => 10, 'type' => 'step', 'notes' => 'Bước tiếp theo'],
                    ['scene' => 5, 'label' => 'Bước 3', 'duration' => 10, 'type' => 'step', 'notes' => 'Hoàn thiện'],
                    ['scene' => 6, 'label' => 'Kết quả', 'duration' => 8, 'type' => 'result', 'notes' => 'Before/after'],
                    ['scene' => 7, 'label' => 'Tips bổ sung', 'duration' => 5, 'type' => 'tips', 'notes' => 'Mẹo thêm'],
                    ['scene' => 8, 'label' => 'CTA', 'duration' => 7, 'type' => 'cta', 'notes' => 'Follow + link mua'],
                ],
            ],
            [
                'name' => 'UGC Authentic 20s',
                'category' => 'ugc',
                'description' => 'Phong cách tự nhiên, chân thực như người dùng thật',
                'aspect_ratio' => '9:16',
                'duration_seconds' => 20,
                'scene_structure' => [
                    ['scene' => 1, 'label' => 'Reaction/Hook tự nhiên', 'duration' => 3, 'type' => 'reaction', 'notes' => '"OMG phải chia sẻ ngay"'],
                    ['scene' => 2, 'label' => 'Show sản phẩm casual', 'duration' => 5, 'type' => 'casual_show', 'notes' => 'Góc tự nhiên, không dàn dựng'],
                    ['scene' => 3, 'label' => 'Trải nghiệm thực', 'duration' => 7, 'type' => 'experience', 'notes' => 'Dùng thử, cảm nhận'],
                    ['scene' => 4, 'label' => 'Kết luận & recommend', 'duration' => 5, 'type' => 'recommend', 'notes' => 'Đánh giá + khuyên mua'],
                ],
            ],
        ];
    }

    // ── Scopes ──
    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeSystem($q) { return $q->where('is_system', true); }
}
