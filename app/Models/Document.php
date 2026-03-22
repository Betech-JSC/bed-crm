<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'title', 'type', 'category', 'description',
        'content', 'file_path', 'status', 'version', 'tags',
        'created_by', 'sort_order',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeRecords($q) { return $q->where('type', 'record'); }
    public function scopeTemplates($q) { return $q->where('type', 'template'); }
    public function scopePublished($q) { return $q->where('status', 'published'); }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'record' ? 'Biên bản' : 'Biểu mẫu';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Nháp',
            'published' => 'Đã xuất bản',
            'archived' => 'Lưu trữ',
            default => ucfirst($this->status),
        };
    }
}
