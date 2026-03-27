<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GmbPost extends Model
{
    protected $fillable = [
        'gmb_location_id', 'post_type', 'content', 'image_url',
        'cta_type', 'cta_url', 'event_start', 'event_end',
        'status', 'published_at',
    ];

    protected $casts = [
        'event_start' => 'date',
        'event_end' => 'date',
        'published_at' => 'datetime',
    ];

    public static function getPostTypes(): array
    {
        return [
            'update' => ['label' => 'Cập nhật', 'icon' => 'pi pi-megaphone'],
            'event' => ['label' => 'Sự kiện', 'icon' => 'pi pi-calendar'],
            'offer' => ['label' => 'Ưu đãi', 'icon' => 'pi pi-tag'],
        ];
    }

    public function location(): BelongsTo { return $this->belongsTo(GmbLocation::class, 'gmb_location_id'); }
}
